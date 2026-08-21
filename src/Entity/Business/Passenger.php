<?php

namespace App\Entity\Business;

use App\Repository\Business\PassengerRepository;
use App\Traits\EntityTrait;
use App\Traits\SearchableTrait;
use App\Traits\UserObjectTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PassengerRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Passenger
{
    use EntityTrait;
    use SearchableTrait;
    use UserObjectTrait;
    use SoftDeleteableEntity;

    #[Groups(['passenger:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['passenger:read'])]
    private $firstname;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['passenger:read'])]
    private $lastname;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Groups(['passenger:read'])]
    private $phoneNumber;

    #[ORM\Column(type: 'string', length: 10)]
    #[Groups(['passenger:read'])]
    private $countryCode = '+225';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $pinCode;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Groups(['passenger:read'])]
    private $gender;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['passenger:read'])]
    private $residenceAddress;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['passenger:read'])]
    private $email;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['passenger:read'])]
    private $identityCardNumber;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['passenger:read'])]
    private $identityType;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['passenger:read'])]
    private $identityRectoUrl;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['passenger:read'])]
    private $identityVersoUrl;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['passenger:read'])]
    private $selfieUrl;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['passenger:read'])]
    private $identityStatus = 'NOT_SUBMITTED';

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['passenger:read'])]
    private $creditScore = 100;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['passenger:read'])]
    private $profileType = 'NEW_USER';

    #[ORM\Column(type: 'integer')]
    #[Groups(['passenger:read'])]
    private $maxCreditLimit = 200000;

    #[ORM\Column(type: 'integer')]
    #[Groups(['passenger:read'])]
    private $availableCredit = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['passenger:read'])]
    private $totalDebt = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['passenger:read'])]
    private $totalReimbursed = 0;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['passenger:read'])]
    private $isIdentified = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['passenger:read'])]
    private $isBlacklisted = false;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Groups(['passenger:read'])]
    private ?string $fcmToken = null;

    #[ORM\OneToMany(mappedBy: 'passenger', targetEntity: Credit::class, orphanRemoval: true)]
    private Collection $creditRequests;

    #[ORM\OneToMany(mappedBy: 'passenger', targetEntity: PassengerContact::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(['passenger:read'])]
    private Collection $contacts;

    public function __construct()
    {
        $this->creditRequests = new ArrayCollection();
        $this->contacts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    // Getters and Setters ...
    public function getFirstname(): ?string { return $this->firstname; }
    public function setFirstname(string $firstname): self { $this->firstname = $firstname; return $this; }

    public function getLastname(): ?string { return $this->lastname; }
    public function setLastname(string $lastname): self { $this->lastname = $lastname; return $this; }

    public function getPhoneNumber(): ?string { return $this->phoneNumber; }
    public function setPhoneNumber(string $phoneNumber): self { $this->phoneNumber = $phoneNumber; return $this; }

    public function getCountryCode(): ?string { return $this->countryCode; }
    public function setCountryCode(string $countryCode): self { $this->countryCode = $countryCode; return $this; }

    public function getPinCode(): ?string { return $this->pinCode; }
    public function setPinCode(?string $pinCode): self { $this->pinCode = $pinCode; return $this; }

    public function getGender(): ?string { return $this->gender; }
    public function setGender(?string $gender): self { $this->gender = $gender; return $this; }

    public function getResidenceAddress(): ?string { return $this->residenceAddress; }
    public function setResidenceAddress(?string $residenceAddress): self { $this->residenceAddress = $residenceAddress; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }

    public function getIdentityCardNumber(): ?string { return $this->identityCardNumber; }
    public function setIdentityCardNumber(?string $identityCardNumber): self { $this->identityCardNumber = $identityCardNumber; return $this; }

    public function getIdentityType(): ?string { return $this->identityType; }
    public function setIdentityType(?string $identityType): self { $this->identityType = $identityType; return $this; }

    public function getIdentityRectoUrl(): ?string { return $this->identityRectoUrl; }
    public function setIdentityRectoUrl(?string $identityRectoUrl): self { $this->identityRectoUrl = $identityRectoUrl; return $this; }

    public function getIdentityVersoUrl(): ?string { return $this->identityVersoUrl; }
    public function setIdentityVersoUrl(?string $identityVersoUrl): self { $this->identityVersoUrl = $identityVersoUrl; return $this; }

    public function getSelfieUrl(): ?string { return $this->selfieUrl; }
    public function setSelfieUrl(?string $selfieUrl): self { $this->selfieUrl = $selfieUrl; return $this; }

    public function getIdentityStatus(): ?string { return $this->identityStatus; }
    public function setIdentityStatus(string $identityStatus): self { $this->identityStatus = $identityStatus; return $this; }

    public function getCreditScore(): ?int { return $this->creditScore; }
    public function setCreditScore(?int $creditScore): self { $this->creditScore = $creditScore; return $this; }

    public function getProfileType(): ?string { return $this->profileType; }
    public function setProfileType(string $profileType): self { $this->profileType = $profileType; return $this; }

    public function getMaxCreditLimit(): ?int { return $this->maxCreditLimit; }
    public function setMaxCreditLimit(int $maxCreditLimit): self { $this->maxCreditLimit = $maxCreditLimit; return $this; }

    public function getAvailableCredit(): ?int { return $this->availableCredit; }
    public function setAvailableCredit(int $availableCredit): self { $this->availableCredit = $availableCredit; return $this; }

    public function getTotalDebt(): ?int { return $this->totalDebt; }
    public function setTotalDebt(int $totalDebt): self { $this->totalDebt = $totalDebt; return $this; }

    public function getTotalReimbursed(): ?int { return $this->totalReimbursed; }
    public function setTotalReimbursed(int $totalReimbursed): self { $this->totalReimbursed = $totalReimbursed; return $this; }

    public function getIsIdentified(): ?bool { return $this->isIdentified; }
    public function setIsIdentified(bool $isIdentified): self { $this->isIdentified = $isIdentified; return $this; }

    public function getIsBlacklisted(): ?bool { return $this->isBlacklisted; }
    public function setIsBlacklisted(bool $isBlacklisted): self { $this->isBlacklisted = $isBlacklisted; return $this; }

    public function getTitle(): string { return $this->firstname . ' ' . $this->lastname; }
    public function getDetail(): string { return $this->phoneNumber; }

    #[Groups(['passenger:read'])]
    public function getFullName(): string
    {
        return trim(($this->firstname ?? '') . ' ' . ($this->lastname ?? ''));
    }

    #[Groups(['passenger:read'])]
    public function getNom(): string
    {
        return $this->getFullName();
    }

    #[Groups(['passenger:read'])]
    public function getTelephone(): string
    {
        return $this->phoneNumber ?? '';
    }

    #[Groups(['passenger:read'])]
    public function getInscription(): string
    {
        return $this->getCreatedAt() ? $this->getCreatedAt()->format('d/m/Y H:i') : '';
    }

    #[Groups(['passenger:read'])]
    public function getSolde(): int
    {
        return $this->totalDebt ?? 0;
    }

    #[Groups(['passenger:read'])]
    public function getStatut(): string
    {
        if ($this->isBlacklisted) {
            return 'Bloqué';
        }
        return match(strtoupper($this->identityStatus ?? 'NOT_SUBMITTED')) {
            'VERIFIED', 'APPROVED', 'VALIDATED' => 'Vérifié',
            'PENDING', 'SUBMITTED' => 'En attente KYC',
            'REJECTED', 'REFUSED' => 'KYC Rejeté',
            default => 'Compte Créé',
        };
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

    /**
     * @return Collection<int, PassengerContact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(PassengerContact $contact): self
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
            $contact->setPassenger($this);
        }

        return $this;
    }

    public function removeContact(PassengerContact $contact): self
    {
        if ($this->contacts->removeElement($contact)) {
            if ($contact->getPassenger() === $this) {
                $contact->setPassenger(null);
            }
        }

        return $this;
    }

    public function getDaysOverdue(int $delaiDays = 14): int
    {
        if (($this->totalDebt ?? 0) <= 0) {
            return 0;
        }

        $now = new \DateTime();
        $maxOverdueDays = 0;

        if ($this->creditRequests) {
            foreach ($this->creditRequests as $credit) {
                $status = strtoupper(trim((string)$credit->getStatus()));
                if (in_array($status, ['APPROVED', 'VALIDE', 'VALIDATED'])) {
                    $toRepay = method_exists($credit, 'getAmountToRepay') ? $credit->getAmountToRepay() : ($credit->getAmountRequested() ?: $credit->getTotalAmount());
                    $rem = max(0, (int)$toRepay - (int)$credit->getRepaidAmount());
                    if ($rem > 0) {
                        $startDate = $credit->getCreatedAt() ?? $credit->getTravelDate();
                        if ($startDate) {
                            $dueDate = (clone $startDate)->modify("+{$delaiDays} days");
                            if ($now > $dueDate) {
                                $diff = (int)$now->diff($dueDate)->format('%a');
                                if ($diff > $maxOverdueDays) {
                                    $maxOverdueDays = $diff;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $maxOverdueDays;
    }

    #[Groups(['passenger:read'])]
    public function getIsOverdue(): bool
    {
        return $this->getDaysOverdue() > 0;
    }
}
