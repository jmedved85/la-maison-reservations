<?php

namespace App\DataFixtures;

use App\Entity\RestaurantTable;
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

        $regularTables = [
            ['number' => '1', 'capacity' => 2, 'location' => 'Window'],
            ['number' => '2', 'capacity' => 2, 'location' => 'Window'],
            ['number' => '3', 'capacity' => 4, 'location' => 'Main Floor'],
            ['number' => '4', 'capacity' => 4, 'location' => 'Main Floor'],
            ['number' => '5', 'capacity' => 4, 'location' => 'Main Floor'],
            ['number' => '6', 'capacity' => 6, 'location' => 'Main Floor'],
            ['number' => '7', 'capacity' => 6, 'location' => 'Corner'],
            ['number' => '8', 'capacity' => 2, 'location' => 'Bar Area'],
        ];

        foreach ($regularTables as $tableData) {
            $table = new RestaurantTable();
            $table->setTableNumber($tableData['number']);
            $table->setCapacity($tableData['capacity']);
            $table->setTableType('regular');
            $table->setLocation($tableData['location']);
            $table->setIsActive(true);
            $manager->persist($table);
        }

        $privateTable = new RestaurantTable();
        $privateTable->setTableNumber('P1');
        $privateTable->setCapacity(12);
        $privateTable->setTableType('private');
        $privateTable->setLocation('Private Dining Room');
        $privateTable->setIsActive(true);
        $manager->persist($privateTable);

        $manager->flush();
    }
}
