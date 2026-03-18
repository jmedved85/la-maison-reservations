<?php

namespace App\Repository;

use App\Entity\TimeSlot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TimeSlot>
 */
class TimeSlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeSlot::class);
    }

    /**
     * Find all active time slots of a specific type, ordered by time.
     *
     * @return TimeSlot[]
     */
    public function findActiveByType(string $slotType): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.slotType = :type')
            ->andWhere('t.isActive = :active')
            ->setParameter('type', $slotType)
            ->setParameter('active', true)
            ->orderBy('t.time', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Find all active time slots, ordered by time.
     *
     * @return TimeSlot[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.time', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Find time slot by time and type.
     */
    public function findOneByTimeAndType(\DateTimeInterface $time, string $slotType): ?TimeSlot
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.time = :time')
            ->andWhere('t.slotType = :type')
            ->setParameter('time', $time)
            ->setParameter('type', $slotType)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function save(TimeSlot $timeSlot): void
    {
        $this->getEntityManager()->persist($timeSlot);
        $this->getEntityManager()->flush();
    }
}
