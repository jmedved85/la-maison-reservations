<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\ReservationStatus;
use App\Form\ReservationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationFormType::class, $reservation);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation->setStatus(ReservationStatus::Pending);

            $this->entityManager->persist($reservation);
            $this->entityManager->flush();

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
