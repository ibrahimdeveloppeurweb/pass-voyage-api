<?php

namespace App\Entity\Extra;

use Doctrine\ORM\Mapping as ORM;
use App\Traits\UserObjectTrait;
use App\Repository\Extra\FileRepository;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: FileRepository::class)]
#[ORM\HasLifecycleCallbacks]
class File
{
    use UserObjectTrait;

    /**
     * @Groups({"default", "file","list","photo"})
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * @Groups({"default", "file","list","photo"})
     */
    #[ORM\Column(type: 'string', length: 255)]
    private $src;

    /**
     * @Groups({"default", "file","list","photo"})
     */
    private $fullPath;

    /**
     * @Groups({"default", "file","list","photo"})
     */
    #[ORM\Column(type: 'string', length: 255)]
    private $realName;

    /**
     * @Groups({"default", "file","list","photo"})
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $type;

    #[ORM\JoinColumn(referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Folder::class, inversedBy: 'files')]
    private $folder;

    #[ORM\ManyToOne(targetEntity: Folder::class, inversedBy: 'filesSigne')]
    private $signed;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSrc(): ?string
    {
        return $this->src;
    }

    public function setSrc(string $src): self
    {
        $this->src = $src;

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

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function setFolder(?Folder $folder): self
    {
        $this->folder = $folder;

        return $this;
    }

    public function getRealName(): ?string
    {
        return $this->realName;
    }
    
    public function setRealName(?string $realName)
    {
        $this->realName = $realName;
    }

    /**
     * @return mixed
     */
    public function getFullPath()
    {
        return $this->fullPath;
    }

    /**
     * @param mixed $fullPath
     */
    public function setFullPath($fullPath): void
    {
        $this->fullPath = $fullPath;
    }

    public function getSigned(): ?Folder
    {
        return $this->signed;
    }

    public function setSigned(?Folder $signed): self
    {
        $this->signed = $signed;

        return $this;
    }
}
