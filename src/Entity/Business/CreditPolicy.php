<?php

namespace App\Entity\Business;

use App\Repository\Business\CreditPolicyRepository;
use App\Traits\EntityTrait;
use App\Traits\SearchableTrait;
use App\Traits\UserObjectTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CreditPolicyRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class CreditPolicy
{
    use EntityTrait;
    use SearchableTrait;
    use UserObjectTrait;
    use SoftDeleteableEntity;

    #[Groups(['creditpolicy:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['creditpolicy:read'])]
    #[ORM\Column(type: 'integer')]
    private $newUserLimit = 5000;

    #[Groups(['creditpolicy:read'])]
    #[ORM\Column(type: 'integer')]
    private $standardLimit = 10000;

    #[Groups(['creditpolicy:read'])]
    #[ORM\Column(type: 'integer')]
    private $vipLimit = 25000;

    #[Groups(['creditpolicy:read'])]
    #[ORM\Column(type: 'boolean')]
    private $autoApproveEnabled = true;

    #[Groups(['creditpolicy:read'])]
    #[ORM\Column(type: 'boolean')]
    private $autoRejectBlacklistEnabled = true;

    public function getId(): ?int { return $this->id; }

    public function getNewUserLimit(): ?int { return $this->newUserLimit; }
    public function setNewUserLimit(int $newUserLimit): self { $this->newUserLimit = $newUserLimit; return $this; }

    public function getStandardLimit(): ?int { return $this->standardLimit; }
    public function setStandardLimit(int $standardLimit): self { $this->standardLimit = $standardLimit; return $this; }

    public function getVipLimit(): ?int { return $this->vipLimit; }
    public function setVipLimit(int $vipLimit): self { $this->vipLimit = $vipLimit; return $this; }

    public function getAutoApproveEnabled(): ?bool { return $this->autoApproveEnabled; }
    public function setAutoApproveEnabled(bool $autoApproveEnabled): self { $this->autoApproveEnabled = $autoApproveEnabled; return $this; }

    public function getAutoRejectBlacklistEnabled(): ?bool { return $this->autoRejectBlacklistEnabled; }
    public function setAutoRejectBlacklistEnabled(bool $autoRejectBlacklistEnabled): self { $this->autoRejectBlacklistEnabled = $autoRejectBlacklistEnabled; return $this; }

    public function getTitle(): string { return "Politique de Crédit Voyage"; }
    public function getDetail(): string { return sprintf("Plafonds: %d / %d / %d", $this->newUserLimit, $this->standardLimit, $this->vipLimit); }
}
