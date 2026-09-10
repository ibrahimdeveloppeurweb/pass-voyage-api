<?php

namespace App\Entity\Business;

use App\Repository\Business\TicketRepository;
use App\Traits\EntityTrait;
use App\Traits\SearchableTrait;
use App\Traits\UserObjectTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Ticket
{
    use EntityTrait;
    use SearchableTrait;
    use UserObjectTrait;
    use SoftDeleteableEntity;

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Credit::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    private $creditRequest;

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $company;

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: true)]
    private $ticketNumber;

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\Column(type: 'integer')]
    private $ticketIndex = 1;

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\Column(type: 'integer')]
    private $unitPrice = 0;

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $seatNumber;

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $qrCodeContent;

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\Column(type: 'string', length: 255)]
    private $status = 'PENDING';

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\Column(type: 'boolean')]
    private $isUsed = false;

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $expirationDate;


    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $validatedAt;

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $usedAt;
    
    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $passengerPhoto;

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $verificationContact;

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $departureTime;

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private $physicalTicketNumber;

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $refusalComment;

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\ManyToOne(targetEntity: Agent::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $validatedByAgent;

    #[Groups(['ticket:read', 'creditrequest:read'])]
    #[ORM\ManyToOne(targetEntity: Station::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $validatedAtStation;

    public function getId(): ?int { return $this->id; }

    public function getCreditRequest(): ?CreditRequest { return $this->creditRequest; }
    public function setCreditRequest(?CreditRequest $creditRequest): self { $this->creditRequest = $creditRequest; return $this; }

    public function getCompany(): ?Company { return $this->company; }
    public function setCompany(?Company $company): self { $this->company = $company; return $this; }

    public function getTicketNumber(): ?string { return $this->ticketNumber; }
    public function setTicketNumber(?string $ticketNumber): self { $this->ticketNumber = $ticketNumber; return $this; }

    public function getTicketIndex(): ?int { return $this->ticketIndex; }
    public function setTicketIndex(int $ticketIndex): self { $this->ticketIndex = $ticketIndex; return $this; }

    public function getUnitPrice(): ?int { return $this->unitPrice; }
    public function setUnitPrice(int $unitPrice): self { $this->unitPrice = $unitPrice; return $this; }

    public function getSeatNumber(): ?string { return $this->seatNumber; }
    public function setSeatNumber(?string $seatNumber): self { $this->seatNumber = $seatNumber; return $this; }

    public function getQrCodeContent(): ?string { return $this->qrCodeContent; }
    public function setQrCodeContent(?string $qrCodeContent): self { $this->qrCodeContent = $qrCodeContent; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getIsUsed(): ?bool { return $this->isUsed; }
    public function setIsUsed(bool $isUsed): self { $this->isUsed = $isUsed; return $this; }

    public function getExpirationDate(): ?\DateTimeInterface { return $this->expirationDate; }
    public function setExpirationDate(?\DateTimeInterface $expirationDate): self { $this->expirationDate = $expirationDate; return $this; }


    public function getValidatedAt(): ?\DateTimeInterface { return $this->validatedAt; }
    public function setValidatedAt(?\DateTimeInterface $validatedAt): self { $this->validatedAt = $validatedAt; return $this; }

    public function getUsedAt(): ?\DateTimeInterface { return $this->usedAt; }
    public function setUsedAt(?\DateTimeInterface $usedAt): self { $this->usedAt = $usedAt; return $this; }

    public function getDepartureTime(): ?string { return $this->departureTime; }
    public function setDepartureTime(?string $departureTime): self { $this->departureTime = $departureTime; return $this; }

    public function getPassengerPhoto(): ?string { return $this->passengerPhoto; }
    public function setPassengerPhoto(?string $passengerPhoto): self { $this->passengerPhoto = $passengerPhoto; return $this; }

    public function getVerificationContact(): ?string { return $this->verificationContact; }
    public function setVerificationContact(?string $verificationContact): self { $this->verificationContact = $verificationContact; return $this; }

    public function getPhysicalTicketNumber(): ?string { return $this->physicalTicketNumber; }
    public function setPhysicalTicketNumber(?string $physicalTicketNumber): self { $this->physicalTicketNumber = $physicalTicketNumber; return $this; }

    public function getRefusalComment(): ?string { return $this->refusalComment; }
    public function setRefusalComment(?string $refusalComment): self { $this->refusalComment = $refusalComment; return $this; }

    public function getValidatedByAgent(): ?Agent { return $this->validatedByAgent; }
    public function setValidatedByAgent(?Agent $validatedByAgent): self { $this->validatedByAgent = $validatedByAgent; return $this; }

    public function getValidatedAtStation(): ?Station { return $this->validatedAtStation; }
    public function setValidatedAtStation(?Station $validatedAtStation): self { $this->validatedAtStation = $validatedAtStation; return $this; }

    #[Groups(['ticket:read', 'creditrequest:read'])]
    public function getValidationDate(): ?string
    {
        return $this->validatedAt ? $this->validatedAt->format('d/m/Y H:i') : null;
    }

    public function getTitle(): string { return (string) ($this->ticketNumber ?? 'Ticket'); }
    public function getDetail(): string { return (string) ($this->status ?? 'PENDING'); }
}