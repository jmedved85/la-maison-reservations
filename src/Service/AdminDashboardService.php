<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\ReservationStatus;
use App\Entity\ReservationType;
use App\Repository\ReservationRepository;

class AdminDashboardService
{
    public function __construct(
        private readonly ReservationAvailabilityService $availabilityService,
        private readonly TimeSlotService $timeSlotService,
        private readonly ReservationRepository $reservationRepository,
    ) {
    }

    /**
     * Calculate statistics for the admin dashboard.
     *
     * @param array<Reservation> $reservations
     *
     * @return array<string, int>
     */
    public function calculateStatistics(array $reservations): array
    {
        $totalGuests = 0;
        $pendingCount = 0;
        $confirmedCount = 0;

        foreach ($reservations as $reservation) {
            // Count guests (exclude cancelled reservations)
            if (ReservationStatus::Cancelled !== $reservation->getStatus()) {
                $totalGuests += $reservation->getPartySize();
            }

            // Count by status
            if (ReservationStatus::Pending === $reservation->getStatus()) {
                ++$pendingCount;
            } elseif (ReservationStatus::Confirmed === $reservation->getStatus()) {
                ++$confirmedCount;
            }
        }

        return [
            'totalReservations' => count($reservations),
            'expectedGuests' => $totalGuests,
            'pendingCount' => $pendingCount,
            'confirmedCount' => $confirmedCount,
        ];
    }

    /**
     * Get slot statistics for admin dashboard.
     *
     * @return array{timeSlot: string, totalGuests: int, remainingCapacity: int, isFullyBooked: bool, reservationCount: int}[]
     */
    public function getSlotStatistics(\DateTimeInterface $date, ReservationType $type): array
    {
        $slots = $this->timeSlotService->getAvailableTimeSlots($date, $type);
        $statistics = [];

        foreach ($slots as $timeSlot) {
            $totalGuests = $this->reservationRepository->getTotalGuestsForSlot($date, $timeSlot, $type);
            $remainingCapacity = $this->availabilityService->getRemainingCapacity($date, $timeSlot, $type);
            $reservationCount = $this->reservationRepository->countActiveReservationsForSlot($date, $timeSlot, $type);

            $statistics[] = [
                'timeSlot' => $timeSlot,
                'totalGuests' => $totalGuests,
                'remainingCapacity' => $remainingCapacity,
                'isFullyBooked' => $this->availabilityService->isSlotFullyBooked($date, $timeSlot, $type),
                'reservationCount' => $reservationCount,
            ];
        }

        return $statistics;
    }

    /**
     * Get array of fully booked time slots (20/20 guests for regular dining).
     *
     * @return array<string> Array of strings in format 'Y-m-d|H:i'
     */
    public function getFullyBookedSlots(\DateTimeInterface $date): array
    {
        $fullyBooked = [];

        $allSlots = $this->timeSlotService->getAvailableTimeSlots($date, ReservationType::Regular);

        // Check if slot is fully booked (0 remaining capacity)
        foreach ($allSlots as $timeSlot) {
            if ($this->availabilityService->isSlotFullyBooked($date, $timeSlot, ReservationType::Regular)) {
                $fullyBooked[] = $date->format('Y-m-d') . '|' . $timeSlot;
            }
        }

        return $fullyBooked;
    }

    /**
     * Build pagination metadata array.
     *
     * @return array<string, mixed>
     */
    public function buildPaginationData(int $page, int $totalItems, int $itemsPerPage): array
    {
        $totalPages = (int) ceil($totalItems / $itemsPerPage);
        $offset = ($page - 1) * $itemsPerPage;

        return [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'itemsPerPage' => $itemsPerPage,
            'hasNextPage' => $page < $totalPages,
            'hasPreviousPage' => $page > 1,
            'startItem' => $totalItems > 0 ? $offset + 1 : 0,
            'endItem' => min($offset + $itemsPerPage, $totalItems),
        ];
    }
}
