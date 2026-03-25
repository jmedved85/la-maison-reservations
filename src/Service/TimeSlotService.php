<?php

namespace App\Service;

use App\Entity\ReservationType;
use App\Entity\TimeSlot;
use App\Repository\TimeSlotRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Service for managing restaurant time slots and availability.
 * Fetches time slot data from the database.
 */
class TimeSlotService
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct(
        private readonly TimeSlotRepository $timeSlotRepository,
        ParameterBagInterface $params,
    ) {
        $this->config = $params->get('restaurant');
    }

    /**
     * Get all available time slots for a given date and reservation type.
     *
     * @return array<string> Array of time strings in H:i format (e.g., ['12:00', '12:30', ...])
     */
    public function getAvailableTimeSlots(\DateTimeInterface $date, ReservationType $type): array
    {
        $dayOfWeek = (int) $date->format('N'); // 1 = Monday, 7 = Sunday

        $slotType = ReservationType::PrivateDining === $type ? 'private_dining' : 'regular';

        // Check if this type is available on this day
        if (ReservationType::PrivateDining === $type) {
            if (!in_array($dayOfWeek, $this->config['private_dining']['available_days'], true)) {
                return [];
            }
        } else {
            if (!in_array($dayOfWeek, $this->config['regular_dining']['available_days'], true)) {
                return [];
            }
        }

        // Fetch from database
        $timeSlots = $this->timeSlotRepository->findActiveByType($slotType);

        return array_map(fn ($slot) => $slot->getTimeFormatted(), $timeSlots);
    }

    /**
     * Check if a date is within the allowed booking window.
     */
    public function isDateBookable(\DateTimeInterface $date): bool
    {
        $today = new \DateTimeImmutable('today');
        $maxDate = $today->modify("+{$this->config['max_booking_days']} days");

        return $date >= $today && $date <= $maxDate;
    }

    /**
     * Get the maximum party size for a reservation type.
     */
    public function getMaxPartySize(ReservationType $type): int
    {
        if (ReservationType::PrivateDining === $type) {
            return $this->config['private_dining']['max_party_size'];
        }

        return $this->config['regular_dining']['max_party_size'];
    }

    /**
     * Get the minimum party size for a reservation type.
     */
    public function getMinPartySize(ReservationType $type): int
    {
        if (ReservationType::PrivateDining === $type) {
            return $this->config['private_dining']['min_party_size'];
        }

        return $this->config['regular_dining']['min_party_size'];
    }

    /**
     * Get the maximum guest capacity for a time slot.
     */
    public function getMaxGuestsPerSlot(ReservationType $type): int
    {
        if (ReservationType::PrivateDining === $type) {
            // For private dining, we track by number of reservations, not guests
            return $this->config['private_dining']['max_reservations_per_slot'];
        }

        return $this->config['regular_dining']['max_guests_per_slot'];
    }

    /**
     * Get restaurant opening time.
     */
    public function getOpeningTime(): string
    {
        return $this->config['opening_time'];
    }

    /**
     * Get restaurant closing time.
     */
    public function getClosingTime(): string
    {
        return $this->config['closing_time'];
    }

    /**
     * Get last reservation time.
     */
    public function getLastReservationTime(): string
    {
        return $this->config['last_reservation_time'];
    }

    /**
     * Get maximum booking days in advance.
     */
    public function getMaxBookingDays(): int
    {
        return $this->config['max_booking_days'];
    }

    /**
     * Check if private dining is available on the given day.
     */
    public function isPrivateDiningAvailableOnDay(\DateTimeInterface $date): bool
    {
        $dayOfWeek = (int) $date->format('N');

        return in_array($dayOfWeek, $this->config['private_dining']['available_days'], true);
    }

    /**
     * Get all time slots for the restaurant (regular dining hours).
     *
     * @return array<string>
     */
    public function getAllTimeSlots(): array
    {
        $timeSlots = $this->timeSlotRepository->findActiveByType('regular');

        return array_map(fn ($slot) => $slot->getTimeFormatted(), $timeSlots);
    }

    /**
     * Get all time slots from database (both regular and private).
     *
     * @return array<TimeSlot>
     */
    public function getAllTimeSlotsFromDatabase(): array
    {
        return $this->timeSlotRepository->findBy(['isActive' => true], ['time' => 'ASC']);
    }

    /**
     * Get formatted operating hours string.
     */
    public function getOperatingHours(): string
    {
        return sprintf(
            '%s - %s',
            $this->config['opening_time'],
            $this->config['closing_time']
        );
    }
}
