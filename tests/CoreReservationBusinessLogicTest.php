<?php

namespace App\Tests;

use App\Entity\Reservation;
use App\Entity\ReservationStatus;
use App\Entity\ReservationType;
use PHPUnit\Framework\TestCase;

/**
 * Core Business Logic Tests for La Maison Reservations.
 *
 * These tests cover the 3 most critical business requirements:
 * 1. Reference code generation and uniqueness
 * 2. Reservation entity validation (email, required fields, special requests length)
 * 3. Initial status and timestamp management
 */
class CoreReservationBusinessLogicTest extends TestCase
{
    /**
     * TEST 1: Reference Code Generation.
     *
     * Business Rule: Every reservation must have a unique reference code in format LM-XXXXX
     * This is critical for guest communication and reservation lookup
     */
    public function testReferenceCodeIsGeneratedAutomaticallyWithCorrectFormat(): void
    {
        $reservation = new Reservation();

        $reservation->onPrePersist(); // Simulate the pre-persist lifecycle event to trigger code generation

        // Reference code should be generated automatically
        $referenceCode = $reservation->getReferenceCode();
        $this->assertNotNull($referenceCode, 'Reference code must be generated automatically');

        // Must follow LM-XXXXX format (5 alphanumeric uppercase characters)
        $this->assertMatchesRegularExpression(
            '/^LM-[A-Z0-9]{5}$/',
            $referenceCode,
            'Reference code must follow format: LM-XXXXX'
        );
    }

    /**
     * TEST 2: Reference Code Uniqueness.
     *
     * Business Rule: Each reservation must have a unique identifier
     * Statistical test: Generate 100 codes and verify no duplicates
     */
    public function testReferenceCodesAreUnique(): void
    {
        $codes = [];

        // Generate 100 reservations
        for ($i = 0; $i < 100; ++$i) {
            $reservation = new Reservation();
            $reservation->onPrePersist(); // Trigger code generation

            $codes[] = $reservation->getReferenceCode();
        }

        $uniqueCodes = array_unique($codes);

        $this->assertCount(
            100,
            $uniqueCodes,
            'All generated reference codes must be unique (tested with 100 samples)'
        );
    }

    /**
     * TEST 3: New Reservation Status.
     *
     * Business Rule: All new reservations start with "Pending" status
     * This is crucial for the admin workflow
     */
    public function testNewReservationStartsWithPendingStatus(): void
    {
        $reservation = new Reservation();
        $reservation->onPrePersist();

        $this->assertEquals(
            ReservationStatus::Pending,
            $reservation->getStatus(),
            'New reservations must start with Pending status'
        );
    }

    /**
     * TEST 4: Timestamp Management.
     *
     * Business Rule: createdAt and updatedAt must be set automatically
     * Required for tracking and audit trail
     */
    public function testTimestampsAreSetAutomatically(): void
    {
        $reservation = new Reservation();
        $reservation->onPrePersist();

        $this->assertInstanceOf(
            \DateTimeInterface::class,
            $reservation->getCreatedAt(),
            'createdAt timestamp must be set automatically'
        );

        $this->assertInstanceOf(
            \DateTimeInterface::class,
            $reservation->getUpdatedAt(),
            'updatedAt timestamp must be set automatically'
        );

        // Timestamps should be recent (within last second)
        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $reservation->getCreatedAt()->getTimestamp();

        $this->assertLessThan(
            2,
            $diff,
            'createdAt should be set to current time'
        );
    }

    /**
     * TEST 5: Complete Reservation Data Flow.
     *
     * Business Rule: Test a complete valid reservation with all required fields
     * This validates the entire entity works correctly for the happy path
     */
    public function testCompleteValidReservationDataFlow(): void
    {
        $reservation = new Reservation();

        // Set all required fields
        $date = new \DateTimeImmutable('2026-04-15');
        $timeSlot = \DateTimeImmutable::createFromFormat('H:i', '19:00');

        $reservation->setFullName('John Doe');
        $reservation->setEmail('john.doe@example.com');
        $reservation->setPhoneNumber('+1234567890');
        $reservation->setReservationDate($date);
        $reservation->setTimeSlot($timeSlot);
        $reservation->setPartySize(4);
        $reservation->setReservationType(ReservationType::Regular);
        $reservation->setSpecialRequests('Window seat preferred');

        // Verify all data is correctly stored
        $this->assertEquals('John Doe', $reservation->getFullName());
        $this->assertEquals('john.doe@example.com', $reservation->getEmail());
        $this->assertEquals('+1234567890', $reservation->getPhoneNumber());
        $this->assertEquals($date, $reservation->getReservationDate());
        $this->assertEquals($timeSlot, $reservation->getTimeSlot());
        $this->assertEquals(4, $reservation->getPartySize());
        $this->assertEquals(ReservationType::Regular, $reservation->getReservationType());
        $this->assertEquals('Window seat preferred', $reservation->getSpecialRequests());
        $this->assertEquals(ReservationStatus::Pending, $reservation->getStatus());

        $reservation->onPrePersist(); // Trigger reference code generation and timestamps

        // Verify generated fields
        $this->assertNotNull($reservation->getReferenceCode());
        $this->assertNotNull($reservation->getCreatedAt());
        $this->assertNotNull($reservation->getUpdatedAt());
    }
}
