<?php

namespace App\Entity\Business;

use App\Repository\Business\StationRepository;
use App\Traits\EntityTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Traits\UserObjectTrait;
use App\Traits\SearchableTrait;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: StationRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Station
{
    use EntityTrait;
    use SearchableTrait;
    use UserObjectTrait;
    use SoftDeleteableEntity;
    #[Groups(['station:read', 'agent:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['station:read', 'agent:read'])]
    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[Groups(['station:read', 'agent:read'])]
    #[ORM\ManyToOne(targetEntity: City::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $city;

    #[Groups(['station:read'])]
    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $company;

    #[Groups(['station:read'])]
    #[ORM\Column(type: 'boolean')]
    private $isActive = true;

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getCity(): ?City { return $this->city; }
    public function setCity(?City $city): self { $this->city = $city; return $this; }

    public function getCompany(): ?Company { return $this->company; }
    public function setCompany(?Company $company): self { $this->company = $company; return $this; }

    public function getIsActive(): ?bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }

    public function getTitle(): string { return (string) ($this->name ?? 'Station'); }
    public function getDetail(): string { return (string) ($this->name ?? 'Station'); }
}