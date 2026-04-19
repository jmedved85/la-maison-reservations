<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Exception\ReservationNotAvailableException;
use App\Form\ReservationFormType;
use App\Repository\ReservationRepository;
use App\Service\ReservationAvailabilityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly ReservationAvailabilityService $availabilityService,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request, ReservationRepository $reservationRepository): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationFormType::class, $reservation);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check slot availability
            $timeSlot = $reservation->getTimeSlot()->format('H:i');
            $isAvailable = $this->availabilityService->isSlotAvailable(
                $reservation->getReservationDate(),
                $timeSlot,
                $reservation->getReservationType(),
                $reservation->getPartySize()
            );

            if (!$isAvailable) {
                throw new ReservationNotAvailableException('Selected time slot is not available. Please choose another time.');
            }

            $reservationRepository->save($reservation);

            // Store reservation data in session for modal display
            $this->storeInSession($request, $reservation);

            return $this->redirectToRoute('app_home');
        }

        // Get reservation data from session if exists (for modal display)
        $reservationData = null;
        if ($request->getSession()->has('reservation_confirmed')) {
            $reservationData = $request->getSession()->get('reservation_data');

            // Clear session data after reading
            $this->clearSession($request);
        }

        return $this->render('home/index.html.twig', [
            'reservationForm' => $form->createView(),
            'reservationData' => $reservationData,
        ]);
    }

    private function storeInSession(Request $request, Reservation $reservation): void
    {
        $request->getSession()->set('reservation_confirmed', true);
        $request->getSession()->set('reservation_data', [
            'referenceCode' => $reservation->getReferenceCode(),
            'fullName' => $reservation->getFullName(),
            'email' => $reservation->getEmail(),
            'phoneNumber' => $reservation->getPhoneNumber(),
            'partySize' => $reservation->getPartySize(),
            'reservationDate' => $reservation->getReservationDate(),
            'timeSlot' => $reservation->getTimeSlot(),
            'reservationType' => $reservation->getReservationType()->value,
            'reservationTypeLabel' => $reservation->getReservationTypeLabel(),
            'isPrivateDining' => $reservation->isPrivateDining(),
            'statusValue' => $reservation->getStatus()->value,
            'statusLabel' => $reservation->getStatusLabel(),
            'statusBadgeClass' => $reservation->getStatus()->getBadgeClass(),
            'specialRequests' => $reservation->getSpecialRequests(),
        ]);
    }

    private function clearSession(Request $request): void
    {
        $request->getSession()->remove('reservation_confirmed');
        $request->getSession()->remove('reservation_data');
    }
}
