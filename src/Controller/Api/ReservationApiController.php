<?php

namespace App\Controller\Api;

use App\Entity\ReservationType;
use App\Service\ReservationAvailabilityService;
use App\Service\TimeSlotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reservations', name: 'api_reservations_')]
class ReservationApiController extends AbstractController
{
    public function __construct(
        private readonly ReservationAvailabilityService $availabilityService,
        private readonly TimeSlotService $timeSlotService,
    ) {
    }

    /**
     * Get available time slots for a given date, reservation type, and party size.
     */
    #[Route('/available-slots', name: 'available_slots', methods: ['GET'])]
    public function getAvailableSlots(Request $request): JsonResponse
    {
        $dateStr = $request->query->get('date');
        $typeStr = $request->query->get('type');
        $partySize = (int) $request->query->get('partySize', 1);

        // Validate date
        if (!$dateStr) {
            return $this->json(['error' => 'Date is required'], 400);
        }

        try {
            $date = new \DateTimeImmutable($dateStr);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format'], 400);
        }

        // Validate reservation type
        if (!$typeStr) {
            return $this->json(['error' => 'Reservation type is required'], 400);
        }

        try {
            $type = ReservationType::from($typeStr);
        } catch (\ValueError $e) {
            return $this->json(['error' => 'Invalid reservation type'], 400);
        }

        // Check if private dining is available on this day
        if (ReservationType::PrivateDining === $type && !$this->timeSlotService->isPrivateDiningAvailableOnDay($date)) {
            return $this->json([
                'slots' => [],
                'message' => 'Private dining is only available on Fridays and Saturdays',
            ]);
        }

        // Validate party size
        $minPartySize = $this->timeSlotService->getMinPartySize($type);
        $maxPartySize = $this->timeSlotService->getMaxPartySize($type);

        if ($partySize < $minPartySize || $partySize > $maxPartySize) {
            return $this->json([
                'error' => sprintf(
                    'Party size must be between %d and %d for %s',
                    $minPartySize,
                    $maxPartySize,
                    $type->getLabel()
                ),
            ], 400);
        }

        // Get available slots
        $slots = $this->availabilityService->getAvailableSlots($date, $type, $partySize);

        // Format slots for dropdown
        $formattedSlots = array_map(function ($slot) {
            return [
                'value' => $slot,
                'label' => $slot,
            ];
        }, $slots);

        return $this->json([
            'slots' => $formattedSlots,
            'message' => empty($slots) ? 'No available slots for the selected criteria' : null,
        ]);
    }

    /**
     * Check if private dining is available on a specific date.
     */
    #[Route('/check-private-dining', name: 'check_private_dining', methods: ['GET'])]
    public function checkPrivateDining(Request $request): JsonResponse
    {
        $dateStr = $request->query->get('date');

        if (!$dateStr) {
            return $this->json(['error' => 'Date is required'], 400);
        }

        try {
            $date = new \DateTimeImmutable($dateStr);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format'], 400);
        }

        $isAvailable = $this->timeSlotService->isPrivateDiningAvailableOnDay($date);

        return $this->json([
            'available' => $isAvailable,
            'dayOfWeek' => $date->format('l'), // e.g., "Friday"
            'message' => $isAvailable
                ? 'Private dining is available on this date'
                : 'Private dining is only available on Fridays and Saturdays',
        ]);
    }
}
