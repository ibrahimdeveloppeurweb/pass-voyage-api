<?php

namespace App\Entity\Business;

use App\Repository\Business\CompanyInvoiceRepository;
use App\Traits\EntityTrait;
use App\Traits\SearchableTrait;
use App\Traits\UserObjectTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

#[ORM\Entity(repositoryClass: CompanyInvoiceRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class CompanyInvoice
{
    use EntityTrait;
    use SearchableTrait;
    use UserObjectTrait;
    use SoftDeleteableEntity;

    #[Groups(['company_invoice:read', 'company:read', 'admin', 'user'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['company_invoice:read', 'company:read', 'admin', 'user'])]
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $reference = null;

    #[Groups(['company_invoice:read', 'company:read', 'admin', 'user'])]
    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[Groups(['company_invoice:read', 'company:read', 'admin', 'user'])]
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $period = null;

    #[Groups(['company_invoice:read', 'company:read', 'admin', 'user'])]
    #[ORM\Column(type: 'float')]
    private float $amount = 0.0;

    #[Groups(['company_invoice:read', 'company:read', 'admin', 'user'])]
    #[ORM\Column(type: 'string', length: 50)]
    private string $status = 'En attente';

    #[Groups(['company_invoice:read', 'company:read', 'admin', 'user'])]
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $paidAt = null;

    public function __construct()
    {
        $this->uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
    }

    public function getId(): ?int { return $this->id; }

    public function getReference(): ?string { return $this->reference; }
    public function setReference(string $reference): self { $this->reference = $reference; return $this; }

    public function getCompany(): ?Company { return $this->company; }
    public function setCompany(?Company $company): self { $this->company = $company; return $this; }

    public function getPeriod(): ?string { return $this->period; }
    public function setPeriod(string $period): self { $this->period = $period; return $this; }

    public function getAmount(): float { return $this->amount; }
    public function setAmount(float $amount): self { $this->amount = $amount; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getPaidAt(): ?\DateTimeInterface { return $this->paidAt; }
    public function setPaidAt(?\DateTimeInterface $paidAt): self { $this->paidAt = $paidAt; return $this; }

    #[Groups(['company_invoice:read', 'company:read', 'admin', 'user'])]
    public function getCompanyName(): string
    {
        return $this->company ? ($this->company->getName() ?? '') : '';
    }

    public function getTitle(): string { return $this->reference ?? ''; }
    public function getDetail(): string { return ($this->reference ?? '') . ' - ' . $this->getCompanyName(); }
}
