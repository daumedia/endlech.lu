<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Service\AdminStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Feature 12, AK-49 — die zwei Summen der Verwaltung.
 *
 * Gezählt werden Konten, nicht Durchläufe: Wer die Einführung im Profil erneut ansieht, ändert
 * die Zahl nicht (EC-09).
 */
final class UserOnboardingCountTest extends KernelTestCase
{
    public function testAk49VorgeseheneUndAngezeigteKontenWerdenGezaehlt(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $stats = static::getContainer()->get(AdminStatsService::class);

        $vorgesehenVorher = $stats->getOnboardingDueCount();
        $angezeigtVorher = $stats->getOnboardingShownCount();

        foreach (['f12-zaehl-a@qa.lu' => true, 'f12-zaehl-b@qa.lu' => false] as $email => $angezeigt) {
            $user = new User();
            $user->setName('Zählung')->setEmail($email)->setPassword('x')->setIsVerified(true);
            $user->markOnboardingDue();
            if ($angezeigt) {
                $user->markOnboardingShown(new \DateTimeImmutable());
            }
            $em->persist($user);
        }
        // Ein Bestandskonto zählt nicht mit.
        $bestand = new User();
        $bestand->setName('Bestand')->setEmail('f12-zaehl-bestand@qa.lu')->setPassword('x')->setIsVerified(true);
        $em->persist($bestand);
        $em->flush();

        self::assertSame($vorgesehenVorher + 2, $stats->getOnboardingDueCount());
        self::assertSame($angezeigtVorher + 1, $stats->getOnboardingShownCount());
    }
}
