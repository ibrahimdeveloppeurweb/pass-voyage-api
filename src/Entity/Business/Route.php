<?php

namespace App\Entity\Business;

use App\Repository\Business\RouteRepository;
use App\Traits\EntityTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Traits\UserObjectTrait;
use App\Traits\SearchableTrait;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RouteRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Route
{
    use EntityTrait;
    use SearchableTrait;
    use UserObjectTrait;
    use SoftDeleteableEntity;
    #[Groups(['route:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['route:read'])]
    #[ORM\ManyToOne(targetEntity: City::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $departureCity;

    #[Groups(['route:read'])]
    #[ORM\ManyToOne(targetEntity: City::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $arrivalCity;

    #[Groups(['route:read'])]
    #[ORM\ManyToOne(targetEntity: Station::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $departureStation;

    #[Groups(['route:read'])]
    #[ORM\ManyToOne(targetEntity: Station::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $arrivalStation;

    #[Groups(['route:read'])]
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private $distance;

    #[Groups(['route:read'])]
    #[ORM\Column(type: 'boolean')]
    private $isActive = true;

    public function getId(): ?int { return $this->id; }

    public function getDepartureCity(): ?City { return $this->departureCity; }
    public function setDepartureCity(?City $departureCity): self { $this->departureCity = $departureCity; return $this; }

    public function getArrivalCity(): ?City { return $this->arrivalCity; }
    public function setArrivalCity(?City $arrivalCity): self { $this->arrivalCity = $arrivalCity; return $this; }

    public function getDepartureStation(): ?Station { return $this->departureStation; }
    public function setDepartureStation(?Station $departureStation): self { $this->departureStation = $departureStation; return $this; }

    public function getArrivalStation(): ?Station { return $this->arrivalStation; }
    public function setArrivalStation(?Station $arrivalStation): self { $this->arrivalStation = $arrivalStation; return $this; }

    public function getDistance(): ?string { return $this->distance; }
    public function setDistance(?string $distance): self { $this->distance = $distance; return $this; }

    public function getIsActive(): ?bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }

    public function getTitle(): string {
        $dep = $this->departureCity ? $this->departureCity->getName() : ($this->departureStation ? $this->departureStation->getName() : 'Départ');
        $arr = $this->arrivalCity ? $this->arrivalCity->getName() : ($this->arrivalStation ? $this->arrivalStation->getName() : 'Arrivée');
        return "$dep - $arr";
    }

    public function getDetail(): string { return $this->getTitle(); }
}