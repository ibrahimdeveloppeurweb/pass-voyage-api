<?php

namespace App\Entity\Admin;

use App\Entity\Extra\Role;
use App\Repository\Admin\UserRepository;
use App\Traits\SearchableTrait;
use App\Traits\UserObjectTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use SearchableTrait;
    use SoftDeleteableEntity;
    use UserObjectTrait;

    const TYPE = [
        'ADMIN' => 'ADMIN',
        'AGENT' => 'AGENT',
        'PASSENGER' => 'PASSENGER',
    ];

    #[Groups(['user', 'admin', 'passenger:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['user', 'admin', 'passenger:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $nom;

    #[Groups(['user', 'admin', 'passenger:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $prenom;

    #[Groups(['user', 'admin', 'passenger:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $telephone;

    #[Groups(['user', 'admin', 'passenger:read'])]
    #[ORM\Column(type: 'string', length: 255)]
    private $type = self::TYPE['ADMIN'];

    #[Groups(['user', 'admin', 'passenger:read'])]
    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private $username;

    #[Groups(['user', 'admin', 'passenger:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $email;

    #[Groups(['user', 'admin'])]
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private $fcmToken;

    #[ORM\Column(type: 'string', length: 255)]
    private $password;

    #[Groups(['user', 'admin'])]
    #[ORM\Column(type: 'boolean')]
    private $isFirst = false;

    #[Groups(['user', 'admin'])]
    #[ORM\Column(type: 'boolean')]
    private $isEnabled = true;

    #[Groups(['user', 'admin'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $avatar;

    #[Groups(['user', 'admin'])]
    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[Groups(['user', 'admin'])]
    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    private $droits;

    #[Groups(['user', 'admin'])]
    #[ORM\ManyToOne(targetEntity: \App\Entity\Business\Station::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $station;

    #[Groups(['user', 'admin'])]
    #[ORM\ManyToOne(targetEntity: \App\Entity\Business\Company::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $company;

    #[ORM\OneToOne(targetEntity: \App\Entity\Business\Passenger::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private $passenger;

    #[ORM\OneToOne(inversedBy: 'user', targetEntity: \App\Entity\Business\Agent::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private $agent;

    public function __construct()
    {
        $this->droits = new ArrayCollection();

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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    #[Groups(['user', 'admin', 'passenger:read'])]
    public function getFullName(): string
    {
        return trim(($this->nom ?? '') . ' ' . ($this->prenom ?? ''));
    }

    #[Groups(['user', 'admin', 'passenger:read'])]
    public function getLibelle(): string
    {
        $fullName = $this->getFullName();
        return !empty($fullName) ? $fullName : ($this->username ?? '');
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getFcmToken(): ?string
    {
        return $this->fcmToken;
    }

    public function setFcmToken(?string $fcmToken): self
    {
        $this->fcmToken = $fcmToken;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getIsFirst(): ?bool
    {
        return $this->isFirst;
    }

    public function setIsFirst(bool $isFirst): self
    {
        $this->isFirst = $isFirst;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled ?? true;
    }

    public function getIsEnabled(): bool
    {
        return $this->isEnabled ?? true;
    }

    public function setIsEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getDroits(): Collection
    {
        return $this->droits;
    }

    public function addDroit(Role $droit): self
    {
        if (!$this->droits->contains($droit)) {
            $this->droits->add($droit);
        }

        return $this;
    }

    public function removeDroit(Role $droit): self
    {
        $this->droits->removeElement($droit);

        return $this;
    }

    public function getStation(): ?\App\Entity\Business\Station
    {
        return $this->station;
    }

    public function setStation(?\App\Entity\Business\Station $station): self
    {
        $this->station = $station;

        return $this;
    }

    public function getCompany(): ?\App\Entity\Business\Company
    {
        return $this->company;
    }

    public function setCompany(?\App\Entity\Business\Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    #[Groups(['user', 'admin'])]
    public function getCompanyUuid(): ?string
    {
        return $this->company ? $this->company->getUuid() : null;
    }

    public function getPassenger(): ?\App\Entity\Business\Passenger
    {
        return $this->passenger;
    }

    public function setPassenger(?\App\Entity\Business\Passenger $passenger): self
    {
        $this->passenger = $passenger;

        return $this;
    }

    public function getAgent(): ?\App\Entity\Business\Agent
    {
        return $this->agent;
    }

    public function setAgent(?\App\Entity\Business\Agent $agent): self
    {
        $this->agent = $agent;

        return $this;
    }

    #[Groups(['user', 'admin'])]
    public function getTitle(): string
    {
        return $this->getFullName();
    }

    #[Groups(['user', 'admin'])]
    public function getDetail(): string
    {
        return $this->email ?? '';
    }

    #[Groups(['user', 'admin'])]
    public function getPermissions(): array
    {
        $droits = $this->getDroits() ?: [];
        $permissions = [];
        foreach ($droits as $droit) {
            if ($droit->getIsAdmin() || stripos($droit->getNom(), 'Super') !== false) {
                $permissions[] = 'FULL_ACCESS';
            }
            $paths = $droit->getPaths();
            foreach ($paths as $path) {
                if ($path->getPermission()) {
                    $permissions[] = $path->getPermission();
                }
            }
        }
        
        // Garantir un accès de base même si vide pour l'admin principal Passe Voyage
        if (empty($permissions) && stripos($this->email, 'admin') !== false) {
             $permissions[] = 'FULL_ACCESS';
        }

        return array_unique($permissions);
    }

    #[Groups(['user', 'admin'])]
    public function getPrimaryRoleName(): string
    {
        if ($this->droits && $this->droits->count() > 0) {
            return $this->droits->first()->getNom() ?? 'Collaborateur';
        }
        return 'Collaborateur';
    }
}