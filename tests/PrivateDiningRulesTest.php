<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests for Private Dining Business Rules.
 *
 * Validates that restaurant.yaml configuration matches business requirements:
 * 1. Available only on Friday and Saturday (days 5 and 6)
 * 2. Time slots: 18:00 to 22:00 only
 * 3. Party size: 6-12 guests required
 * 4. Only 1 reservation per time slot (exclusivity)
 */
class PrivateDiningRulesTest extends KernelTestCase
{
    /** @var array<string, mixed> */
    private array $config;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->config = static::getContainer()->getParameter('restaurant');
    }

    /**
     * TEST 1: Private Dining Available Days from Config.
     *
     * Business Rule: Private dining is available ONLY on Friday (5) and Saturday (6)
     * Validates restaurant.yaml: private_dining.available_days
     */
    public function testPrivateDiningAvailableOnlyOnWeekends(): void
    {
        $availableDays = $this->config['private_dining']['available_days'];

        $this->assertCount(2, $availableDays, 'Private dining should be available on exactly 2 days');
        $this->assertContains(5, $availableDays, 'Friday must be available');
        $this->assertContains(6, $availableDays, 'Saturday must be available');

        // Verify other days are NOT included
        $this->assertNotContains(1, $availableDays, 'Monday must NOT be available');
        $this->assertNotContains(2, $availableDays, 'Tuesday must NOT be available');
        $this->assertNotContains(3, $availableDays, 'Wednesday must NOT be available');
        $this->assertNotContains(4, $availableDays, 'Thursday must NOT be available');
        $this->assertNotContains(7, $availableDays, 'Sunday must NOT be available');
    }

    /**
     * TEST 2: Verify Day of Week for Specific Dates.
     */
    public function testDayOfWeekDetectionForPrivateDining(): void
    {
        $friday = new \DateTimeImmutable('2026-04-17');
        $this->assertEquals('5', $friday->format('N'), 'April 17, 2026 must be Friday (5)');

        $saturday = new \DateTimeImmutable('2026-04-18');
        $this->assertEquals('6', $saturday->format('N'), 'April 18, 2026 must be Saturday (6)');

        $monday = new \DateTimeImmutable('2026-04-20');
        $this->assertEquals('1', $monday->format('N'), 'April 20, 2026 must be Monday (1)');

        $availableDays = $this->config['private_dining']['available_days'];

        $this->assertTrue(
            in_array((int) $friday->format('N'), $availableDays, true),
            'Friday should be in available days from config'
        );
        $this->assertFalse(
            in_array((int) $monday->format('N'), $availableDays, true),
            'Monday should NOT be in available days from config'
        );
    }

    /**
     * TEST 3: Private Dining Time Slot Range from Config.
     *
     * Validates restaurant.yaml: private_dining.start_time and end_time
     */
    public function testPrivateDiningTimeSlotRange(): void
    {
        $startTime = $this->config['private_dining']['start_time'];
        $endTime = $this->config['private_dining']['end_time'];

        $this->assertEquals('18:00', $startTime, 'Private dining starts at 18:00');
        $this->assertEquals('22:00', $endTime, 'Private dining ends at 22:00');

        // Parse times for comparison
        $start = \DateTimeImmutable::createFromFormat('H:i', $startTime);
        $end = \DateTimeImmutable::createFromFormat('H:i', $endTime);

        $this->assertEquals(18, (int) $start->format('H'), 'Start hour must be 18 (6 PM)');
        $this->assertEquals(22, (int) $end->format('H'), 'End hour must be 22 (10 PM)');

        // Duration check: 4 hours
        $duration = $end->getTimestamp() - $start->getTimestamp();
        $hours = $duration / 3600;
        $this->assertEquals(4, $hours, 'Private dining window is 4 hours');
    }

    /**
     * TEST 4: Time Slot Validation Against Config.
     */
    public function testTimeSlotIsWithinPrivateDiningWindow(): void
    {
        $startTime = $this->config['private_dining']['start_time'];
        $endTime = $this->config['private_dining']['end_time'];

        $privateDiningStart = \DateTimeImmutable::createFromFormat('H:i', $startTime);
        $privateDiningEnd = \DateTimeImmutable::createFromFormat('H:i', $endTime);

        // Valid time slots
        $validSlots = ['18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00', '21:30'];

        foreach ($validSlots as $slot) {
            $time = \DateTimeImmutable::createFromFormat('H:i', $slot);

            $this->assertGreaterThanOrEqual(
                $privateDiningStart->getTimestamp(),
                $time->getTimestamp(),
                "Time slot {$slot} should be >= {$startTime}"
            );

            $this->assertLessThan(
                $privateDiningEnd->getTimestamp(),
                $time->getTimestamp(),
                "Time slot {$slot} should be < {$endTime}"
            );
        }

        // Invalid time slots (outside window)
        $invalidSlots = ['12:00', '17:30', '22:00', '22:30'];

        foreach ($invalidSlots as $slot) {
            $time = \DateTimeImmutable::createFromFormat('H:i', $slot);
            $isValid = $time >= $privateDiningStart && $time < $privateDiningEnd;

            $this->assertFalse(
                $isValid,
                "Time slot {$slot} should be INVALID for private dining"
            );
        }
    }

    /**
     * TEST 5: Private Dining Party Size Requirements from Config.
     *
     * Validates restaurant.yaml: private_dining.min_party_size and max_party_size
     */
    public function testPrivateDiningPartySizeRequirements(): void
    {
        $minPartySize = $this->config['private_dining']['min_party_size'];
        $maxPartySize = $this->config['private_dining']['max_party_size'];

        $this->assertEquals(6, $minPartySize, 'Min party size must be 6 from config');
        $this->assertEquals(12, $maxPartySize, 'Max party size must be 12 from config');

        // Valid party sizes
        $validSizes = [6, 7, 8, 9, 10, 11, 12];

        foreach ($validSizes as $size) {
            $this->assertGreaterThanOrEqual($minPartySize, $size);
            $this->assertLessThanOrEqual($maxPartySize, $size);
        }

        // Invalid party sizes (too small)
        $tooSmall = [1, 2, 3, 4, 5];

        foreach ($tooSmall as $size) {
            $this->assertLessThan(
                $minPartySize,
                $size,
                "Party size {$size} is too small for private dining (min: {$minPartySize})"
            );
        }

        // Invalid party sizes (too large)
        $tooLarge = [13, 14, 15, 20];

        foreach ($tooLarge as $size) {
            $this->assertGreaterThan(
                $maxPartySize,
                $size,
                "Party size {$size} exceeds private dining maximum (max: {$maxPartySize})"
            );
        }
    }

    /**
     * TEST 6: Exclusivity Rule from Config.
     *
     * Validates restaurant.yaml: private_dining.max_reservations_per_slot
     */
    public function testPrivateDiningExclusivityRule(): void
    {
        $maxReservationsPerSlot = $this->config['private_dining']['max_reservations_per_slot'];

        $this->assertEquals(
            1,
            $maxReservationsPerSlot,
            'Private dining must allow exactly 1 reservation per slot for exclusivity'
        );

        // Simulate scenario: if 1 reservation exists
        $existingReservations = 1;
        $canAcceptMore = $existingReservations < $maxReservationsPerSlot;

        $this->assertFalse(
            $canAcceptMore,
            'Cannot accept more reservations when slot is already booked'
        );

        // Scenario: slot is empty
        $existingReservations = 0;
        $canAcceptMore = $existingReservations < $maxReservationsPerSlot;

        $this->assertTrue(
            $canAcceptMore,
            'Can accept reservation when slot is empty'
        );
    }

    /**
     * TEST 7: Combined Rules Validation with Config.
     */
    public function testCompletePrivateDiningScenario(): void
    {
        // Get config values
        $availableDays = $this->config['private_dining']['available_days'];
        $startTime = $this->config['private_dining']['start_time'];
        $endTime = $this->config['private_dining']['end_time'];
        $minSize = $this->config['private_dining']['min_party_size'];
        $maxSize = $this->config['private_dining']['max_party_size'];

        // Scenario: Friday, April 17, 2026 at 19:30 with 8 guests
        $date = new \DateTimeImmutable('2026-04-17'); // Friday
        $timeSlot = '19:30';
        $partySize = 8;

        // Rule 1: Check day
        $dayOfWeek = (int) $date->format('N');
        $isDayValid = in_array($dayOfWeek, $availableDays, true);
        $this->assertTrue($isDayValid, 'Friday is valid for private dining');

        // Rule 2: Check time
        $time = \DateTimeImmutable::createFromFormat('H:i', $timeSlot);
        $start = \DateTimeImmutable::createFromFormat('H:i', $startTime);
        $end = \DateTimeImmutable::createFromFormat('H:i', $endTime);
        $isTimeValid = $time >= $start && $time < $end;
        $this->assertTrue($isTimeValid, '19:30 is within private dining hours');

        // Rule 3: Check party size
        $isPartySizeValid = $partySize >= $minSize && $partySize <= $maxSize;
        $this->assertTrue($isPartySizeValid, '8 guests is valid for private dining');
    }

    /**
     * TEST 8: Invalid Private Dining Scenario with Config.
     */
    public function testInvalidPrivateDiningScenario(): void
    {
        $availableDays = $this->config['private_dining']['available_days'];
        $startTime = $this->config['private_dining']['start_time'];
        $endTime = $this->config['private_dining']['end_time'];
        $minSize = $this->config['private_dining']['min_party_size'];
        $maxSize = $this->config['private_dining']['max_party_size'];

        // Scenario: Monday at 12:00 with 4 guests (violates all 3 rules)
        $date = new \DateTimeImmutable('2026-04-20'); // Monday
        $timeSlot = '12:00';
        $partySize = 4;

        // Rule 1: Check day (INVALID)
        $dayOfWeek = (int) $date->format('N');
        $isDayValid = in_array($dayOfWeek, $availableDays, true);
        $this->assertFalse($isDayValid, 'Monday is NOT valid for private dining');

        // Rule 2: Check time (INVALID)
        $time = \DateTimeImmutable::createFromFormat('H:i', $timeSlot);
        $start = \DateTimeImmutable::createFromFormat('H:i', $startTime);
        $end = \DateTimeImmutable::createFromFormat('H:i', $endTime);
        $isTimeValid = $time >= $start && $time < $end;
        $this->assertFalse($isTimeValid, '12:00 is NOT within private dining hours');

        // Rule 3: Check party size (INVALID)
        $isPartySizeValid = $partySize >= $minSize && $partySize <= $maxSize;
        $this->assertFalse($isPartySizeValid, '4 guests is too small for private dining');
    }
}
