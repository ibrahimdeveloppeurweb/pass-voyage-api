<?php

namespace App\Events;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Ramsey\Uuid\Uuid;
use App\Traits\PhotoTrait;
use App\Entity\Extra\File;
use App\Traits\FolderTrait;
use App\Entity\Extra\Folder;

#[AsDoctrineListener(event: Events::prePersist, priority: 500)]
#[AsDoctrineListener(event: Events::preUpdate, priority: 500)]
class UuidEvent implements EventSubscriberInterface
{
    private $em;
    private $security;

    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(object $args): void
    {
        $entity = method_exists($args, 'getObject') ? $args->getObject() : null;
        if (!$entity) return;

        // 1. Auto-generate UUID
        if (method_exists($entity, 'setUuid')) {
            if (!$entity->getUuid()) {
                $entity->setUuid(Uuid::uuid4()->toString());
            }
        }

        // 2. Auto-set createdAt
        if (method_exists($entity, 'setCreatedAt')) {
            if (!$entity->getCreatedAt()) {
                $entity->setCreatedAt(new \DateTime());
            }
        }

        // 3. Auto-set updatedAt
        if (method_exists($entity, 'setUpdatedAt')) {
            if (!$entity->getUpdatedAt()) {
                $entity->setUpdatedAt(new \DateTime());
            }
        }

        $this->fileSetter($entity);
    }

    public function preUpdate(object $args): void
    {
        $entity = method_exists($args, 'getObject') ? $args->getObject() : null;
        if (!$entity) return;

        // Auto-update updatedAt on changes
        if (method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt(new \DateTime());
        }
    }

    public function fileSetter($entity): void
    {
        if (!is_object($entity)) return;

        $classUses = class_uses(get_class($entity));
        if ($classUses && in_array(PhotoTrait::class, $classUses, true)) {
            if (method_exists($entity, 'getPhotoUuid') && !empty($entity->getPhotoUuid())) {
                $photo = $this->em->getRepository(File::class)->findOneBy(['uuid' => (string)$entity->getPhotoUuid()]);
                if ($photo instanceof File) {
                    if (method_exists($entity, 'getPhoto') && $entity->getPhoto() instanceof File) {
                        $filePath = __DIR__ . '/../../public/' . $entity->getPhotoSrc();
                        @unlink($filePath);
                        $this->em->remove($entity->getPhoto());
                    }
                    $entity->setPhoto($photo);
                }
            }
        }

        if ($classUses && in_array(FolderTrait::class, $classUses, true)) {
            if (method_exists($entity, 'getFolderUuid') && !empty($entity->getFolderUuid())) {
                $folder = $this->em->getRepository(Folder::class)->findOneBy(['uuid' => (string)$entity->getFolderUuid()]);
                if ($folder instanceof Folder) {
                    $entity->setFolder($folder);
                }
            }
        }
    }
}
