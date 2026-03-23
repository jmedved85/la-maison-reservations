<?php

namespace App\Controller;

use App\Entity\ReservationStatus;
use App\Entity\ReservationType;
use App\Repository\ReservationRepository;
use App\Service\ReservationAvailabilityService;
use App\Service\TimeSlotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly ReservationAvailabilityService $availabilityService,
        private readonly TimeSlotService $timeSlotService,
    ) {
    }

    #[Route('/admin', name: 'app_admin')]
    public function index(Request $request): Response
    {
        // Get filter parameters
        $filterDate = $request->query->get('date');
        $filterStatus = $request->query->get('status');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 15; // Items per page

        // Parse filter date or default to today
        try {
            $date = $filterDate
                ? new \DateTimeImmutable($filterDate)
                : new \DateTimeImmutable('today');
        } catch (\Exception $e) {
            $date = new \DateTimeImmutable('today');
        }

        // Parse filter status
        $status = null;
        if ($filterStatus && '' !== $filterStatus) {
            try {
                $status = ReservationStatus::from($filterStatus);
            } catch (\ValueError $e) {
                $status = null;
            }
        }

        // Get reservations for the filtered date and status
        $allReservations = $this->reservationRepository->findByDate($date, $status);

        // Sort by time slot (ascending)
        usort($allReservations, function ($a, $b) {
            return $a->getTimeSlot() <=> $b->getTimeSlot();
        });

        // Calculate pagination
        $totalReservations = count($allReservations);
        $totalPages = (int) ceil($totalReservations / $limit);
        $offset = ($page - 1) * $limit;
        $reservations = array_slice($allReservations, $offset, $limit);

        // Calculate statistics for the filtered date
        $stats = $this->calculateStatistics($date, $allReservations);

        // Get fully booked slots for visual marking
        $fullyBookedSlots = $this->getFullyBookedSlots($date);

        return $this->render('admin/index.html.twig', [
            'reservations' => $reservations,
            'filterDate' => $date,
            'filterStatus' => $filterStatus,
            'stats' => $stats,
            'fullyBookedSlots' => $fullyBookedSlots,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalReservations,
                'itemsPerPage' => $limit,
                'hasNextPage' => $page < $totalPages,
                'hasPreviousPage' => $page > 1,
                'startItem' => $totalReservations > 0 ? $offset + 1 : 0,
                'endItem' => min($offset + $limit, $totalReservations),
            ],
        ]);
    }

    #[Route('/admin/reservation/{id}', name: 'app_admin_reservation_detail', requirements: ['id' => '\d+'])]
    public function detail(int $id): Response
    {
        $reservation = $this->reservationRepository->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Reservation not found');
        }

        return $this->render('admin/detail.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/admin/reservation/{id}/status', name: 'app_admin_reservation_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateStatus(Request $request, int $id): Response
    {
        $reservation = $this->reservationRepository->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Reservation not found');
        }

        // Verify CSRF token
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reservation_status_' . $id, $token)) {
            $this->addFlash('error', 'Invalid CSRF token');

            return $this->redirectToRoute('app_admin_reservation_detail', ['id' => $id]);
        }

        // Get new status
        $newStatusValue = strtolower($request->request->get('status'));
        try {
            $newStatus = ReservationStatus::from($newStatusValue);
        } catch (\ValueError $e) {
            $this->addFlash('danger', 'Invalid status value');

            return $this->redirectToRoute('app_admin_reservation_detail', ['id' => $id]);
        }

        // Update status
        $reservation->setStatus($newStatus);
        $this->reservationRepository->save($reservation);

        $this->addFlash('success', 'Reservation status updated successfully');

        return $this->redirectToRoute('app_admin_reservation_detail', ['id' => $id]);
    }

    /**
     * Calculate statistics for the admin dashboard.
     *
     * @param array<\App\Entity\Reservation> $reservations
     *
     * @return array<string, int>
     */
    private function calculateStatistics(\DateTimeInterface $date, array $reservations): array
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

        // Count fully booked slots
        $fullyBookedSlots = $this->getFullyBookedSlots($date);

        return [
            'totalReservations' => count($reservations),
            'expectedGuests' => $totalGuests,
            'pendingCount' => $pendingCount,
            'confirmedCount' => $confirmedCount,
            'fullyBookedSlotsCount' => count($fullyBookedSlots),
        ];
    }

    /**
     * Get array of fully booked time slots (20/20 guests for regular dining).
     *
     * @return array<string> Array of time strings in H:i format
     */
    private function getFullyBookedSlots(\DateTimeInterface $date): array
    {
        $fullyBooked = [];

        // Get all regular time slots for the day
        $allSlots = $this->timeSlotService->getAvailableTimeSlots($date, ReservationType::Regular);

        foreach ($allSlots as $timeSlot) {
            // Check if slot is fully booked (0 remaining capacity)
            if ($this->availabilityService->isSlotFullyBooked($date, $timeSlot, ReservationType::Regular)) {
                $fullyBooked[] = $timeSlot;
            }
        }

        return $fullyBooked;
    }
}
