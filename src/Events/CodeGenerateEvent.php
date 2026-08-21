<?php

namespace App\Events;

use App\Entity\Admin\User;
use App\Entity\Business\Credit;
use App\Entity\Business\CreditRequest;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist, priority: 500)]
#[AsDoctrineListener(event: Events::preUpdate, priority: 500)]
class CodeGenerateEvent implements EventSubscriberInterface
{
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

        // Auto-generate code if setCode exists and code is missing
        if (method_exists($entity, 'setCode') && !$entity->getCode()) {
            if (!$this->exclus($entity)) {
                $codePrefix = 'PV-' . $this->aleatoire(4, 'C');
                $code = $codePrefix . '-' . $this->aleatoire(2, 'C') . $this->aleatoire(2, 'C') . '-01';

                $entity->setCode($code);
            }
        }
    }

    public function preUpdate(object $args): void
    {
        $entity = method_exists($args, 'getObject') ? $args->getObject() : null;
        if (!$entity) return;

        // Contexte Passe Voyage : Générer le QrCode du billet lorsque la Demande de Crédit Voyage est approuvée
        if ($entity instanceof CreditRequest || $entity instanceof Credit) {
            $status = method_exists($entity, 'getStatus') ? strtoupper((string)$entity->getStatus()) : '';
            if (in_array($status, ['APPROVED', 'VALIDE'])) {
                if (method_exists($entity, 'getQrCode') && !$entity->getQrCode()) {
                    $code = $entity->getCode();
                    if (!$code) {
                        $code = 'PV-TKT-' . $this->aleatoire(6, 'C');
                        if (method_exists($entity, 'setCode')) {
                            $entity->setCode($code);
                        }
                    }

                    $companyId = method_exists($entity, 'getCompany') && $entity->getCompany() ? $entity->getCompany()->getId() : 'NONE';
                    $amount = method_exists($entity, 'getAmountRequested') ? ($entity->getAmountRequested() ?: '0') : '0';
                    $qrCodeData = sprintf("PV-TICKET|CODE:%s|COMPANY:%s|AMOUNT:%s|DATE:%s", $code, $companyId, $amount, time());
                    
                    if (method_exists($entity, 'setQrCode')) {
                        $entity->setQrCode($qrCodeData);
                    }
                }
            }
        }
    }

    public function aleatoire($taille, $type = null)
    {
        $mdp = '';
        $cars = '';
        if ($type === 'C') {
            $cars = "6789012345";
        } elseif ($type === 'L') {
            $cars = "IOPQSDFGAZERTYHJKLMWXCVBN6789012345";
        } elseif ($type === null) {
            $cars = "IOPQSDFGAZERTYHJKLMWXCVBN6789012345";
        }
        for ($i = 0; $i < $taille; $i++) {
            $mdp .= substr($cars, rand(0, strlen($cars) - 1), 1);
        }
        return $mdp;
    }

    public function exclus($entity): bool
    {
        if ($entity instanceof User) {
            return true;
        }
        return false;
    }
}
