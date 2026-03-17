<?php

namespace App\Entity;

enum ReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    /**
     * Get human-readable label for the status.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
        };
    }

    /**
     * Get CSS class/badge color for UI display.
     */
    public function getBadgeClass(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'success',
            self::Cancelled => 'danger',
            self::Completed => 'secondary',
        };
    }

    /**
     * Get all available statuses as associative array (value => label)
     * Useful for form dropdowns.
     *
     * @return array<string, string>
     */
    public static function getChoices(): array
    {
        $choices = [];
        foreach (self::cases() as $status) {
            $choices[$status->getLabel()] = $status->value;
        }

        return $choices;
    }

    /**
     * Get all cases as array for validation.
     *
     * @return array<string>
     */
    public static function getValues(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
