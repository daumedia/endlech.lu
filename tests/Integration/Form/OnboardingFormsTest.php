<?php

declare(strict_types=1);

namespace App\Tests\Integration\Form;

use App\Form\OnboardingAvatarType;
use App\Form\OnboardingNewsletterType;
use App\Form\ProfileType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Feature 12 — die beiden Formulare der Einführung (AK-25 bis AK-27, AK-30).
 *
 * Gegen die echte Validierung, nicht gegen eine Attrappe: AK-27 (umbenannte Textdatei) hängt
 * davon ab, dass der MIME-Typ aus dem Inhalt erkannt wird — genau das täuscht eine Attrappe vor.
 */
final class OnboardingFormsTest extends KernelTestCase
{
    private FormFactoryInterface $forms;
    private string $dir;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->forms = static::getContainer()->get(FormFactoryInterface::class);
        $this->dir = sys_get_temp_dir().'/endlech-f12-forms-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*') ?: []);
        rmdir($this->dir);
        parent::tearDown();
    }

    private function datei(string $name, string $inhalt): UploadedFile
    {
        $pfad = $this->dir.'/'.$name;
        file_put_contents($pfad, $inhalt);

        return new UploadedFile($pfad, $name, null, null, true);
    }

    private function png(): string
    {
        $bild = imagecreatetruecolor(4, 4);
        ob_start();
        imagepng($bild);

        return (string) ob_get_clean();
    }

    /**
     * @return list<string>
     */
    private function meldungen(FormInterface $form): array
    {
        $meldungen = [];
        foreach ($form->get('avatar')->getErrors(true) as $fehler) {
            $meldungen[] = $fehler->getMessageTemplate();
        }

        return $meldungen;
    }

    private function absenden(UploadedFile $datei): FormInterface
    {
        $form = $this->forms->create(OnboardingAvatarType::class, null, ['csrf_protection' => false]);
        $form->submit(['avatar' => $datei]);

        return $form;
    }

    public function testAk25PngWirdAngenommen(): void
    {
        $form = $this->absenden($this->datei('bild.png', $this->png()));

        self::assertTrue($form->isValid());
    }

    public function testAk26ZuGrossWirdMitDerMeldungDesProfilsAbgewiesen(): void
    {
        $form = $this->absenden($this->datei('gross.png', $this->png().str_repeat("\0", 2 * 1024 * 1024 + 1)));

        self::assertFalse($form->isValid());
        self::assertContains('profile.avatar_max_size', $this->meldungen($form));
    }

    public function testAk27UmbenannteTextdateiWirdAbgewiesen(): void
    {
        $form = $this->absenden($this->datei('bild.jpg', "Das ist kein Bild.\n"));

        self::assertFalse($form->isValid());
        self::assertContains('profile.avatar_mime_type', $this->meldungen($form));
    }

    public function testAk26ProfilUndEinfuehrungPruefenGleich(): void
    {
        // „dieselbe Meldung wie im Profil" — und dieselbe Grenze. Beide Formulare lesen die
        // Regel aus derselben Stelle.
        $profil = $this->forms->create(ProfileType::class, null, ['csrf_protection' => false])->get('avatar')->getConfig()->getOption('constraints');
        $einfuehrung = $this->forms->create(OnboardingAvatarType::class, null, ['csrf_protection' => false])->get('avatar')->getConfig()->getOption('constraints');

        self::assertEquals($profil, $einfuehrung);
    }

    public function testAk30HaekchenIstNichtVorangehakt(): void
    {
        $form = $this->forms->create(OnboardingNewsletterType::class, null, ['csrf_protection' => false]);

        self::assertFalse((bool) $form->get('consent')->getData());
        self::assertFalse($form->get('consent')->getConfig()->getRequired(), 'Die Einwilligung ist für nichts eine Bedingung (AK-39).');
    }

    public function testAk58FormulareTragenEinenCsrfSchutz(): void
    {
        foreach ([OnboardingAvatarType::class, OnboardingNewsletterType::class] as $typ) {
            self::assertTrue($this->forms->create($typ)->getConfig()->getOption('csrf_protection'), $typ);
        }
    }
}
