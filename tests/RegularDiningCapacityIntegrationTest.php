<?php

namespace App\Tests;

use App\Entity\Reservation;
use App\Entity\ReservationType;
use App\Repository\ReservationRepository;
use App\Service\ReservationAvailabilityService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests for Regular Dining Capacity Management.
 *
 * Tests the core capacity checking logic with real reservations in the database.
 */
class RegularDiningCapacityIntegrationTest extends KernelTestCase
{
    private ReservationAvailabilityService $availabilityService;
    private ReservationRepository $reservationRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->availabilityService = static::getContainer()->get(ReservationAvailabilityService::class);
        $this->reservationRepository = static::getContainer()->get(ReservationRepository::class);
    }

    /**
     * TEST 1: Slot Becomes Unavailable When Remaining Capacity Is Insufficient.
     *
     * When existing reservations consume most of the capacity,
     * new reservations that exceed remaining capacity should be rejected.
     *
     * Scenario:
     * - Max capacity: 20 guests
     * - Existing: 8 + 10 = 18 guests
     * - Remaining: 2 guests
     * - Request for 5 guests: SHOULD BE REJECTED
     */
    public function testSlotBecomesUnavailableWhenCapacityInsufficient(): void
    {
        $date = new \DateTimeImmutable('2026-04-15'); // Tuesday (regular dining)
        $timeSlot = '19:00';
        $time = \DateTimeImmutable::createFromFormat('H:i', $timeSlot);

        // Create first reservation: 8 guests
        $reservation1 = new Reservation();
        $reservation1->onPrePersist();
        $reservation1->setFullName('John Doe');
        $reservation1->setEmail('john@example.com');
        $reservation1->setPhoneNumber('+1234567890');
        $reservation1->setReservationDate($date);
        $reservation1->setTimeSlot($time);
        $reservation1->setPartySize(8);
        $reservation1->setReservationType(ReservationType::Regular);

        $this->reservationRepository->save($reservation1);

        // Create second reservation: 10 guests
        $reservation2 = new Reservation();
        $reservation2->onPrePersist();
        $reservation2->setFullName('Jane Smith');
        $reservation2->setEmail('jane@example.com');
        $reservation2->setPhoneNumber('+1234567891');
        $reservation2->setReservationDate($date);
        $reservation2->setTimeSlot($time);
        $reservation2->setPartySize(10);
        $reservation2->setReservationType(ReservationType::Regular);

        $this->reservationRepository->save($reservation2);

        // Verify: Total of 18 guests from 2 reservations
        $totalGuests = $this->reservationRepository->getTotalGuestsForSlot(
            $date,
            $timeSlot,
            ReservationType::Regular
        );
        $this->assertEquals(18, $totalGuests, 'Should have 18 guests total from 2 reservations');

        // Check remaining capacity
        $remainingCapacity = $this->availabilityService->getRemainingCapacity(
            $date,
            $timeSlot,
            ReservationType::Regular
        );
        $this->assertEquals(2, $remainingCapacity, 'Should have only 2 seats remaining (20 - 18)');

        // Test: Try to book for 5 guests (exceeds remaining capacity of 2)
        $isAvailableFor5 = $this->availabilityService->isSlotAvailable(
            $date,
            $timeSlot,
            ReservationType::Regular,
            5 // Requesting 5 guests
        );

        $this->assertFalse(
            $isAvailableFor5,
            'Slot should NOT be available for 5 guests when only 2 seats remain'
        );

        // Verify slot is fully booked for requests exceeding capacity
        $isFullyBooked = $this->availabilityService->isSlotFullyBooked(
            $date,
            $timeSlot,
            ReservationType::Regular
        );

        $this->assertFalse(
            $isFullyBooked,
            'Slot should not be marked as fully booked yet (2 seats available)'
        );
    }

    /**
     * TEST 2: Slot Remains Available When Party Size Fits Remaining Capacity.
     *
     * When remaining capacity is sufficient for the requested party size,
     * the slot should be available.
     *
     * Scenario:
     * - Max capacity: 20 guests
     * - Existing: 8 + 10 = 18 guests
     * - Remaining: 2 guests
     * - Request for 2 guests: SHOULD BE ACCEPTED
     */
    public function testSlotRemainsAvailableWhenPartySizeFitsCapacity(): void
    {
        $date = new \DateTimeImmutable('2026-04-16'); // Wednesday
        $timeSlot = '18:30';
        $time = \DateTimeImmutable::createFromFormat('H:i', $timeSlot);

        // Create existing reservations totaling 18 guests
        $reservation1 = new Reservation();
        $reservation1->onPrePersist();
        $reservation1->setFullName('Alice Johnson');
        $reservation1->setEmail('alice@example.com');
        $reservation1->setPhoneNumber('+1234567892');
        $reservation1->setReservationDate($date);
        $reservation1->setTimeSlot($time);
        $reservation1->setPartySize(8);
        $reservation1->setReservationType(ReservationType::Regular);

        $this->reservationRepository->save($reservation1);

        $reservation2 = new Reservation();
        $reservation2->onPrePersist();
        $reservation2->setFullName('Bob Williams');
        $reservation2->setEmail('bob@example.com');
        $reservation2->setPhoneNumber('+1234567893');
        $reservation2->setReservationDate($date);
        $reservation2->setTimeSlot($time);
        $reservation2->setPartySize(10);
        $reservation2->setReservationType(ReservationType::Regular);

        $this->reservationRepository->save($reservation2);

        // Verify remaining capacity
        $remainingCapacity = $this->availabilityService->getRemainingCapacity(
            $date,
            $timeSlot,
            ReservationType::Regular
        );
        $this->assertEquals(2, $remainingCapacity, 'Should have 2 seats remaining');

        // Test: Try to book for 2 guests (exactly fits remaining capacity)
        $isAvailableFor2 = $this->availabilityService->isSlotAvailable(
            $date,
            $timeSlot,
            ReservationType::Regular,
            2
        );

        $this->assertTrue(
            $isAvailableFor2,
            'Slot SHOULD be available for 2 guests when 2 seats remain'
        );

        // Also test for 1 guest (under capacity)
        $isAvailableFor1 = $this->availabilityService->isSlotAvailable(
            $date,
            $timeSlot,
            ReservationType::Regular,
            1
        );

        $this->assertTrue(
            $isAvailableFor1,
            'Slot SHOULD be available for 1 guest when 2 seats remain'
        );
    }

    /**
     * TEST 3: Slot Is Fully Booked When Capacity Reached Exactly.
     *
     * When total guests equals max capacity (20),
     * the slot should be marked as fully booked.
     */
    public function testSlotFullyBookedWhenCapacityReachedExactly(): void
    {
        $date = new \DateTimeImmutable('2026-04-17'); // Thursday
        $timeSlot = '20:00';
        $time = \DateTimeImmutable::createFromFormat('H:i', $timeSlot);

        // Create reservations totaling exactly 20 guests
        $reservation1 = new Reservation();
        $reservation1->onPrePersist();
        $reservation1->setFullName('Charlie Brown');
        $reservation1->setEmail('charlie@example.com');
        $reservation1->setPhoneNumber('+1234567894');
        $reservation1->setReservationDate($date);
        $reservation1->setTimeSlot($time);
        $reservation1->setPartySize(10);
        $reservation1->setReservationType(ReservationType::Regular);

        $this->reservationRepository->save($reservation1);

        $reservation2 = new Reservation();
        $reservation2->onPrePersist();
        $reservation2->setFullName('Diana Prince');
        $reservation2->setEmail('diana@example.com');
        $reservation2->setPhoneNumber('+1234567895');
        $reservation2->setReservationDate($date);
        $reservation2->setTimeSlot($time);
        $reservation2->setPartySize(6);
        $reservation2->setReservationType(ReservationType::Regular);

        $this->reservationRepository->save($reservation2);

        $reservation3 = new Reservation();
        $reservation3->onPrePersist();
        $reservation3->setFullName('Edward Norton');
        $reservation3->setEmail('edward@example.com');
        $reservation3->setPhoneNumber('+1234567896');
        $reservation3->setReservationDate($date);
        $reservation3->setTimeSlot($time);
        $reservation3->setPartySize(4);
        $reservation3->setReservationType(ReservationType::Regular);

        $this->reservationRepository->save($reservation3);

        // Verify total capacity used
        $totalGuests = $this->reservationRepository->getTotalGuestsForSlot(
            $date,
            $timeSlot,
            ReservationType::Regular
        );
        $this->assertEquals(20, $totalGuests, 'Should have exactly 20 guests (10+6+4)');

        // Check remaining capacity
        $remainingCapacity = $this->availabilityService->getRemainingCapacity(
            $date,
            $timeSlot,
            ReservationType::Regular
        );
        $this->assertEquals(0, $remainingCapacity, 'Should have 0 seats remaining');

        // Verify slot is fully booked
        $isFullyBooked = $this->availabilityService->isSlotFullyBooked(
            $date,
            $timeSlot,
            ReservationType::Regular
        );

        $this->assertTrue($isFullyBooked, 'Slot should be marked as fully booked');

        // Try to book 1 more guest - should be rejected
        $isAvailable = $this->availabilityService->isSlotAvailable(
            $date,
            $timeSlot,
            ReservationType::Regular,
            1
        );

        $this->assertFalse(
            $isAvailable,
            'Slot should NOT be available even for 1 guest when fully booked'
        );
    }

    /**
     * TEST 4: Multiple Small Reservations Fill Capacity Gradually.
     *
     * Simulates realistic scenario with multiple small parties
     * booking the same time slot until capacity is reached.
     */
    public function testMultipleSmallReservationsFillCapacity(): void
    {
        $date = new \DateTimeImmutable('2026-04-18'); // Friday
        $timeSlot = '19:30';
        $time = \DateTimeImmutable::createFromFormat('H:i', $timeSlot);

        // Create 4 small reservations: 2+2+3+4 = 11 guests
        $partySizes = [2, 2, 3, 4];

        foreach ($partySizes as $index => $partySize) {
            $reservation = new Reservation();
            $reservation->onPrePersist();
            $reservation->setFullName("Guest {$index}");
            $reservation->setEmail("guest{$index}@example.com");
            $reservation->setPhoneNumber("+123456789{$index}");
            $reservation->setReservationDate($date);
            $reservation->setTimeSlot($time);
            $reservation->setPartySize($partySize);
            $reservation->setReservationType(ReservationType::Regular);

            $this->reservationRepository->save($reservation);
        }

        // Verify total
        $totalGuests = $this->reservationRepository->getTotalGuestsForSlot(
            $date,
            $timeSlot,
            ReservationType::Regular
        );
        $this->assertEquals(11, $totalGuests, 'Should have 11 guests from 4 reservations');

        // Check remaining capacity: 20 - 11 = 9
        $remainingCapacity = $this->availabilityService->getRemainingCapacity(
            $date,
            $timeSlot,
            ReservationType::Regular
        );
        $this->assertEquals(9, $remainingCapacity, 'Should have 9 seats remaining');

        // Slot should still be available for party of 9 or less
        $this->assertTrue(
            $this->availabilityService->isSlotAvailable($date, $timeSlot, ReservationType::Regular, 9),
            'Should accept party of 9'
        );

        $this->assertTrue(
            $this->availabilityService->isSlotAvailable($date, $timeSlot, ReservationType::Regular, 5),
            'Should accept party of 5'
        );

        // But not for party of 10 or more
        $this->assertFalse(
            $this->availabilityService->isSlotAvailable($date, $timeSlot, ReservationType::Regular, 10),
            'Should reject party of 10 (exceeds remaining capacity)'
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up - remove test reservations
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->createQuery('DELETE FROM App\Entity\Reservation')->execute();
    }
}
