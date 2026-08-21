<?php

namespace App\Entity\Business;

use App\Repository\Business\PassengerContactRepository;
use App\Traits\EntityTrait;
use App\Traits\SearchableTrait;
use App\Traits\UserObjectTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PassengerContactRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class PassengerContact
{
    use EntityTrait;
    use SearchableTrait;
    use UserObjectTrait;
    use SoftDeleteableEntity;

    #[Groups(['passenger_contact:read', 'passenger:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['passenger_contact:read'])]
    #[ORM\ManyToOne(targetEntity: Passenger::class, inversedBy: 'contacts')]
    #[ORM\JoinColumn(name: 'passenger_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private $passenger;

    #[Groups(['passenger_contact:read', 'passenger:read'])]
    #[ORM\Column(name: 'contact_name', type: 'string', length: 255, nullable: true)]
    private $contactName;

    #[Groups(['passenger_contact:read', 'passenger:read'])]
    #[ORM\Column(name: 'phone_number', type: 'string', length: 50)]
    private $phoneNumber;

    public function __construct()
    {

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPassenger(): ?Passenger
    {
        return $this->passenger;
    }

    public function setPassenger(?Passenger $passenger): self
    {
        $this->passenger = $passenger;
        return $this;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(?string $contactName): self
    {
        $this->contactName = $contactName;
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

    public function getTitle(): string
    {
        return (string) ($this->contactName ?: $this->phoneNumber);
    }

    public function getDetail(): string
    {
        return (string) ($this->phoneNumber ?: $this->contactName);
    }
}
