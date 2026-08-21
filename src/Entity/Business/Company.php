<?php

namespace App\Entity\Business;

use App\Repository\Business\CompanyRepository;
use App\Traits\EntityTrait;
use App\Traits\SearchableTrait;
use App\Traits\UserObjectTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Company
{
    use EntityTrait;
    use SearchableTrait;
    use UserObjectTrait;
    use SoftDeleteableEntity;

    #[Groups(['company:read', 'admin', 'user', 'creditrequest:read', 'credit:read', 'ticket:read', 'agent:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['company:read', 'admin', 'user', 'creditrequest:read', 'credit:read', 'ticket:read', 'agent:read'])]
    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[Groups(['company:read', 'admin', 'user', 'creditrequest:read', 'credit:read', 'ticket:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $contactEmail;

    #[Groups(['company:read', 'admin', 'user', 'creditrequest:read', 'credit:read', 'ticket:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $contactPhone;

    #[Groups(['company:read', 'admin', 'user', 'creditrequest:read', 'credit:read', 'ticket:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $address;

    #[Groups(['company:read', 'admin', 'user', 'creditrequest:read', 'credit:read', 'ticket:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $status = 'Partenaire Actif';

    #[Groups(['company:read', 'admin', 'user', 'creditrequest:read', 'credit:read', 'ticket:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $logo;

    #[Groups(['company:read', 'admin', 'user', 'creditrequest:read', 'credit:read', 'ticket:read'])]
    #[ORM\Column(type: 'boolean')]
    private $isActive = true;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Credit::class)]
    private Collection $creditRequests;

    public function __construct()
    {
        $this->creditRequests = new ArrayCollection();
        $this->uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getContactEmail(): ?string { return $this->contactEmail; }
    public function setContactEmail(?string $contactEmail): self { $this->contactEmail = $contactEmail; return $this; }

    public function getContactPhone(): ?string { return $this->contactPhone; }
    public function setContactPhone(?string $contactPhone): self { $this->contactPhone = $contactPhone; return $this; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): self { $this->address = $address; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }

    public function getLogo(): ?string { return $this->logo; }
    public function setLogo(?string $logo): self { $this->logo = $logo; return $this; }

    public function getIsActive(): ?bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }

    public function getTitle(): string { return $this->name ?? ''; }
    public function getDetail(): string { return $this->name ?? ''; }
}
