<?php

namespace App\Entity\Business;

use App\Repository\Business\CompanyFundHistoryRepository;
use App\Traits\EntityTrait;
use App\Traits\SearchableTrait;
use App\Traits\UserObjectTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

#[ORM\Entity(repositoryClass: CompanyFundHistoryRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class CompanyFundHistory
{
    use EntityTrait;
    use SearchableTrait;
    use UserObjectTrait;
    use SoftDeleteableEntity;

    #[Groups(['company_fund_history:read', 'company_fund:read', 'company:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['company_fund_history:read', 'company_fund:read'])]
    #[ORM\ManyToOne(targetEntity: CompanyFund::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?CompanyFund $companyFund = null;

    #[Groups(['company_fund_history:read', 'company_fund:read'])]
    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Company $company = null;

    #[Groups(['company_fund_history:read', 'company_fund:read'])]
    #[ORM\Column(type: 'string', length: 50)]
    private string $type = 'RECHARGE'; // RECHARGE or DEBIT_BILLET

    #[Groups(['company_fund_history:read', 'company_fund:read'])]
    #[ORM\Column(type: 'float')]
    private float $amount = 0.0;

    #[Groups(['company_fund_history:read', 'company_fund:read'])]
    #[ORM\Column(type: 'float')]
    private float $previousBalance = 0.0;

    #[Groups(['company_fund_history:read', 'company_fund:read'])]
    #[ORM\Column(type: 'float')]
    private float $newBalance = 0.0;

    #[Groups(['company_fund_history:read', 'company_fund:read'])]
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $reference = null;

    #[Groups(['company_fund_history:read', 'company_fund:read'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[Groups(['company_fund_history:read', 'company_fund:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $performedBy = null;

    #[Groups(['company_fund_history:read', 'company_fund:read'])]
    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Ticket $ticket = null;

    public function __construct()
    {
        $this->setUuid(\Ramsey\Uuid\Uuid::uuid4()->toString());
        $this->setCreatedAt(new \DateTime());
    }

    public function getId(): ?int { return $this->id; }

    public function getCompanyFund(): ?CompanyFund { return $this->companyFund; }
    public function setCompanyFund(?CompanyFund $companyFund): self { $this->companyFund = $companyFund; return $this; }

    public function getCompany(): ?Company { return $this->company; }
    public function setCompany(?Company $company): self { $this->company = $company; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }

    public function getAmount(): float { return $this->amount; }
    public function setAmount(float $amount): self { $this->amount = $amount; return $this; }

    public function getPreviousBalance(): float { return $this->previousBalance; }
    public function setPreviousBalance(float $previousBalance): self { $this->previousBalance = $previousBalance; return $this; }

    public function getNewBalance(): float { return $this->newBalance; }
    public function setNewBalance(float $newBalance): self { $this->newBalance = $newBalance; return $this; }

    public function getReference(): ?string { return $this->reference; }
    public function setReference(?string $reference): self { $this->reference = $reference; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getPerformedBy(): ?string { return $this->performedBy; }
    public function setPerformedBy(?string $performedBy): self { $this->performedBy = $performedBy; return $this; }

    public function getTicket(): ?Ticket { return $this->ticket; }
    public function setTicket(?Ticket $ticket): self { $this->ticket = $ticket; return $this; }

    public function getTitle(): string { return $this->type . ' - ' . $this->amount; }
    public function getDetail(): string { return $this->description ?? ''; }
}
