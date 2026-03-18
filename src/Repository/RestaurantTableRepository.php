<?php

namespace App\Repository;

use App\Entity\RestaurantTable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RestaurantTable>
 */
class RestaurantTableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RestaurantTable::class);
    }

    /**
     * Find all active tables of a specific type.
     *
     * @return RestaurantTable[]
     */
    public function findActiveByType(string $tableType): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.tableType = :type')
            ->andWhere('t.isActive = :active')
            ->setParameter('type', $tableType)
            ->setParameter('active', true)
            ->orderBy('t.tableNumber', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Find all active tables ordered by table number.
     *
     * @return RestaurantTable[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.tableNumber', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Calculate total capacity for a table type.
     */
    public function getTotalCapacityByType(string $tableType): int
    {
        $result = $this->createQueryBuilder('t')
            ->select('SUM(t.capacity)')
            ->andWhere('t.tableType = :type')
            ->andWhere('t.isActive = :active')
            ->setParameter('type', $tableType)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    public function save(RestaurantTable $table): void
    {
        $this->getEntityManager()->persist($table);
        $this->getEntityManager()->flush();
    }
}
