<?php

namespace App\Entity\Business;

use App\Repository\Business\PaymentRepository;
use App\Traits\EntityTrait;
use App\Traits\UserObjectTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Traits\SearchableTrait;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Payment
{
    use EntityTrait;
    use SearchableTrait;
    use UserObjectTrait;
    use SoftDeleteableEntity;
#[Groups(['payment:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['payment:read'])]
    #[ORM\ManyToOne(targetEntity: Credit::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $creditRequest;

    #[Groups(['payment:read'])]
    #[ORM\ManyToOne(targetEntity: Passenger::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $passenger;

    #[Groups(['payment:read'])]
    #[ORM\Column(type: 'integer')]
    private $amount;

    #[Groups(['payment:read'])]
    #[ORM\Column(type: 'string', length: 255)]
    private $paymentMethod;

    #[Groups(['payment:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $transactionId;

    #[Groups(['payment:read'])]
    #[ORM\Column(type: 'datetime')]
    private $paymentDate;

    #[Groups(['payment:read'])]
    #[ORM\Column(type: 'string', length: 255)]
    private $status = 'COMPLETED';

    public function getId(): ?int { return $this->id; }

    public function getCreditRequest(): ?CreditRequest { return $this->creditRequest; }
    public function setCreditRequest(?CreditRequest $creditRequest): self { $this->creditRequest = $creditRequest; return $this; }

    public function getPassenger(): ?Passenger { return $this->passenger; }
    public function setPassenger(?Passenger $passenger): self { $this->passenger = $passenger; return $this; }

    public function getAmount(): ?int { return $this->amount; }
    public function setAmount(int $amount): self { $this->amount = $amount; return $this; }

    public function getPaymentMethod(): ?string { return $this->paymentMethod; }
    public function setPaymentMethod(string $paymentMethod): self { $this->paymentMethod = $paymentMethod; return $this; }

    public function getTransactionId(): ?string { return $this->transactionId; }
    public function setTransactionId(?string $transactionId): self { $this->transactionId = $transactionId; return $this; }

    public function getPaymentDate(): ?\DateTimeInterface { return $this->paymentDate; }
    public function setPaymentDate(\DateTimeInterface $paymentDate): self { $this->paymentDate = $paymentDate; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getTitle(): string { return (string) ($this->id ?? 'Payment'); }
    public function getDetail(): string { return (string) ($this->id ?? 'Payment'); }
}