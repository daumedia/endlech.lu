<?php

declare(strict_types=1);

namespace App\Tests\Functional\Board;

use App\Board\BoardModerator;
use App\Entity\BoardIdea;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;

/**
 * QA 06 · AK-54, AK-37, AK-50: der TATSÄCHLICHE Payload der einen Mail.
 *
 * Geprüft wird der gerenderte Inhalt, nicht der Quelltext, der ihn baut. Die
 * Zusage in `design.md` lautet: Empfänger ist ausschließlich der Verfasser,
 * übertragen werden Titel und Link — **nicht der Beschreibungstext**, weil dort
 * eine Gesundheitsangabe stehen kann.
 */
final class BoardNotifierPayloadTest extends AbstractWebTestCase
{
    use MailerAssertionsTrait;

    private const GESUNDHEIT = 'Ich bin auf einen Rollstuhl angewiesen und habe eine Angststörung.';
    private const KONTAKT = 'Telefon 691 123456, privat@example.org';

    public function testAK54_MailTraegtWederBeschreibungstextNochFremdeEmpfaenger(): void
    {
        $client = static::createClient();
        $verfasser = $this->user($client, 'user@endlech.lu');
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $idee = (new BoardIdea())
            ->setTitle('Reizarme Ecken kennzeichnen')
            ->setDescription(self::GESUNDHEIT . ' ' . self::KONTAKT)
            ->setSlug('reizarme-ecken')
            ->setLocale('de')
            ->setSubmittedBy($verfasser);
        $em->persist($idee);
        $em->flush();

        $client->getContainer()->get(BoardModerator::class)->publish($idee);

        self::assertEmailCount(1);
        $mail = self::getMailerMessage();
        self::assertNotNull($mail);

        // Empfänger: ausschließlich der Verfasser. Keine Sammel- oder
        // Betreiberadresse (AK-54).
        $empfaenger = array_map(static fn ($a) => $a->getAddress(), $mail->getTo());
        self::assertSame(['user@endlech.lu'], $empfaenger);
        self::assertSame([], $mail->getCc());
        self::assertSame([], $mail->getBcc());

        $inhalt = $mail->getHtmlBody() . ' ' . (string) $mail->getTextBody() . ' ' . $mail->getSubject();

        // Der Titel geht mit …
        self::assertStringContainsString('Reizarme Ecken kennzeichnen', $inhalt);
        // … der Beschreibungstext NICHT.
        self::assertStringNotContainsString(self::GESUNDHEIT, $inhalt, 'Der Beschreibungstext darf die Anwendung nicht verlassen.');
        self::assertStringNotContainsString('Angststörung', $inhalt);
        self::assertStringNotContainsString('691 123456', $inhalt);
        self::assertStringNotContainsString('privat@example.org', $inhalt);
    }

    /** AK-53: Das Board legt keinen Marketing-Kontakt an. */
    public function testAK53_KeinMarketingKontaktDurchDasBoard(): void
    {
        $client = static::createClient();
        $verfasser = $this->user($client, 'user@endlech.lu');
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $vorher = (int) $em->getConnection()->fetchOne('SELECT COUNT(*) FROM marketing_contact');

        $idee = (new BoardIdea())
            ->setTitle('Idee ohne Werbefolgen')
            ->setDescription('Beschreibung.')
            ->setSlug('ohne-werbefolgen')
            ->setLocale('de')
            ->setSubmittedBy($verfasser);
        $em->persist($idee);
        $em->flush();

        $client->getContainer()->get(BoardModerator::class)->publish($idee);

        $nachher = (int) $em->getConnection()->fetchOne('SELECT COUNT(*) FROM marketing_contact');
        self::assertSame($vorher, $nachher, 'Das Board darf keinen Werbe-Kontakt erzeugen.');
    }

    /** AK-36: Bei einer Dublette zeigt der Link auf das Original. */
    public function testAK36_MailLinkZeigtAufDasOriginal(): void
    {
        $client = static::createClient();
        $verfasser = $this->user($client, 'user@endlech.lu');
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $original = (new BoardIdea())->setTitle('Original')->setDescription('X')->setSlug('original')->setLocale('de');
        $original->setPublishedAt(new \DateTimeImmutable());
        $em->persist($original);

        $dublette = (new BoardIdea())->setTitle('Dublette')->setDescription('Y')->setSlug('dublette')->setLocale('de')->setSubmittedBy($verfasser);
        $dublette->setDuplicateOf($original);
        $em->persist($dublette);
        $em->flush();

        $client->getContainer()->get(BoardModerator::class)->publish($dublette);

        $inhalt = (string) self::getMailerMessage()?->getHtmlBody();
        self::assertStringContainsString('/' . $original->getId() . '-original', $inhalt);
        self::assertStringContainsString('Original', $inhalt);
    }
}
