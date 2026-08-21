<?php

namespace App\Entity\Business;

use App\Repository\Business\AgentRepository;
use App\Traits\EntityTrait;
use App\Traits\SearchableTrait;
use App\Traits\UserObjectTrait;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\Admin\User;

#[ORM\Entity(repositoryClass: AgentRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Agent
{
    use EntityTrait;
    use SearchableTrait;
    use UserObjectTrait;
    use SoftDeleteableEntity;

    #[Groups(['agent:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['agent:read'])]
    private $firstname;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['agent:read'])]
    private $lastname;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Groups(['agent:read'])]
    private $phoneNumber;

    #[ORM\Column(type: 'string', length: 10)]
    #[Groups(['agent:read'])]
    private $countryCode = '+225';

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Groups(['agent:read'])]
    private $gender;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['agent:read'])]
    private $residenceAddress;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['agent:read'])]
    private $company;

    #[ORM\ManyToOne(targetEntity: Station::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['agent:read'])]
    private $stationAssigned;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['agent:read'])]
    private $isActivated = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['agent:read'])]
    private $isActive = true;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['agent:read'])]
    private $status = 'PENDING';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['agent:read'])]
    private $fcmToken;

    #[ORM\Column(type: 'string', length: 50, nullable: true, unique: true)]
    #[Groups(['agent:read'])]
    private $agentCode;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['agent:read'])]
    private $assignmentDate;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Groups(['agent:read'])]
    private $shiftStart = '08:00';

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Groups(['agent:read'])]
    private $shiftEnd = '17:00';

    #[ORM\OneToOne(mappedBy: 'agent', targetEntity: User::class)]
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    public function getResidenceAddress(): ?string
    {
        return $this->residenceAddress;
    }

    public function setResidenceAddress(?string $residenceAddress): self
    {
        $this->residenceAddress = $residenceAddress;
        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    public function getStationAssigned(): ?Station
    {
        return $this->stationAssigned;
    }

    public function setStationAssigned(?Station $stationAssigned): self
    {
        $this->stationAssigned = $stationAssigned;
        return $this;
    }

    public function getStation(): ?Station
    {
        return $this->stationAssigned;
    }

    public function setStation(?Station $station): self
    {
        $this->stationAssigned = $station;
        return $this;
    }

    public function getIsActivated(): ?bool
    {
        return $this->isActivated;
    }

    public function setIsActivated(bool $isActivated): self
    {
        $this->isActivated = $isActivated;
        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status ?? 'PENDING';
    }

    public function setStatus(?string $status): self
    {
        // Le statut de validation est definitif : si deja APPROVED, il reste APPROVED
        if ($this->status === 'APPROVED' && $status === 'PENDING') {
            return $this;
        }
        $this->status = $status;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getFcmToken(): ?string
    {
        return $this->fcmToken;
    }

    public function setFcmToken(?string $fcmToken): self
    {
        $this->fcmToken = $fcmToken;
        return $this;
    }

    public function getAgentCode(): ?string
    {
        return $this->agentCode;
    }

    public function setAgentCode(?string $agentCode): self
    {
        // Une fois attribue, le code commercial et son QR code ne changent plus jamais
        if ($this->agentCode !== null && $this->agentCode !== '' && $agentCode !== null && $agentCode !== '') {
            return $this;
        }
        $this->agentCode = $agentCode;
        return $this;
    }

    public function getAssignmentDate(): ?string
    {
        return $this->assignmentDate;
    }

    public function setAssignmentDate(?string $assignmentDate): self
    {
        $this->assignmentDate = $assignmentDate;
        return $this;
    }

    public function getShiftStart(): ?string
    {
        return $this->shiftStart;
    }

    public function setShiftStart(?string $shiftStart): self
    {
        $this->shiftStart = $shiftStart;
        return $this;
    }

    public function getShiftEnd(): ?string
    {
        return $this->shiftEnd;
    }

    public function setShiftEnd(?string $shiftEnd): self
    {
        $this->shiftEnd = $shiftEnd;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function getDetail(): string
    {
        return $this->phoneNumber;
    }
}
