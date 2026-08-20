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

    public function isConfirmed(): bool;

    public function confirm(): static;

    public function getLocale(): string;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getConfirmedAt(): ?\DateTimeImmutable;

    public function setConfirmedAt(?\DateTimeImmutable $confirmedAt): static;
}
