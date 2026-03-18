<?php

namespace App\Entity;

use App\Repository\RestaurantTableRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RestaurantTableRepository::class)]
#[ORM\Index(name: 'IDX_TABLE_TYPE', columns: ['table_type'])]
#[ORM\Index(name: 'IDX_TABLE_NUMBER', columns: ['table_number'])]
class RestaurantTable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $tableNumber = null;

    #[ORM\Column]
    private ?int $capacity = null;

    #[ORM\Column(length: 50)]
    private ?string $tableType = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $location = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTableNumber(): ?string
    {
        return $this->tableNumber;
    }

    public function setTableNumber(string $tableNumber): static
    {
        $this->tableNumber = $tableNumber;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getTableType(): ?string
    {
        return $this->tableType;
    }

    public function setTableType(string $tableType): static
    {
        $this->tableType = $tableType;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isRegularTable(): bool
    {
        return 'regular' === $this->tableType;
    }

    public function isPrivateTable(): bool
    {
        return 'private' === $this->tableType;
    }

    public function getDisplayName(): string
    {
        return sprintf('Table %s (%d seats)', $this->tableNumber, $this->capacity);
    }
}
