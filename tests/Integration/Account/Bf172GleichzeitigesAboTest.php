<?php

declare(strict_types=1);

namespace App\Tests\Integration\Account;

use App\Account\NewsletterSubscription;
use App\Entity\User;
use App\Marketing\MarketingContactRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * BF-172 — zwei gleichzeitige Erst-Abos desselben Kontos aus zwei Sitzungen ergaben HTTP 500.
 *
 * Beide Anfragen fanden noch keine Zeile im Auftragsbuch und legten eine an; die zweite scheiterte
 * am Unique-Index auf `email`. Nachgestellt wird der Augenblick dazwischen: Kurz vor dem Schreiben
 * dieser Anfrage hat die andere ihre Zeile schon gespeichert. Muster BF-121.
 */
final class Bf172GleichzeitigesAboTest extends KernelTestCase
{
    public function testBf172DieVerloreneAnfrageEndetOhneAusnahme(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = new User();
        $user->setName('Gleichzeitig')->setEmail('f12-gleichzeitig@qa.lu')->setPassword('x')->setIsVerified(true);
        $em->persist($user);
        $em->flush();

        $verbindung = $em->getConnection();
        $andereAnfrage = new class($verbindung) {
            public bool $erledigt = false;

            public function __construct(private readonly \Doctrine\DBAL\Connection $verbindung)
            {
            }

            public function preFlush(): void
            {
                if ($this->erledigt) {
                    return;
                }
                $this->erledigt = true;
                $this->verbindung->executeStatement(
                    "INSERT INTO marketing_contact (email, locale, origin, consent_at, sync_state, attempts, created_at, updated_at) VALUES (?, 'de', 'account', NOW(), 'pending', 0, NOW(), NOW())",
                    ['f12-gleichzeitig@qa.lu'],
                );
            }
        };
        $em->getEventManager()->addEventListener(Events::preFlush, $andereAnfrage);

        $dienst = new NewsletterSubscription(static::getContainer()->get(MarketingContactRegistry::class), $em);

        try {
            self::assertFalse($dienst->subscribe($user, 'de'), 'Die andere Anfrage hat das Abo schon angelegt — für diese ist nichts geschehen.');
        } finally {
            $em->getEventManager()->removeEventListener(Events::preFlush, $andereAnfrage);
        }
        self::assertTrue($andereAnfrage->erledigt);
    }
}
