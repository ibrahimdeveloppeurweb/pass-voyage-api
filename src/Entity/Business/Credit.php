<?php

namespace App\Entity\Business;

use App\Repository\Business\CreditRepository;
use App\Traits\EntityTrait;
use App\Traits\SearchableTrait;
use App\Traits\UserObjectTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CreditRepository::class)]
#[ORM\Table(name: 'credit_request')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Credit
{
    use EntityTrait;
    use SearchableTrait;
    use UserObjectTrait;
    use SoftDeleteableEntity;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\ManyToOne(targetEntity: Passenger::class, inversedBy: 'creditRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private $passenger;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\ManyToOne(targetEntity: Route::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $route;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'creditRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private $company;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $departureCity;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $arrivalCity;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\Column(type: 'integer')]
    private $unitPrice = 0;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\Column(type: 'integer')]
    private $amountRequested = 0;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\Column(type: 'integer')]
    private $serviceFee = 0;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\Column(type: 'integer')]
    private $totalAmount = 0;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $travelDate;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $returnDate;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\Column(type: 'boolean')]
    private $isRoundTrip = false;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\Column(type: 'integer')]
    private $passengerCount = 1;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\Column(type: 'string', length: 255)]
    private $status = 'PENDING';

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\Column(type: 'string', length: 255)]
    private $repaymentStatus = 'NOT_PAID';

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\Column(type: 'integer')]
    private $repaidAmount = 0;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $rejectionReason;

    #[Groups(['creditrequest:read', 'credit:read'])]
    #[ORM\OneToMany(mappedBy: 'creditRequest', targetEntity: Ticket::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $tickets;

    public function __construct()
    {
        $this->tickets = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getPassenger(): ?Passenger { return $this->passenger; }
    public function setPassenger(?Passenger $passenger): self { $this->passenger = $passenger; return $this; }

    public function getRoute(): ?Route { return $this->route; }
    public function setRoute(?Route $route): self { $this->route = $route; return $this; }

    public function getCompany(): ?Company { return $this->company; }
    public function setCompany(?Company $company): self { $this->company = $company; return $this; }

    public function getDepartureCity(): ?string { return $this->departureCity; }
    public function setDepartureCity(?string $departureCity): self { $this->departureCity = $departureCity; return $this; }

    public function getArrivalCity(): ?string { return $this->arrivalCity; }
    public function setArrivalCity(?string $arrivalCity): self { $this->arrivalCity = $arrivalCity; return $this; }

    public function getUnitPrice(): ?int { return $this->unitPrice; }
    public function setUnitPrice(int $unitPrice): self { $this->unitPrice = $unitPrice; return $this; }

    public function getAmountRequested(): ?int { return $this->amountRequested; }
    public function setAmountRequested(int $amountRequested): self { $this->amountRequested = $amountRequested; return $this; }

    public function getServiceFee(): ?int { return $this->serviceFee; }
    public function setServiceFee(int $serviceFee): self { $this->serviceFee = $serviceFee; return $this; }

    public function getTotalAmount(): ?int { return $this->totalAmount; }
    public function setTotalAmount(int $totalAmount): self { $this->totalAmount = $totalAmount; return $this; }

    public function getTravelDate(): ?\DateTimeInterface { return $this->travelDate; }
    public function setTravelDate(?\DateTimeInterface $travelDate): self { $this->travelDate = $travelDate; return $this; }

    public function getReturnDate(): ?\DateTimeInterface { return $this->returnDate; }
    public function setReturnDate(?\DateTimeInterface $returnDate): self { $this->returnDate = $returnDate; return $this; }

    public function getIsRoundTrip(): ?bool { return $this->isRoundTrip; }
    public function setIsRoundTrip(bool $isRoundTrip): self { $this->isRoundTrip = $isRoundTrip; return $this; }

    public function getPassengerCount(): ?int { return $this->passengerCount; }
    public function setPassengerCount(int $passengerCount): self { $this->passengerCount = $passengerCount; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getRepaymentStatus(): ?string { return $this->repaymentStatus; }
    public function setRepaymentStatus(string $repaymentStatus): self { $this->repaymentStatus = $repaymentStatus; return $this; }

    public function getRepaidAmount(): int { return (int) ($this->repaidAmount ?? 0); }
    public function setRepaidAmount(int $repaidAmount): self { $this->repaidAmount = $repaidAmount; return $this; }

    public function getAmountToRepay(): int
    {
        if ($this->amountRequested > 0) {
            return (int) $this->amountRequested;
        }
        $total = (int) ($this->totalAmount ?? 0);
        $fee = (int) ($this->serviceFee ?? 0);
        return max(0, $total - $fee);
    }

    public function getRefundStatus(): string
    {
        $toRepay = $this->getAmountToRepay();
        if ($this->repaymentStatus === 'FULLY_REIMBURSED' || ($toRepay > 0 && $this->repaidAmount >= $toRepay)) {
            return 'Remboursé';
        }
        if ($this->repaymentStatus === 'PARTIALLY_REIMBURSED' || $this->repaidAmount > 0) {
            return 'Partiellement remboursé';
        }
        return 'Non remboursé';
    }

    public function getRejectionReason(): ?string { return $this->rejectionReason; }
    public function setRejectionReason(?string $rejectionReason): self { $this->rejectionReason = $rejectionReason; return $this; }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection { return $this->tickets; }

    public function addTicket(Ticket $ticket): self
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets[] = $ticket;
            $ticket->setCreditRequest($this);
        }
        return $this;
    }

    public function removeTicket(Ticket $ticket): self
    {
        if ($this->tickets->removeElement($ticket)) {
            if ($ticket->getCreditRequest() === $this) {
                $ticket->setCreditRequest(null);
            }
        }
        return $this;
    }

    #[Groups(['creditrequest:read', 'credit:read'])]
    public function getDepartureCompany(): ?string
    {
        return $this->company ? $this->company->getName() : null;
    }

    #[Groups(['creditrequest:read', 'credit:read'])]
    public function getCompanyName(): ?string
    {
        return $this->company ? $this->company->getName() : null;
    }

    public function getTitle(): string { return (string) ($this->id ?? 'Credit'); }
    public function getDetail(): string { return (string) ($this->status ?? 'PENDING'); }
}

if (!class_exists('App\Entity\Business\CreditRequest', false)) {
    class_alias(Credit::class, 'App\Entity\Business\CreditRequest');
}
