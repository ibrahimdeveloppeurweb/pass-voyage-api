<?php

namespace App\Entity\Extra;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\Extra\UserOtpRepository;

#[ORM\Table(name: 'user_otp')]
#[ORM\Entity(repositoryClass: UserOtpRepository::class)]
class UserOtp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 20)]
    private $phone;

    #[ORM\Column(type: 'string', length: 6)]
    private $code;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[ORM\Column(type: 'datetime')]
    private $expiresAt;

    #[ORM\Column(type: 'boolean')]
    private $isUsed = false;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getIsUsed(): ?bool
    {
        return $this->isUsed;
    }

    public function setIsUsed(bool $isUsed): self
    {
        $this->isUsed = $isUsed;
        return $this;
    }

    public function isExpired(): bool
    {
        return new \DateTime() > $this->expiresAt;
    }
}
