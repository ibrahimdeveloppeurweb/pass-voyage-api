<?php

namespace App\Entity\Business;

use App\Repository\Business\TariffRepository;
use App\Traits\EntityTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Traits\UserObjectTrait;
use App\Traits\SearchableTrait;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TariffRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Tariff
{
    use EntityTrait;
    use SearchableTrait;
    use UserObjectTrait;
    use SoftDeleteableEntity;
#[Groups(['tariff:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['tariff:read'])]
    #[ORM\ManyToOne(targetEntity: Route::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $route;

    #[Groups(['tariff:read'])]
    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $company;

    #[Groups(['tariff:read'])]
    #[ORM\Column(type: 'integer')]
    private $price;

    #[Groups(['tariff:read'])]
    #[ORM\Column(type: 'boolean')]
    private $isActive = true;

    public function getId(): ?int { return $this->id; }

    public function getRoute(): ?Route { return $this->route; }
    public function setRoute(?Route $route): self { $this->route = $route; return $this; }

    public function getCompany(): ?Company { return $this->company; }
    public function setCompany(?Company $company): self { $this->company = $company; return $this; }

    public function getPrice(): ?int { return $this->price; }
    public function setPrice(int $price): self { $this->price = $price; return $this; }

    public function getIsActive(): ?bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }

    public function getTitle(): string {
        $routeName = $this->route ? $this->route->getTitle() : 'Trajet';
        return "$routeName - " . number_format($this->price ?? 0, 0, '', ' ') . ' XOF';
    }
    public function getDetail(): string { return $this->getTitle(); }
}