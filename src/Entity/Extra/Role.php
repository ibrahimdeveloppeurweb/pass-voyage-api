<?php

namespace App\Entity\Extra;

use App\Entity\Admin\User;
use App\Repository\Extra\RoleRepository;
use App\Traits\SearchableTrait;
use App\Traits\UserObjectTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use App\Annotation\Searchable;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Role
{
    use SearchableTrait;
    use SoftDeleteableEntity;
    use UserObjectTrait;

    #[Groups(['role', 'user'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['role', 'user'])]
    #[Searchable()]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $nom;

    #[Groups(['role'])]
    #[ORM\Column(type: 'boolean')]
    private $isAdmin = false;

    #[Groups(['role', 'user'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    #[Groups(['role'])]
    #[ORM\ManyToMany(targetEntity: Path::class, inversedBy: 'roles')]
    #[ORM\JoinTable(name: 'role_path')]
    private $paths;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'droits')]
    private $users;

    #[Groups(['role'])]
    public function getUsersCount(): int
    {
        return $this->users ? $this->users->count() : 0;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isFirst;

    public function __construct()
    {
        $this->paths = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getIsAdmin(): ?bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    #[Groups(['role', 'user'])]
    public function getTitle(): string
    {
        return $this->nom ?? '';
    }

    #[Groups(['role', 'user'])]
    public function getDetail(): string
    {
        return $this->nom ?? '';
    }

    /**
     * @return Collection<int, Path>
     */
    public function getPaths(): Collection
    {
        return $this->paths;
    }

    public function addPath(Path $path): self
    {
        if (!$this->paths->contains($path)) {
            $this->paths->add($path);
        }

        return $this;
    }

    public function removePath(Path $path): self
    {
        $this->paths->removeElement($path);

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->users->removeElement($user);

        return $this;
    }

    public function getIsFirst(): ?bool
    {
        return $this->isFirst;
    }

    public function setIsFirst(?bool $isFirst): self
    {
        $this->isFirst = $isFirst;

        return $this;
    }
}