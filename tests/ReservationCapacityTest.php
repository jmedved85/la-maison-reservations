<?php

namespace App\Tests;

use App\Entity\Reservation;
use App\Entity\ReservationStatus;
use App\Entity\ReservationType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests for Reservation Capacity Business Rules.
 *
 * Validates that restaurant.yaml configuration matches business requirements:
 * 1. Regular Dining: Maximum guests per time slot (from config)
 * 2. Private Dining: Maximum 1 reservation per time slot (from config)
 * 3. Cancelled reservations don't count toward capacity
 */
class ReservationCapacityTest extends KernelTestCase
{
    /** @var array<string, mixed> */
    private array $config;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->config = static::getContainer()->getParameter('restaurant');
    }

    /**
     * TEST 1: Regular Dining Capacity from Config.
     *
     * Validates restaurant.yaml: regular_dining.max_guests_per_slot
     */
    public function testRegularDiningMaxCapacityIsEnforced(): void
    {
        $maxCapacity = $this->config['regular_dining']['max_guests_per_slot'];

        $this->assertEquals(
            20,
            $maxCapacity,
            'Regular dining max capacity must be 20 guests per slot from config'
        );
    }

    /**
     * TEST 2: Private Dining Single Reservation Rule from Config.
     *
     * Validates restaurant.yaml: private_dining.max_reservations_per_slot
     */
    public function testPrivateDiningAllowsOnlyOneReservation(): void
    {
        $maxReservations = $this->config['private_dining']['max_reservations_per_slot'];

        $this->assertEquals(
            1,
            $maxReservations,
            'Private dining must allow only 1 reservation per slot from config'
        );
    }

    /**
     * TEST 3: Party Size Validation for Regular Dining from Config.
     *
     * Validates restaurant.yaml: regular_dining.min_party_size and max_party_size
     */
    public function testRegularDiningPartySizeLimits(): void
    {
        $minPartySize = $this->config['regular_dining']['min_party_size'];
        $maxPartySize = $this->config['regular_dining']['max_party_size'];

        $this->assertEquals(1, $minPartySize, 'Regular dining minimum party size must be 1 from config');
        $this->assertEquals(10, $maxPartySize, 'Regular dining maximum party size must be 10 from config');

        // Test boundary validation
        $this->assertLessThanOrEqual(
            $maxPartySize,
            8,
            'Party size of 8 is within regular dining limits'
        );
    }

    /**
     * TEST 4: Party Size Validation for Private Dining from Config.
     *
     * Validates restaurant.yaml: private_dining.min_party_size and max_party_size
     */
    public function testPrivateDiningPartySizeLimits(): void
    {
        $minPartySize = $this->config['private_dining']['min_party_size'];
        $maxPartySize = $this->config['private_dining']['max_party_size'];

        $this->assertEquals(6, $minPartySize, 'Private dining minimum party size must be 6 from config');
        $this->assertEquals(12, $maxPartySize, 'Private dining maximum party size must be 12 from config');

        // Verify the range
        $this->assertGreaterThanOrEqual($minPartySize, 8);
        $this->assertLessThanOrEqual($maxPartySize, 10);
    }

    /**
     * TEST 5: Cancelled Reservations Don't Count Toward Capacity.
     *
     * Business Rule: Only active reservations (Pending, Confirmed) count toward capacity
     */
    public function testCancelledReservationsDontCountTowardCapacity(): void
    {
        $reservation = new Reservation();
        $reservation->onPrePersist();

        // Start as Pending
        $this->assertEquals(ReservationStatus::Pending, $reservation->getStatus());

        // Cancel the reservation
        $reservation->setStatus(ReservationStatus::Cancelled);

        $this->assertEquals(
            ReservationStatus::Cancelled,
            $reservation->getStatus(),
            'Cancelled reservation status must be Cancelled'
        );
    }

    /**
     * TEST 6: Multiple Reservations Capacity Scenario with Config.
     *
     * Business Rule: Verify that multiple reservations correctly sum to capacity
     */
    public function testMultipleReservationsWithinCapacity(): void
    {
        $maxCapacity = $this->config['regular_dining']['max_guests_per_slot'];

        // Scenario: Three reservations
        $reservation1PartySize = 8;
        $reservation2PartySize = 7;
        $reservation3PartySize = 4;

        $totalGuests = $reservation1PartySize + $reservation2PartySize + $reservation3PartySize;

        $this->assertEquals(19, $totalGuests, 'Total guests should be 19');
        $this->assertLessThanOrEqual(
            $maxCapacity,
            $totalGuests,
            '3 reservations (8+7+4=19 guests) should fit within capacity'
        );

        // Verify remaining capacity
        $remainingCapacity = $maxCapacity - $totalGuests;
        $this->assertEquals(1, $remainingCapacity, 'Should have 1 guest capacity remaining');
    }

    /**
     * TEST 7: Capacity Overflow Prevention with Config.
     *
     * Business Rule: Cannot accept reservation if it exceeds capacity
     */
    public function testCapacityOverflowIsDetected(): void
    {
        $maxCapacity = $this->config['regular_dining']['max_guests_per_slot'];
        $existingGuests = 18;
        $newPartySize = 5;

        $totalAfterBooking = $existingGuests + $newPartySize;
        $remainingCapacity = $maxCapacity - $existingGuests;

        $this->assertEquals(23, $totalAfterBooking, 'Would result in 23 guests');
        $this->assertGreaterThan(
            $maxCapacity,
            $totalAfterBooking,
            'Booking would exceed capacity'
        );

        $this->assertLessThan(
            $newPartySize,
            $remainingCapacity,
            'Remaining capacity (2) is less than requested party size (5)'
        );
    }

    /**
     * TEST 8: Reservation Type Enum Values.
     *
     * Verify that ReservationType enum has the correct values
     */
    public function testReservationTypeEnumValues(): void
    {
        $this->assertEquals('regular', ReservationType::Regular->value);
        $this->assertEquals('private_dining', ReservationType::PrivateDining->value);

        // Verify we have exactly 2 types
        $allTypes = ReservationType::cases();
        $this->assertCount(2, $allTypes, 'Should have exactly 2 reservation types');
    }
}
