<?php

namespace App\Entity;

enum ReservationType: string
{
    case Regular = 'regular';
    case PrivateDining = 'private_dining';

    /**
     * Get human-readable label for the type.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Regular => 'Regular Dining',
            self::PrivateDining => 'Private Dining',
        };
    }

    /**
     * Get minimum party size for this reservation type.
     */
    public function getMinPartySize(): int
    {
        return match ($this) {
            self::Regular => 1,
            self::PrivateDining => 6,
        };
    }

    /**
     * Get maximum party size for this reservation type.
     */
    public function getMaxPartySize(): int
    {
        return match ($this) {
            self::Regular => 10,
            self::PrivateDining => 12,
        };
    }

    /**
     * Get maximum capacity per time slot.
     */
    public function getMaxCapacity(): int
    {
        return match ($this) {
            self::Regular => 20,
            self::PrivateDining => 1, // Only one private dining reservation per slot
        };
    }

    /**
     * Get all available types as associative array (value => label)
     * Useful for form dropdowns.
     *
     * @return array<string, string>
     */
    public static function getChoices(): array
    {
        $choices = [];
        foreach (self::cases() as $type) {
            $choices[$type->getLabel()] = $type->value;
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
