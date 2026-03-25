<?php

namespace App\Tests;

use App\Entity\ReservationType;
use App\Service\TimeSlotService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests for Private Dining functionality.
 */
class PrivateDiningIntegrationTest extends KernelTestCase
{
    private TimeSlotService $timeSlotService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->timeSlotService = static::getContainer()->get(TimeSlotService::class);
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
}
