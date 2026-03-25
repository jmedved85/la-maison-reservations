<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\ReservationStatus;
use App\Entity\ReservationType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Find reservations by date, optionally filtered by status.
     *
     * @return Reservation[]
     */
    public function findByDate(\DateTimeInterface $date, ?ReservationStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.reservationDate = :date')
            ->setParameter('date', $date)
            ->orderBy('r.timeSlot', 'ASC')
        ;

        if (null !== $status) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $status)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find upcoming reservations (sorted by date and time).
     *
     * @return Reservation[]
     */
    public function findUpcoming(int $limit = 50): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.reservationDate >= :today')
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('r.reservationDate', 'ASC')
            ->addOrderBy('r.timeSlot', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Find reservations for admin list view.
     * If date is null, returns all upcoming reservations.
     * If date is provided, returns reservations for that specific date.
     * Can be filtered by status and sorted by date.
     *
     * @param string $sortOrder 'ASC' or 'DESC'
     *
     * @return Reservation[]
     */
    public function findForAdminList(
        ?\DateTimeInterface $date = null,
        ?ReservationStatus $status = null,
        string $sortOrder = 'ASC',
    ): array {
        $qb = $this->createQueryBuilder('r');

        if (null === $date) {
            // No date filter - show all upcoming reservations
            $qb->andWhere('r.reservationDate >= :today')
                ->setParameter('today', new \DateTime('today'))
            ;
        } else {
            $qb->andWhere('r.reservationDate = :date')
                ->setParameter('date', $date)
            ;
        }

        if (null !== $status) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $status)
            ;
        }

        $sortOrder = strtoupper($sortOrder);
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'ASC';
        }

        $qb->orderBy('r.reservationDate', $sortOrder)
            ->addOrderBy('r.timeSlot', $sortOrder)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * Calculate total number of guests for a specific date and time slot
     * Excludes cancelled reservations.
     */
    public function getTotalGuestsForTimeSlot(
        \DateTimeInterface $date,
        \DateTimeInterface $timeSlot,
        ReservationType $reservationType = ReservationType::Regular,
    ): int {
        $result = $this->createQueryBuilder('r')
            ->select('SUM(r.partySize)')
            ->andWhere('r.reservationDate = :date')
            ->andWhere('r.timeSlot = :timeSlot')
            ->andWhere('r.reservationType = :type')
            ->andWhere('r.status != :cancelled')
            ->setParameter('date', $date)
            ->setParameter('timeSlot', $timeSlot)
            ->setParameter('type', $reservationType)
            ->setParameter('cancelled', ReservationStatus::Cancelled)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    /**
     * Calculate total expected guests for a specific date (all time slots)
     * Excludes cancelled reservations.
     */
    public function getTotalGuestsForDate(\DateTimeInterface $date): int
    {
        $result = $this->createQueryBuilder('r')
            ->select('SUM(r.partySize)')
            ->andWhere('r.reservationDate = :date')
            ->andWhere('r.status != :cancelled')
            ->setParameter('date', $date)
            ->setParameter('cancelled', ReservationStatus::Cancelled)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    /**
     * Check if private dining is available for a specific date and time slot.
     */
    public function isPrivateDiningAvailable(
        \DateTimeInterface $date,
        \DateTimeInterface $timeSlot,
    ): bool {
        $count = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.reservationDate = :date')
            ->andWhere('r.timeSlot = :timeSlot')
            ->andWhere('r.reservationType = :type')
            ->andWhere('r.status != :cancelled')
            ->setParameter('date', $date)
            ->setParameter('timeSlot', $timeSlot)
            ->setParameter('type', ReservationType::PrivateDining)
            ->setParameter('cancelled', ReservationStatus::Cancelled)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return 0 == $count;
    }

    /**
     * Find reservation by reference code.
     */
    public function findByReferenceCode(string $referenceCode): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.referenceCode = :code')
            ->setParameter('code', $referenceCode)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Get fully booked time slots for a specific date and reservation type
     * Returns array of time slot strings that have reached capacity.
     *
     * @return array<string>
     */
    public function getFullyBookedTimeSlots(
        \DateTimeInterface $date,
        ReservationType $reservationType = ReservationType::Regular,
        ?int $maxCapacity = null,
    ): array {
        // Use type-specific capacity if not provided
        if (null === $maxCapacity) {
            $maxCapacity = $reservationType->getMaxCapacity();
        }

        $qb = $this->createQueryBuilder('r')
            ->select('r.timeSlot, SUM(r.partySize) as totalGuests')
            ->andWhere('r.reservationDate = :date')
            ->andWhere('r.reservationType = :type')
            ->andWhere('r.status != :cancelled')
            ->setParameter('date', $date)
            ->setParameter('type', $reservationType)
            ->setParameter('cancelled', ReservationStatus::Cancelled)
            ->groupBy('r.timeSlot')
            ->having('SUM(r.partySize) >= :capacity')
            ->setParameter('capacity', $maxCapacity)
        ;

        $results = $qb->getQuery()->getResult();

        return array_map(function ($result) {
            return $result['timeSlot']->format('H:i');
        }, $results);
    }

    /**
     * Get reservations filtered by status and date range.
     *
     * @return Reservation[]
     */
    public function findByStatusAndDateRange(
        ReservationStatus $status,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null,
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', $status)
            ->orderBy('r.reservationDate', 'ASC')
            ->addOrderBy('r.timeSlot', 'ASC');

        if (null !== $startDate) {
            $qb->andWhere('r.reservationDate >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if (null !== $endDate) {
            $qb->andWhere('r.reservationDate <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }

    public function save(Reservation $reservation): void
    {
        $this->getEntityManager()->persist($reservation);
        $this->getEntityManager()->flush();
    }

    public function remove(Reservation $reservation): void
    {
        $this->getEntityManager()->remove($reservation);
        $this->getEntityManager()->flush();
    }

    /**
     * Calculate total number of guests for a specific date and time slot (using string time).
     * Excludes cancelled reservations.
     */
    public function getTotalGuestsForSlot(
        \DateTimeInterface $date,
        string $timeSlot,
        ReservationType $reservationType,
    ): int {
        $time = \DateTimeImmutable::createFromFormat('H:i', $timeSlot);

        $result = $this->createQueryBuilder('r')
            ->select('SUM(r.partySize)')
            ->andWhere('r.reservationDate = :date')
            ->andWhere('r.timeSlot = :timeSlot')
            ->andWhere('r.reservationType = :type')
            ->andWhere('r.status != :cancelled')
            ->setParameter('date', $date)
            ->setParameter('timeSlot', $time)
            ->setParameter('type', $reservationType)
            ->setParameter('cancelled', ReservationStatus::Cancelled)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    /**
     * Count active reservations (non-cancelled) for a specific slot.
     */
    public function countActiveReservationsForSlot(
        \DateTimeInterface $date,
        string $timeSlot,
        ReservationType $reservationType,
    ): int {
        $time = \DateTimeImmutable::createFromFormat('H:i', $timeSlot);

        $result = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.reservationDate = :date')
            ->andWhere('r.timeSlot = :timeSlot')
            ->andWhere('r.reservationType = :type')
            ->andWhere('r.status != :cancelled')
            ->setParameter('date', $date)
            ->setParameter('timeSlot', $time)
            ->setParameter('type', $reservationType)
            ->setParameter('cancelled', ReservationStatus::Cancelled)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }
}
