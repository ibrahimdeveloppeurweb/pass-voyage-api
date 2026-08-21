<?php

namespace App\Entity\Extra;

use App\Entity\Admin\User;
use App\Repository\Extra\NotificationRepository;
use App\Traits\EntityTrait;
use App\Traits\SearchableTrait;
use App\Traits\UserObjectTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Notification
{
    use EntityTrait;
    use SearchableTrait;
    use UserObjectTrait;
    use SoftDeleteableEntity;

    #[Groups(['notification:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['notification:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    #[Groups(['notification:read'])]
    #[ORM\Column(type: 'string', length: 255)]
    private $title;

    #[Groups(['notification:read'])]
    #[ORM\Column(type: 'text')]
    private $message;

    #[Groups(['notification:read'])]
    #[ORM\Column(type: 'string', length: 255)]
    private $type = 'SYSTEM';

    #[Groups(['notification:read'])]
    #[ORM\Column(type: 'boolean')]
    private $isRead = false;

    #[Groups(['notification:read'])]
    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Gedmo\Timestampable(on: 'create')]
    private $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getTitle(): string { return (string) $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }

    public function getMessage(): ?string { return $this->message; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }

    public function getIsRead(): ?bool { return $this->isRead; }
    public function setIsRead(bool $isRead): self { $this->isRead = $isRead; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getDetail(): string { return (string) $this->message; }
}
