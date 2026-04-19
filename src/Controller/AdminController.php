<?php

namespace App\Controller;

use App\Entity\ReservationStatus;
use App\Entity\ReservationType;
use App\Repository\ReservationRepository;
use App\Service\AdminDashboardService;
use App\Service\ReservationAvailabilityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly AdminDashboardService $dashboardService,
        private readonly ReservationAvailabilityService $availabilityService,
    ) {
    }

    #[Route('/admin', name: 'app_admin')]
    public function index(Request $request): Response
    {
        // Parse filters from request
        $filters = \AdminReservationFilters::fromRequest($request);

        $allReservations = $this->reservationRepository->findForAdminList($filters->date, $filters->status, $filters->sortOrder);

        $totalReservations = count($allReservations);

        $reservations = array_slice($allReservations, $filters->getOffset(), $filters->limit);

        $stats = $this->dashboardService->calculateStatistics($allReservations);

        $fullyBookedSlots = [];
        $slotStatistics = [];

        if (null !== $filters->date) {
            $fullyBookedSlots = $this->dashboardService->getFullyBookedSlots($filters->date);
            $slotStatistics = $this->availabilityService->getSlotStatistics($filters->date, ReservationType::Regular);
        }

        $pagination = $this->dashboardService->buildPaginationData($filters->page, $totalReservations, $filters->limit);

        return $this->render('admin/index.html.twig', [
            'reservations' => $reservations,
            'filterDate' => $filters->date,
            'filterStatus' => $filters->status,
            'sortOrder' => $filters->sortOrder,
            'stats' => $stats,
            'fullyBookedSlots' => $fullyBookedSlots,
            'slotStatistics' => $slotStatistics,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/admin/reservation/{id}', name: 'app_admin_reservation_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $reservation = $this->reservationRepository->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Reservation not found');
        }

        return $this->render('admin/show.html.twig', [
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

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reservation_status_' . $id, $token)) {
            $this->addFlash('error', 'Invalid CSRF token');

            return $this->redirectToRoute('app_admin_reservation_show', ['id' => $id]);
        }

        $newStatusValue = strtolower($request->request->get('status'));

        try {
            $newStatus = ReservationStatus::from($newStatusValue);
        } catch (\ValueError $e) {
            $this->addFlash('danger', 'Invalid status value');

            return $this->redirectToRoute('app_admin_reservation_show', ['id' => $id]);
        }

        $reservation->setStatus($newStatus);
        $this->reservationRepository->save($reservation);

        $this->addFlash('success', 'Reservation status updated successfully');

        return $this->redirectToRoute('app_admin_reservation_show', ['id' => $id]);
    }
}
