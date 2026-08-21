<?php

namespace App\Entity\Extra;

use App\Repository\Extra\GeneralSettingHistoryRepository;
use App\Traits\SearchableTrait;
use App\Traits\UserObjectTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GeneralSettingHistoryRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class GeneralSettingHistory
{
    use SearchableTrait;
    use SoftDeleteableEntity;
    use UserObjectTrait;

    #[Groups(['setting_history'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['setting_history'])]
    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    #[Groups(['setting_history'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    #[Groups(['setting_history'])]
    #[ORM\Column(type: 'json')]
    private $previousValues = [];

    #[Groups(['setting_history'])]
    #[ORM\Column(type: 'json')]
    private $newValues = [];

    #[Groups(['setting_history'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $updatedBy;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->uuid = \Ramsey\Uuid\Uuid::uuid4();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

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

    public function getPreviousValues(): ?array
    {
        return $this->previousValues;
    }

    public function setPreviousValues(array $previousValues): self
    {
        $this->previousValues = $previousValues;

        return $this;
    }

    public function getNewValues(): ?array
    {
        return $this->newValues;
    }

    public function setNewValues(array $newValues): self
    {
        $this->newValues = $newValues;

        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    #[Groups(['setting_history'])]
    public function getCreatedByName(): ?string
    {
        if ($this->getCreateBy()) {
            $user = $this->getCreateBy();
            $nom = $user->getNom() ?? '';
            $prenom = $user->getPrenom() ?? '';
            $name = trim($nom . ' ' . $prenom);
            return !empty($name) ? $name : ($user->getUsername() ?? 'Administrateur');
        }
        return $this->updatedBy ?? 'Administrateur';
    }

    #[Groups(['setting_history'])]
    public function getTitle(): string
    {
        return "Historique Paramètres : " . $this->description;
    }

    #[Groups(['setting_history'])]
    public function getDetail(): string
    {
        return "Modification effectuée le " . ($this->createdAt ? $this->createdAt->format('d/m/Y H:i') : '');
    }
}