<?php

namespace App\Entity;

use App\Repository\TimeSlotRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TimeSlotRepository::class)]
#[ORM\Index(name: 'IDX_TIME', columns: ['time'])]
#[ORM\Index(name: 'IDX_SLOT_TYPE', columns: ['slot_type'])]
class TimeSlot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeInterface $time = null;

    #[ORM\Column(length: 50)]
    private ?string $slotType = null;

    #[ORM\Column]
    private ?int $minCapacity = null;

    #[ORM\Column]
    private ?int $maxCapacity = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(\DateTimeInterface $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getTimeFormatted(): string
    {
        return $this->time ? $this->time->format('H:i') : '';
    }

    public function getSlotType(): ?string
    {
        return $this->slotType;
    }

    public function setSlotType(string $slotType): static
    {
        $this->slotType = $slotType;

        return $this;
    }

    public function getMinCapacity(): ?int
    {
        return $this->minCapacity;
    }

    public function setMinCapacity(int $minCapacity): static
    {
        $this->minCapacity = $minCapacity;

        return $this;
    }

    public function getMaxCapacity(): ?int
    {
        return $this->maxCapacity;
    }

    public function setMaxCapacity(int $maxCapacity): static
    {
        $this->maxCapacity = $maxCapacity;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function isRegularSlot(): bool
    {
        return 'regular' === $this->slotType;
    }

    public function isPrivateDiningSlot(): bool
    {
        return 'private_dining' === $this->slotType;
    }
}
