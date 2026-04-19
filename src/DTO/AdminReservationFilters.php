<?php

use App\Entity\ReservationStatus;
use Symfony\Component\HttpFoundation\Request;

class AdminReservationFilters
{
    public function __construct(
        public readonly ?DateTimeImmutable $date = null,
        public readonly ?ReservationStatus $status = null,
        public readonly string $sortOrder = 'ASC',
        public readonly int $page = 1,
        public readonly int $limit = 15,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $date = self::parseDate($request->query->get('date'));
        $status = self::parseStatus($request->query->get('status'));
        $sortOrder = self::parseSortOrder($request->query->get('order', 'ASC'));
        $page = max(1, (int) $request->query->get('page', 1));

        return new self($date, $status, $sortOrder, $page);
    }

    private static function parseDate(?string $dateStr): ?DateTimeImmutable
    {
        if ('' === $dateStr) {
            return null;
        }

        try {
            return new DateTimeImmutable($dateStr);
        } catch (Exception) {
            return null;
        }
    }

    private static function parseStatus(?string $statusStr): ?ReservationStatus
    {
        if ('' === $statusStr) {
            return null;
        }

        try {
            return ReservationStatus::from($statusStr);
        } catch (ValueError) {
            return null;
        }
    }

    private static function parseSortOrder(?string $order): string
    {
        $order = strtoupper($order ?? 'ASC');

        return in_array($order, ['ASC', 'DESC']) ? $order : 'ASC';
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}
