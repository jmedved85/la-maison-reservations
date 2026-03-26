<?php

namespace App\DataFixtures;

use App\Entity\TimeSlot;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $regularSlots = [
            '12:00', '12:30', '13:00', '13:30', '14:00', '14:30',
            '15:00', '15:30', '16:00', '16:30', '17:00', '17:30',
            '18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00',
        ];

        foreach ($regularSlots as $timeString) {
            $timeSlot = new TimeSlot();
            $timeSlot->setTime(\DateTimeImmutable::createFromFormat('H:i', $timeString));
            $timeSlot->setSlotType('regular');
            $timeSlot->setMinCapacity(1);
            $timeSlot->setMaxCapacity(20);
            $timeSlot->setDescription('Regular dining slot');
            $timeSlot->setIsActive(true);
            $manager->persist($timeSlot);
        }

        $privateDiningSlots = ['18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00'];

        foreach ($privateDiningSlots as $timeString) {
            $timeSlot = new TimeSlot();
            $timeSlot->setTime(\DateTimeImmutable::createFromFormat('H:i', $timeString));
            $timeSlot->setSlotType('private_dining');
            $timeSlot->setMinCapacity(6);
            $timeSlot->setMaxCapacity(12);
            $timeSlot->setDescription('Private dining room slot (Friday/Saturday only)');
            $timeSlot->setIsActive(true);
            $manager->persist($timeSlot);
        }

        $manager->flush();
    }
}
