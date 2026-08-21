<?php

namespace App\Events;

use App\Entity\Admin\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::prePersist, priority: 500)]
#[AsDoctrineListener(event: Events::preUpdate, priority: 500)]
#[AsDoctrineListener(event: Events::preRemove, priority: 500)]
class UserEvent implements EventSubscriberInterface
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::preRemove,
        ];
    }

    public function prePersist(object $args): void
    {
        $entity = method_exists($args, 'getObject') ? $args->getObject() : null;
        if (!$entity) return;

        $user = $this->getAdminUser();

        if ($user && method_exists($entity, 'setCreateBy') && !$entity->getCreateBy()) {
            $entity->setCreateBy($user);
        }

        if ($user && method_exists($entity, 'setUpdateBy')) {
            $entity->setUpdateBy($user);
        }

        if ($user && method_exists($entity, 'setValidateBy') && method_exists($entity, 'setEtat')) {
            $etat = $entity->getEtat();
            if (in_array($etat, ['ACTIF', 'VALIDE', 'DECAISSER', 'APPROVED', 'DISBURSED'], true)) {
                if (method_exists($entity, 'setValidateAt')) {
                    $entity->setValidateAt(new \DateTime('now'));
                }
                $entity->setValidateBy($user);
            }
        }
    }

    public function preUpdate(object $args): void
    {
        $entity = method_exists($args, 'getObject') ? $args->getObject() : null;
        if (!$entity) return;

        $user = $this->getAdminUser();

        if ($user && method_exists($entity, 'setUpdateBy')) {
            $entity->setUpdateBy($user);
        }

        if ($user && method_exists($entity, 'setValidateBy') && method_exists($entity, 'setEtat')) {
            $etat = $entity->getEtat();
            if (in_array($etat, ['ACTIF', 'VALIDE', 'DECAISSER', 'APPROVED', 'DISBURSED'], true)) {
                if (method_exists($entity, 'setValidateAt')) {
                    $entity->setValidateAt(new \DateTime('now'));
                }
                $entity->setValidateBy($user);
            }
        }
    }

    public function preRemove(object $args): void
    {
        $entity = method_exists($args, 'getObject') ? $args->getObject() : null;
        if (!$entity) return;

        $user = $this->getAdminUser();

        if ($user && method_exists($entity, 'setRemoveBy')) {
            $entity->setRemoveBy($user);
        }
    }

    private function getAdminUser(): ?User
    {
        $user = $this->security->getUser();
        if ($user instanceof User) {
            return $user;
        }
        return null;
    }
}
