<?php

namespace App\Entity\Business;

use App\Repository\Business\CompanyFundRepository;
use App\Traits\EntityTrait;
use App\Traits\SearchableTrait;
use App\Traits\UserObjectTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

#[ORM\Entity(repositoryClass: CompanyFundRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class CompanyFund
{
    use EntityTrait;
    use SearchableTrait;
    use UserObjectTrait;
    use SoftDeleteableEntity;

    #[Groups(['company_fund:read', 'company:read', 'admin', 'user'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['company_fund:read', 'company:read', 'admin', 'user'])]
    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[Groups(['company_fund:read', 'company:read', 'admin', 'user'])]
    #[ORM\Column(type: 'float')]
    private float $totalAmount = 0.0;

    #[Groups(['company_fund:read', 'company:read', 'admin', 'user'])]
    #[ORM\Column(type: 'float')]
    private float $consumedAmount = 0.0;

    public function __construct()
    {
        $this->uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
    }

    public function getId(): ?int { return $this->id; }

    public function getCompany(): ?Company { return $this->company; }
    public function setCompany(?Company $company): self { $this->company = $company; return $this; }

    public function getTotalAmount(): float { return $this->totalAmount; }
    public function setTotalAmount(float $totalAmount): self { $this->totalAmount = $totalAmount; return $this; }

    public function getConsumedAmount(): float { return $this->consumedAmount; }
    public function setConsumedAmount(float $consumedAmount): self { $this->consumedAmount = $consumedAmount; return $this; }

    #[Groups(['company_fund:read', 'company:read', 'admin', 'user'])]
    public function getRemainingAmount(): float
    {
        return max(0, $this->totalAmount - $this->consumedAmount);
    }

    #[Groups(['company_fund:read', 'company:read', 'admin', 'user'])]
    public function getPercentage(): float
    {
        if ($this->totalAmount <= 0) return 0.0;
        return round(($this->consumedAmount / $this->totalAmount) * 100, 1);
    }

    #[Groups(['company_fund:read', 'company:read', 'admin', 'user'])]
    public function getStatus(): string
    {
        return $this->getPercentage() >= 80 ? 'Critique' : 'Normal';
    }

    #[Groups(['company_fund:read', 'company:read', 'admin', 'user'])]
    public function getCompanyName(): string
    {
        return $this->company ? ($this->company->getName() ?? '') : '';
    }

    public function getTitle(): string { return $this->getCompanyName() . ' - ' . $this->totalAmount; }
    public function getDetail(): string { return $this->getCompanyName() . ' - ' . $this->totalAmount; }
}
