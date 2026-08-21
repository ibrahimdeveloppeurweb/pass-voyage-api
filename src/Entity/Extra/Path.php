<?php

namespace App\Entity\Extra;

use App\Repository\Extra\PathRepository;
use App\Traits\UserObjectTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PathRepository::class)]
class Path
{
    use UserObjectTrait;

    const TYPE = [
        'CLIENT' => 'CLIENT',
        'PARTNER' => 'PARTNER',
        'COMPANY' => 'COMPANY',
        'EXTRA' => 'EXTRA',
        'AUTH' => 'AUTH',
        'ADMIN' => 'ADMIN'
    ];

    #[Groups(['path', 'role'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['path', 'role'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $chemin;

    #[Groups(['path', 'role'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $libelle;

    #[Groups(['path', 'role'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $nom;

    #[Groups(['path', 'role'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $type = Path::TYPE['CLIENT'];

    #[Groups(['path', 'role'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $permission;

    #[ORM\ManyToMany(targetEntity: Role::class, mappedBy: 'paths')]
    private $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChemin(): ?string
    {
        return $this->chemin;
    }

    public function setChemin(?string $chemin): self
    {
        $this->chemin = $chemin;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function setPermission(?string $permission): self
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }

        return $this;
    }

    public function removeRole(Role $role): self
    {
        $this->roles->removeElement($role);

        return $this;
    }
}