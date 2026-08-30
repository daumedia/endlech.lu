<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\RateLimit\ActionLimiter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        #[Autowire(service: 'limiter.registration')]
        private readonly RateLimiterFactoryInterface $registrationLimiter,
    ) {
    }
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        // Erst nach handleRequest und nur für abgeschickte Formulare: Das reine
        // Aufrufen der Seite darf kein Kontingent verbrauchen – gedeckelt wird die
        // Anlage, nicht das Lesen. Muster wie in PartnerController::submit().
        //
        // ⚠ BF-11: `consume(0)` FRAGT nur ab, es verbraucht nichts. Verbraucht wird
        // erst, wenn die Anlage tatsächlich stattfindet — sonst sperren fünf
        // Tippfehler eine Stunde lang aus, ohne dass ein Konto oder eine Mail
        // entstanden wäre. Der Deckel soll den Angreifer treffen, nicht den, der
        // sich beim Passwort vertippt.
        $limiter = ActionLimiter::for($this->registrationLimiter, $request->getClientIp());

        if ($form->isSubmitted()) {
            if (!$limiter->isAllowed()) {
                $this->addFlash('error', $this->translator->trans('flash.register_rate_limited'));

                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ], new Response(null, Response::HTTP_TOO_MANY_REQUESTS));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $limiter->consume();

            // ⚠ BF-09: Anti-Enumeration, wie sie die API seit jeher hat. Vorher
            // meldete das Formular „Diese E-Mail-Adresse wird bereits verwendet"
            // und verriet damit, wer hier ein Konto hat. Auf einer
            // Barrierefreiheitsplattform ist das eine Angabe, die niemanden etwas
            // angeht: Wer sie abfragt, erfährt nicht, dass jemand hier isst,
            // sondern dass jemand nach barrierefreien Lokalen sucht.
            //
            // Das Passwort wird in BEIDEN Zweigen gehasht. Ohne das verriete die
            // Antwortzeit, was die Meldung verschweigt — Argon2 braucht spürbar
            // länger als eine Abfrage, die nichts findet.
            $hash = $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());

            $vorhanden = $entityManager->getRepository(User::class)
                ->findOneBy(['email' => $user->getEmail()]);

            if ($vorhanden instanceof User) {
                $this->sendeKontoExistiertHinweis($mailer, (string) $user->getEmail(), $request->getLocale());

                $this->addFlash('success', $this->translator->trans('flash.register_success'));

                return $this->redirectToRoute('app_verify_notice');
            }

            $user->setPassword($hash);

            // Werbe-Einwilligung (Feature 04): nur der Zeitpunkt wird
            // festgehalten. Ohne Häkchen bleibt das Feld null – bewusst kein
            // else-Zweig, denn „nicht eingewilligt" ist der Ausgangszustand und
            // kein Vorgang.
            //
            // ⚠ Hier geht nichts an Brevo (AK-05). Ein Konto mit unbestätigter
            // Adresse gehört in keine Verteilerliste – übertragen wird erst bei
            // der E-Mail-Verifikation. Wer zustimmt und nie verifiziert, wartet
            // folgenlos.
            //
            // ⚠ Der Aufruf steht NACH dem Enumerations-Zweig oben: Für eine
            // bereits vergebene Adresse ist die Antwort dieselbe wie hier, und
            // das Häkchen darf daran nichts ändern.
            if (true === $form->get('marketingConsent')->getData()) {
                $user->setMarketingConsentAt(new \DateTimeImmutable());
            }

            $token = $user->generateVerificationToken();

            $entityManager->persist($user);
            $entityManager->flush();

            $verifyUrl = $this->generateUrl('app_verify_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            $email = (new TemplatedEmail())
                ->to($user->getEmail())
                // Ohne diese Zeile rendert das Template erst beim Versand – bei
                // asynchronem Transport also im Worker, wo es keine Request-Sprache
                // gibt und default_locale (lb) greift. Der Betreff wäre dann
                // französisch, der Inhalt luxemburgisch. BodyRenderer wertet
                // getLocale() aus und rendert über den LocaleSwitcher.
                ->locale($request->getLocale())
                ->subject($this->translator->trans('email.verify_subject'))
                ->htmlTemplate('email/verification.html.twig')
                ->context([
                    'user' => $user,
                    'verifyUrl' => $verifyUrl,
                ]);

            try {
                $mailer->send($email);
            } catch (TransportExceptionInterface) {
                $this->addFlash('warning', $this->translator->trans('flash.register_email_failed'));

                return $this->redirectToRoute('app_verify_notice');
            }

            $this->addFlash('success', $this->translator->trans('flash.register_success'));

            return $this->redirectToRoute('app_verify_notice');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    /**
     * Sagt einem bestehenden Konto Bescheid, ohne es dem Aufrufer zu verraten.
     *
     * Der Hinweis auf das Zurücksetzen des Passworts steht seit Feature 01 wieder
     * drin — vorher verwies er auf eine Funktion, die es nicht gab.
     */
    private function sendeKontoExistiertHinweis(MailerInterface $mailer, string $email, string $locale): void
    {
        $mail = (new Email())
            ->to($email)
            ->subject($this->translator->trans('email.account_exists_subject', [], null, $locale))
            ->text($this->translator->trans('email.account_exists_text', [], null, $locale));

        try {
            $mailer->send($mail);
        } catch (TransportExceptionInterface) {
            // Die Antwort bleibt in jedem Fall dieselbe.
        }
    }
}
