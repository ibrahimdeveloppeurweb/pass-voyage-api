<?php

namespace App\Traits;

use App\Entity\Admin\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

trait UserObjectTrait
{
    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: true)]
    #[Groups(['user', 'admin', 'passenger:read', 'role', 'company:read', 'company_invoice:read', 'company_fund:read', 'agent:read', 'credit_request:read', 'creditrequest:read', 'credit:read', 'ticket:read', 'notification:read'])]
    private $uuid;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user', 'admin', 'passenger:read'])]
    private $code;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Gedmo\Timestampable(on: 'create')]
    #[Groups(['user', 'admin', 'passenger:read'])]
    private $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    #[Groups(['user', 'admin', 'passenger:read'])]
    private $updatedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'create_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private $createBy;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'update_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private $updateBy;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'remove_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private $removeBy;

    public function setUuid($uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getUuid()
    {
        return $this->uuid ? (is_string($this->uuid) ? $this->uuid : (is_object($this->uuid) && method_exists($this->uuid, 'toString') ? $this->uuid->toString() : (string)$this->uuid)) : null;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCreateBy(): ?User
    {
        return $this->createBy;
    }

    #[Groups(['user', 'admin'])]
    public function getCreate(): ?string
    {
        if (!$this->createBy) return null;
        if (method_exists($this->createBy, 'getLibelle')) return $this->createBy->getLibelle();
        if (method_exists($this->createBy, 'getFullName')) {
            $fn = $this->createBy->getFullName();
            if (!empty($fn)) return $fn;
        }
        return method_exists($this->createBy, 'getUsername') ? $this->createBy->getUsername() : null;
    }

    public function setCreateBy(?User $createBy): self
    {
        $this->createBy = $createBy;

        return $this;
    }

    public function getUpdateBy(): ?User
    {
        return $this->updateBy;
    }

    #[Groups(['user', 'admin'])]
    public function getUpdate(): ?string
    {
        if (!$this->updateBy) return null;
        if (method_exists($this->updateBy, 'getLibelle')) return $this->updateBy->getLibelle();
        if (method_exists($this->updateBy, 'getFullName')) {
            $fn = $this->updateBy->getFullName();
            if (!empty($fn)) return $fn;
        }
        return method_exists($this->updateBy, 'getUsername') ? $this->createBy->getUsername() : null;
    }

    public function setUpdateBy(?User $updateBy): self
    {
        $this->updateBy = $updateBy;

        return $this;
    }

    public function getRemoveBy(): ?User
    {
        return $this->removeBy;
    }

    public function setRemoveBy(?User $removeBy): self
    {
        $this->removeBy = $removeBy;

        return $this;
    }
}