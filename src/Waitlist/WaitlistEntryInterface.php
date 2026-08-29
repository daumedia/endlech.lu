<?php

namespace App\Waitlist;

use App\Enum\WaitlistStatus;

/**
 * Gemeinsamer Vertrag der beiden Wartelisten-Typen (Restaurants und
 * Organisationen).
 *
 * Zweck ist nicht Vererbung um ihrer selbst willen: Der
 * WaitlistConfirmationService und die kombinierte Admin-Liste arbeiten gegen
 * genau diese Methoden, ohne die konkreten Entities zu kennen. Alles
 * Typspezifische bleibt bewusst außen vor.
 */
interface WaitlistEntryInterface
{
    public function getId(): ?int;

    /** Anzeigename in Listen und Betreffzeilen (Restaurant- bzw. Organisationsname). */
    public function getDisplayName(): string;

    public function getContactName(): string;

    public function getEmail(): string;

    public function getStatus(): WaitlistStatus;

    public function setStatus(WaitlistStatus $status): static;

    public function getConfirmationToken(): ?string;

    public function generateConfirmationToken(): string;

    /**
     * ⚠ **Wahr auch nach einem Verwaltungs-Statuswechsel.** Wer prüfen muss, ob
     * die Adresse *belegt* ist, nimmt `hasSelfConfirmed()` — siehe dort.
     */
    public function isConfirmed(): bool;

    /**
     * Hat der Interessent **selbst** bestätigt (Double-Opt-In eingelöst)?
     *
     * ⚠ BF-89: `isConfirmed()` beantwortet das **nicht**. `confirmedAt` wird
     * auch gesetzt, wenn ein Admin einen nie bestätigten Eintrag von Hand
     * weitersetzt. Alles, was eine belegte Adresse voraussetzt — die
     * Übertragung nach Brevo (AK-05) allen voran — fragt hier.
     */
    public function hasSelfConfirmed(): bool;

    public function getSelfConfirmedAt(): ?\DateTimeImmutable;

    public function confirm(): static;

    public function getLocale(): string;

    /**
     * Zeitpunkt der Werbe-Einwilligung; `null` heißt: keine (Feature 04).
     *
     * Steht hier und nicht bei den typspezifischen Feldern, weil der
     * WaitlistConfirmationService gegen dieses Interface arbeitet und beim
     * Bestätigen entscheiden muss, ob ein Marketing-Kontakt entsteht.
     */
    public function getMarketingConsentAt(): ?\DateTimeImmutable;

    public function setMarketingConsentAt(?\DateTimeImmutable $marketingConsentAt): static;

    public function hasMarketingConsent(): bool;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getConfirmedAt(): ?\DateTimeImmutable;

    public function setConfirmedAt(?\DateTimeImmutable $confirmedAt): static;
}
