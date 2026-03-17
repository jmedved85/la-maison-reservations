<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Index(name: 'IDX_RESERVATION_DATE', columns: ['reservation_date'])]
#[ORM\Index(name: 'IDX_STATUS', columns: ['status'])]
#[ORM\Index(name: 'IDX_DATE_TIME', columns: ['reservation_date', 'time_slot'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'UNIQ_REFERENCE_CODE', fields: ['referenceCode'])]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 8, unique: true)]
    private ?string $referenceCode = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Full name is required')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Name must be at least {{ limit }} characters',
        maxMessage: 'Name cannot be longer than {{ limit }} characters'
    )]
    private ?string $fullName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please provide a valid email address')]
    private ?string $email = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Phone number is required')]
    #[Assert\Length(
        min: 6,
        max: 50,
        minMessage: 'Phone number must be at least {{ limit }} characters',
        maxMessage: 'Phone number cannot be longer than {{ limit }} characters'
    )]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotBlank(message: 'Reservation date is required')]
    private ?\DateTimeInterface $reservationDate = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Assert\NotBlank(message: 'Time slot is required')]
    private ?\DateTimeInterface $timeSlot = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Party size is required')]
    #[Assert\Range(
        min: 1,
        max: 12,
        notInRangeMessage: 'Party size must be between {{ min }} and {{ max }} guests'
    )]
    private ?int $partySize = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: 'Special requests cannot exceed {{ limit }} characters'
    )]
    private ?string $specialRequests = null;

    #[ORM\Column(enumType: ReservationStatus::class)]
    private ReservationStatus $status = ReservationStatus::Pending;

    #[ORM\Column(enumType: ReservationType::class)]
    private ReservationType $reservationType = ReservationType::Regular;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        if (null === $this->referenceCode) {
            $this->generateReferenceCode();
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function generateReferenceCode(): void
    {
        // Generate reference code in format LM-XXXXX (5 alphanumeric characters)
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = 'LM-';
        for ($i = 0; $i < 5; ++$i) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        $this->referenceCode = $code;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReferenceCode(): ?string
    {
        return $this->referenceCode;
    }

    public function setReferenceCode(string $referenceCode): static
    {
        $this->referenceCode = $referenceCode;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getReservationDate(): ?\DateTimeInterface
    {
        return $this->reservationDate;
    }

    public function setReservationDate(\DateTimeInterface $reservationDate): static
    {
        $this->reservationDate = $reservationDate;

        return $this;
    }

    public function getTimeSlot(): ?\DateTimeInterface
    {
        return $this->timeSlot;
    }

    public function setTimeSlot(\DateTimeInterface $timeSlot): static
    {
        $this->timeSlot = $timeSlot;

        return $this;
    }

    public function getPartySize(): ?int
    {
        return $this->partySize;
    }

    public function setPartySize(int $partySize): static
    {
        $this->partySize = $partySize;

        return $this;
    }

    public function getSpecialRequests(): ?string
    {
        return $this->specialRequests;
    }

    public function setSpecialRequests(?string $specialRequests): static
    {
        $this->specialRequests = $specialRequests;

        return $this;
    }

    public function getStatus(): ReservationStatus
    {
        return $this->status;
    }

    public function setStatus(ReservationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getReservationType(): ReservationType
    {
        return $this->reservationType;
    }

    public function setReservationType(ReservationType $reservationType): static
    {
        $this->reservationType = $reservationType;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function isPrivateDining(): bool
    {
        return ReservationType::PrivateDining === $this->reservationType;
    }

    public function isPending(): bool
    {
        return ReservationStatus::Pending === $this->status;
    }

    public function isConfirmed(): bool
    {
        return ReservationStatus::Confirmed === $this->status;
    }

    public function isCancelled(): bool
    {
        return ReservationStatus::Cancelled === $this->status;
    }

    public function isCompleted(): bool
    {
        return ReservationStatus::Completed === $this->status;
    }

    /**
     * Get the human-readable status label.
     */
    public function getStatusLabel(): string
    {
        return $this->status->getLabel();
    }

    /**
     * Get the reservation type label.
     */
    public function getReservationTypeLabel(): string
    {
        return $this->reservationType->getLabel();
    }

    #[Assert\Callback]
    public function validatePartySize(ExecutionContextInterface $context): void
    {
        $minSize = $this->reservationType->getMinPartySize();
        $maxSize = $this->reservationType->getMaxPartySize();

        if ($this->partySize < $minSize || $this->partySize > $maxSize) {
            $context
                ->buildViolation(
                    sprintf('%s requires between %d and %d guests',
                        $this->reservationType->getLabel(),
                        $minSize,
                        $maxSize
                    )
                )
                ->atPath('partySize')
                ->addViolation()
            ;
        }
    }
}
