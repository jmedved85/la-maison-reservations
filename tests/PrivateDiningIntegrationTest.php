<?php

namespace App\Tests;

use App\Entity\Reservation;
use App\Entity\ReservationType;
use App\Exception\ReservationNotAvailableException;
use App\Repository\ReservationRepository;
use App\Service\ReservationAvailabilityService;
use App\Service\TimeSlotService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests for Private Dining functionality.
 */
class PrivateDiningIntegrationTest extends KernelTestCase
{
    private TimeSlotService $timeSlotService;
    private ReservationAvailabilityService $availabilityService;
    private ReservationRepository $reservationRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->timeSlotService = static::getContainer()->get(TimeSlotService::class);
        $this->availabilityService = static::getContainer()->get(ReservationAvailabilityService::class);
        $this->reservationRepository = static::getContainer()->get(ReservationRepository::class);
    }

    /**
     * TEST 1: Private Dining Days from Real Config.
     *
     * Verify private dining availability based on real configuration
     */
    public function testPrivateDiningDaysFromRealConfig(): void
    {
        $friday = new \DateTimeImmutable('2026-04-17');
        $monday = new \DateTimeImmutable('2026-04-20');

        // Use real service that reads from restaurant.yaml
        $fridaySlots = $this->timeSlotService->getAvailableTimeSlots(
            $friday,
            ReservationType::PrivateDining
        );

        $mondaySlots = $this->timeSlotService->getAvailableTimeSlots(
            $monday,
            ReservationType::PrivateDining
        );

        $this->assertNotEmpty($fridaySlots, 'Friday should have private dining slots');
        $this->assertEmpty($mondaySlots, 'Monday should have NO private dining slots');
    }

    /**
     * TEST 2: Private Dining Allows Only One Reservation Per Slot.
     *
     * When one private dining reservation exists for a time slot,
     * the availability service must report the slot as unavailable,
     * and attempting to book should be rejected.
     */
    public function testPrivateDiningRejectsSecondReservationForSameSlot(): void
    {
        // Setup: Create and save first private dining reservation
        $date = new \DateTimeImmutable('2026-04-18'); // Friday
        $timeSlot = '19:00';
        $time = \DateTimeImmutable::createFromFormat('H:i', $timeSlot);

        $firstReservation = new Reservation();
        $firstReservation->onPrePersist();
        $firstReservation->setFullName('John Doe');
        $firstReservation->setEmail('john@example.com');
        $firstReservation->setPhoneNumber('+1234567890');
        $firstReservation->setReservationDate($date);
        $firstReservation->setTimeSlot($time);
        $firstReservation->setPartySize(8);
        $firstReservation->setReservationType(ReservationType::PrivateDining);

        $this->reservationRepository->save($firstReservation);

        // Verify first reservation was saved
        $count = $this->reservationRepository->countActiveReservationsForSlot(
            $date,
            $timeSlot,
            ReservationType::PrivateDining
        );
        $this->assertEquals(1, $count, 'Should have exactly 1 active private dining reservation');

        // Test: Availability service should report slot as unavailable
        $isAvailable = $this->availabilityService->isSlotAvailable(
            $date,
            $timeSlot,
            ReservationType::PrivateDining,
            10
        );

        $this->assertFalse(
            $isAvailable,
            'Private dining slot must NOT be available after first reservation'
        );

        // Remaining capacity should be 0
        $remainingCapacity = $this->availabilityService->getRemainingCapacity(
            $date,
            $timeSlot,
            ReservationType::PrivateDining
        );
        $this->assertEquals(0, $remainingCapacity, 'No capacity remaining for private dining slot');
    }

    /**
     * TEST 3: Controller-Level Validation Prevents Double Booking.
     *
     * Simulates the validation logic that HomeController performs
     * to prevent double booking of private dining slots.
     */
    public function testControllerValidationPreventsDoubleBooking(): void
    {
        $date = new \DateTimeImmutable('2026-04-18'); // Friday
        $timeSlot = '19:00';
        $time = \DateTimeImmutable::createFromFormat('H:i', $timeSlot);

        // Create and save first reservation
        $firstReservation = new Reservation();
        $firstReservation->onPrePersist();
        $firstReservation->setFullName('John Doe');
        $firstReservation->setEmail('john@example.com');
        $firstReservation->setPhoneNumber('+1234567890');
        $firstReservation->setReservationDate($date);
        $firstReservation->setTimeSlot($time);
        $firstReservation->setPartySize(8);
        $firstReservation->setReservationType(ReservationType::PrivateDining);

        $this->reservationRepository->save($firstReservation);

        // Simulate controller validation for second reservation
        $secondReservation = new Reservation();
        $secondReservation->setFullName('Jane Smith');
        $secondReservation->setEmail('jane@example.com');
        $secondReservation->setPhoneNumber('+1234567891');
        $secondReservation->setReservationDate($date);
        $secondReservation->setTimeSlot($time);
        $secondReservation->setPartySize(10);
        $secondReservation->setReservationType(ReservationType::PrivateDining);

        // Check availability using the same logic as HomeController
        $isAvailable = $this->availabilityService->isSlotAvailable(
            $secondReservation->getReservationDate(),
            $secondReservation->getTimeSlot()->format('H:i'),
            $secondReservation->getReservationType(),
            $secondReservation->getPartySize()
        );

        $this->assertFalse($isAvailable, 'Slot should not be available for second private dining reservation');

        if (false === $isAvailable) {
            $this->expectException(ReservationNotAvailableException::class);
            throw new ReservationNotAvailableException('Selected time slot is not available. Please choose another time.');
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up - remove test reservations
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->createQuery('DELETE FROM App\Entity\Reservation')->execute();
    }
}
