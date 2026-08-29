<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\AccessibilityReportMailer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Feature 02 – Datenschutz des Meldewegs am tatsächlichen Mailer-Verhalten.
 * AK-49 (E-Mail optional), AK-56 (nur ins Postfach), AK-57 (kein PII im Log).
 */
final class AccessibilityReportMailerTest extends TestCase
{
    private function translator(): TranslatorInterface
    {
        $t = $this->createStub(TranslatorInterface::class);
        $t->method('trans')->willReturn('Neue Barriere-Meldung');

        return $t;
    }

    public function testSendDeliversOnlyToContactAddressWithReplyTo(): void // AK-56
    {
        $captured = null;
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send')
            ->willReturnCallback(function (TemplatedEmail $email) use (&$captured): void {
                $captured = $email;
            });
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $svc = new AccessibilityReportMailer($mailer, $this->translator(), $logger, 'kontakt@example.lu');
        $ok = $svc->send('Der Kontrast ist zu niedrig.', 'melder@example.com');

        self::assertTrue($ok);
        self::assertNotNull($captured);
        // Genau ein Empfänger: die feste Kontaktadresse — kein weiterer.
        self::assertCount(1, $captured->getTo());
        self::assertSame('kontakt@example.lu', $captured->getTo()[0]->getAddress());
        // Antwort geht an den Melder (nur wenn angegeben).
        self::assertCount(1, $captured->getReplyTo());
        self::assertSame('melder@example.com', $captured->getReplyTo()[0]->getAddress());
        // Der Meldetext reist ausschließlich im Mail-Context.
        self::assertSame('Der Kontrast ist zu niedrig.', $captured->getContext()['description']);
    }

    public function testSendWithoutEmailHasNoReplyTo(): void // AK-49
    {
        $captured = null;
        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')->willReturnCallback(function (TemplatedEmail $e) use (&$captured): void {
            $captured = $e;
        });

        $svc = new AccessibilityReportMailer($mailer, $this->translator(), $this->createStub(LoggerInterface::class), 'kontakt@example.lu');
        self::assertTrue($svc->send('Barriere ohne Rückmeldewunsch.', null));
        self::assertCount(0, $captured->getReplyTo());
    }

    public function testTransportFailureIsLoggedWithoutPii(): void // AK-57, EC-04 (Mailer-Transportfehler)
    {
        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')->willThrowException(new TransportException('SMTP weg'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')
            ->willReturnCallback(function (string $message, array $context): void {
                $flat = $message.' '.json_encode($context);
                self::assertStringNotContainsString('GEHEIME-BARRIERE', $flat, 'Meldetext darf nicht ins Log');
                self::assertStringNotContainsString('melder@example.com', $flat, 'Melder-Adresse darf nicht ins Log');
                self::assertArrayHasKey('exception_class', $context);
                self::assertArrayHasKey('code', $context);
            });

        $svc = new AccessibilityReportMailer($mailer, $this->translator(), $logger, 'kontakt@example.lu');
        self::assertFalse($svc->send('GEHEIME-BARRIERE Beschreibung', 'melder@example.com'));
    }

    public function testMessengerTransportFailureIsAlsoCaught(): void // BF-73 / EC-04
    {
        // Async-Versand über Messenger: scheitert der Queue-Dispatch (Transport nicht
        // erreichbar), kommt eine Messenger-TransportException — sie muss ebenso zu
        // einer freundlichen Meldung führen (false), nicht zu einem 500.
        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')->willThrowException(
            new \Symfony\Component\Messenger\Exception\TransportException('Queue weg'),
        );
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $svc = new AccessibilityReportMailer($mailer, $this->translator(), $logger, 'kontakt@example.lu');
        self::assertFalse($svc->send('Barriere', 'melder@example.com'));
    }
}
