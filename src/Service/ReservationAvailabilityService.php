<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\ReservationType;
use App\Repository\ReservationRepository;

/**
 * Service for checking reservation availability and capacity.
 */
class ReservationAvailabilityService
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly TimeSlotService $timeSlotService,
    ) {
    }

    /**
     * Get available time slots for a specific date and reservation type,
     * filtered by capacity.
     *
     * @param int $partySize The requested party size
     *
     * @return array<string> Available time slots
     */
    public function getAvailableSlots(
        \DateTimeInterface $date,
        ReservationType $type,
        int $partySize,
    ): array {
        $allSlots = $this->timeSlotService->getAvailableTimeSlots($date, $type);

        $availableSlots = [];

        foreach ($allSlots as $timeSlot) {
            if ($this->isSlotAvailable($date, $timeSlot, $type, $partySize)) {
                $availableSlots[] = $timeSlot;
            }
        }

        return $availableSlots;
    }

    /**
     * Check if a specific time slot is available for the given party size.
     */
    public function isSlotAvailable(
        \DateTimeInterface $date,
        string $timeSlot,
        ReservationType $type,
        int $partySize,
    ): bool {
        if (ReservationType::PrivateDining === $type) {
            return $this->isPrivateDiningSlotAvailable($date, $timeSlot);
        }

        return $this->isRegularDiningSlotAvailable($date, $timeSlot, $partySize);
    }

    /**
     * Check if a private dining slot is available (max 1 reservation per slot).
     */
    private function isPrivateDiningSlotAvailable(
        \DateTimeInterface $date,
        string $timeSlot,
    ): bool {
        $existingReservations = $this->reservationRepository->countActiveReservationsForSlot(
            $date,
            $timeSlot,
            ReservationType::PrivateDining
        );

        $maxReservations = $this->timeSlotService->getMaxGuestsPerSlot(ReservationType::PrivateDining);

        return $existingReservations < $maxReservations;
    }

    /**
     * Check if a regular dining slot has enough capacity for the requested party size.
     */
    private function isRegularDiningSlotAvailable(
        \DateTimeInterface $date,
        string $timeSlot,
        int $partySize,
    ): bool {
        $currentGuestCount = $this->reservationRepository->getTotalGuestsForSlot(
            $date,
            $timeSlot,
            ReservationType::Regular
        );

        $maxCapacity = $this->timeSlotService->getMaxGuestsPerSlot(ReservationType::Regular);

        $remainingCapacity = $maxCapacity - $currentGuestCount;

        return $remainingCapacity >= $partySize;
    }

    /**
     * Get remaining capacity for a specific time slot.
     * Returns guest count for regular dining, reservation count for private dining.
     */
    public function getRemainingCapacity(
        \DateTimeInterface $date,
        string $timeSlot,
        ReservationType $type,
    ): int {
        if (ReservationType::PrivateDining === $type) {
            $existingReservations = $this->reservationRepository->countActiveReservationsForSlot(
                $date,
                $timeSlot,
                $type
            );
            $maxReservations = $this->timeSlotService->getMaxGuestsPerSlot($type);

            return $maxReservations - $existingReservations;
        }

        $currentGuestCount = $this->reservationRepository->getTotalGuestsForSlot($date, $timeSlot, $type);
        $maxCapacity = $this->timeSlotService->getMaxGuestsPerSlot($type);

        return $maxCapacity - $currentGuestCount;
    }

    /**
     * Check if a time slot is fully booked.
     */
    public function isSlotFullyBooked(
        \DateTimeInterface $date,
        string $timeSlot,
        ReservationType $type,
    ): bool {
        return 0 === $this->getRemainingCapacity($date, $timeSlot, $type);
    }

    /**
     * Get total expected guests for a specific date.
     */
    public function getTotalGuestsForDate(\DateTimeInterface $date): int
    {
        return $this->reservationRepository->getTotalGuestsForDate($date);
    }
}
