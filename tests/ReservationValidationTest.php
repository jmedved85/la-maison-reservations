<?php

namespace App\Tests;

use App\Entity\Reservation;
use App\Entity\ReservationType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests for Reservation Validation Rules.
 *
 * Critical Validation Rules:
 * 1. Email must be valid format
 * 2. All required fields must be filled
 * 3. Special requests max 500 characters
 * 4. Name, phone, date, time, party size constraints
 */
class ReservationValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get('validator');
    }

    /**
     * TEST 1: Valid Email Format.
     *
     * Business Rule: Email must be in valid format for guest communication
     */
    public function testValidEmailIsAccepted(): void
    {
        $reservation = $this->createValidReservation();
        $reservation->setEmail('valid.email@example.com');

        $violations = $this->validator->validate($reservation);
        $emailViolations = [];

        foreach ($violations as $violation) {
            if ('email' === $violation->getPropertyPath()) {
                $emailViolations[] = $violation;
            }
        }

        $this->assertCount(
            0,
            $emailViolations,
            'Valid email should not produce validation errors'
        );
    }

    /**
     * TEST 2: Invalid Email Format Rejected.
     *
     * Business Rule: System must reject invalid email formats
     */
    public function testInvalidEmailIsRejected(): void
    {
        $reservation = $this->createValidReservation();

        $invalidEmails = [
            'notanemail',
            'missing@domain',
            '@nodomain.com',
            'spaces in@email.com',
            'multiple@@signs.com',
        ];

        foreach ($invalidEmails as $invalidEmail) {
            $reservation->setEmail($invalidEmail);
            $violations = $this->validator->validate($reservation);

            $hasEmailError = false;
            foreach ($violations as $violation) {
                if ('email' === $violation->getPropertyPath()) {
                    $hasEmailError = true;
                    break;
                }
            }

            $this->assertTrue(
                $hasEmailError,
                "Invalid email '{$invalidEmail}' should be rejected"
            );
        }
    }

    /**
     * TEST 3: Required Fields Validation.
     *
     * Business Rule: All core reservation fields are mandatory
     */
    public function testRequiredFieldsMustNotBeEmpty(): void
    {
        $reservation = new Reservation();
        $reservation->onPrePersist(); // Initialize reference code and timestamps

        // Validate empty reservation
        $violations = $this->validator->validate($reservation);

        $this->assertGreaterThan(
            0,
            count($violations),
            'Empty reservation should have validation errors'
        );

        // Check specific required fields
        $requiredFields = ['fullName', 'email', 'phoneNumber', 'reservationDate', 'timeSlot', 'partySize'];

        foreach ($requiredFields as $field) {
            $hasFieldError = false;

            foreach ($violations as $violation) {
                if ($violation->getPropertyPath() === $field) {
                    $hasFieldError = true;
                    break;
                }
            }

            $this->assertTrue(
                $hasFieldError,
                "Required field '{$field}' should have validation error when empty"
            );
        }
    }

    /**
     * TEST 4: Special Requests Length Limit.
     *
     * Business Rule: Special requests cannot exceed 500 characters
     * This prevents excessive text and database issues
     */
    public function testSpecialRequestsMaxLength(): void
    {
        $reservation = $this->createValidReservation();

        // Test valid length (500 chars)
        $validText = str_repeat('a', 500);
        $reservation->setSpecialRequests($validText);

        $violations = $this->validator->validate($reservation);
        $lengthViolations = [];

        foreach ($violations as $violation) {
            if ('specialRequests' === $violation->getPropertyPath()) {
                $lengthViolations[] = $violation;
            }
        }

        $this->assertCount(
            0,
            $lengthViolations,
            '500 characters should be accepted for special requests'
        );

        // Test invalid length (501 chars)
        $tooLongText = str_repeat('a', 501);
        $reservation->setSpecialRequests($tooLongText);

        $violations = $this->validator->validate($reservation);
        $hasLengthError = false;

        foreach ($violations as $violation) {
            if ('specialRequests' === $violation->getPropertyPath()
                && str_contains($violation->getMessage(), '500')) {
                $hasLengthError = true;
                break;
            }
        }

        $this->assertTrue(
            $hasLengthError,
            '501 characters should be rejected for special requests'
        );
    }

    /**
     * TEST 5: Full Name Validation.
     *
     * Business Rule: Name must be 2-255 characters
     */
    public function testFullNameLengthConstraints(): void
    {
        $reservation = $this->createValidReservation();

        // Test too short (1 character)
        $reservation->setFullName('A');
        $violations = $this->validator->validate($reservation);

        $hasMinLengthError = false;
        foreach ($violations as $violation) {
            if ('fullName' === $violation->getPropertyPath()
                && str_contains($violation->getMessage(), '2')) {
                $hasMinLengthError = true;
                break;
            }
        }

        $this->assertTrue($hasMinLengthError, 'Name with 1 character should be rejected');

        // Test valid (2 characters)
        $reservation->setFullName('Ab');
        $violations = $this->validator->validate($reservation);

        $hasNameError = false;
        foreach ($violations as $violation) {
            if ('fullName' === $violation->getPropertyPath()) {
                $hasNameError = true;
                break;
            }
        }

        $this->assertFalse($hasNameError, 'Name with 2 characters should be valid');

        // Test too long (256 characters)
        $reservation->setFullName(str_repeat('A', 256));
        $violations = $this->validator->validate($reservation);

        $hasMaxLengthError = false;
        foreach ($violations as $violation) {
            if ('fullName' === $violation->getPropertyPath()
                && str_contains($violation->getMessage(), '255')) {
                $hasMaxLengthError = true;
                break;
            }
        }

        $this->assertTrue($hasMaxLengthError, 'Name with 256 characters should be rejected');
    }

    /**
     * TEST 6: Phone Number Validation.
     *
     * Business Rule: Phone number must be 6-50 characters
     */
    public function testPhoneNumberLengthConstraints(): void
    {
        $reservation = $this->createValidReservation();

        // Test too short (5 characters)
        $reservation->setPhoneNumber('12345');
        $violations = $this->validator->validate($reservation);

        $hasMinLengthError = false;
        foreach ($violations as $violation) {
            if ('phoneNumber' === $violation->getPropertyPath()
                && str_contains($violation->getMessage(), '6')) {
                $hasMinLengthError = true;
                break;
            }
        }

        $this->assertTrue($hasMinLengthError, 'Phone with 5 characters should be rejected');

        // Test valid (6 characters)
        $reservation->setPhoneNumber('123456');
        $violations = $this->validator->validate($reservation);

        $hasPhoneError = false;
        foreach ($violations as $violation) {
            if ('phoneNumber' === $violation->getPropertyPath()) {
                $hasPhoneError = true;
                break;
            }
        }

        $this->assertFalse($hasPhoneError, 'Phone with 6 characters should be valid');
    }

    /**
     * TEST 7: Party Size Range Validation.
     *
     * Business Rule: Party size must be within type limits
     * - Regular: 1-10 guests
     * - Private Dining: 6-12 guests
     */
    public function testPartySizeRangeConstraints(): void
    {
        $reservation = $this->createValidReservation();

        // Test invalid (0 guests)
        $reservation->setPartySize(0);
        $violations = $this->validator->validate($reservation);

        $hasRangeError = false;
        foreach ($violations as $violation) {
            if ('partySize' === $violation->getPropertyPath()) {
                $hasRangeError = true;
                break;
            }
        }

        $this->assertTrue($hasRangeError, 'Party size 0 should be rejected');

        // Test valid for Regular dining (1-10)
        $reservation->setReservationType(ReservationType::Regular);
        foreach ([1, 5, 10] as $validSize) {
            $reservation->setPartySize($validSize);
            $violations = $this->validator->validate($reservation);

            $hasPartySizeError = false;
            foreach ($violations as $violation) {
                if ('partySize' === $violation->getPropertyPath()) {
                    $hasPartySizeError = true;
                    break;
                }
            }

            $this->assertFalse(
                $hasPartySizeError,
                "Party size {$validSize} should be valid for Regular dining"
            );
        }

        // Test invalid for Regular dining (11+ guests)
        $reservation->setReservationType(ReservationType::Regular);
        $reservation->setPartySize(11);
        $violations = $this->validator->validate($reservation);

        $hasMaxError = false;
        foreach ($violations as $violation) {
            if ('partySize' === $violation->getPropertyPath()) {
                $hasMaxError = true;
                break;
            }
        }

        $this->assertTrue($hasMaxError, 'Party size 11 should be rejected for Regular dining');

        // Test valid for Private Dining (6-12)
        $reservation->setReservationType(ReservationType::PrivateDining);
        foreach ([6, 8, 12] as $validSize) {
            $reservation->setPartySize($validSize);
            $violations = $this->validator->validate($reservation);

            $hasPartySizeError = false;
            foreach ($violations as $violation) {
                if ('partySize' === $violation->getPropertyPath()) {
                    $hasPartySizeError = true;
                    break;
                }
            }

            $this->assertFalse(
                $hasPartySizeError,
                "Party size {$validSize} should be valid for Private Dining"
            );
        }

        // Test invalid for Private Dining (13+ guests)
        $reservation->setReservationType(ReservationType::PrivateDining);
        $reservation->setPartySize(13);
        $violations = $this->validator->validate($reservation);

        $hasMaxError = false;
        foreach ($violations as $violation) {
            if ('partySize' === $violation->getPropertyPath()) {
                $hasMaxError = true;
                break;
            }
        }

        $this->assertTrue($hasMaxError, 'Party size 13 should be rejected for Private Dining');
    }

    /**
     * TEST 8: Complete Valid Reservation Passes All Validation.
     *
     * Verify that a properly filled reservation has no validation errors
     */
    public function testCompleteValidReservationPassesValidation(): void
    {
        $reservation = $this->createValidReservation();

        $violations = $this->validator->validate($reservation);

        $this->assertCount(
            0,
            $violations,
            'Complete valid reservation should have zero validation errors. Violations: ' .
            (string) $violations
        );
    }

    /**
     * Helper method to create a valid reservation for testing.
     */
    private function createValidReservation(): Reservation
    {
        $reservation = new Reservation();
        $reservation->onPrePersist(); // Initialize reference code and timestamps

        $reservation->setFullName('John Doe');
        $reservation->setEmail('john.doe@example.com');
        $reservation->setPhoneNumber('+1234567890');
        $reservation->setReservationDate(new \DateTimeImmutable('2026-04-15'));
        $reservation->setTimeSlot(\DateTimeImmutable::createFromFormat('H:i', '19:00'));
        $reservation->setPartySize(4);
        $reservation->setReservationType(ReservationType::Regular);

        return $reservation;
    }
}
