<?php

namespace App\Manager\Business;

use App\Entity\Business\CreditPolicy;
use App\Repository\Business\CreditPolicyRepository;
use Doctrine\ORM\EntityManagerInterface;

class CreditPolicyManager
{
    private $em;
    private $policyRepository;

    public function __construct(
        EntityManagerInterface $em,
        CreditPolicyRepository $policyRepository
    ) {
        $this->em = $em;
        $this->policyRepository = $policyRepository;
    }

    public function getOrCreatePolicy(): CreditPolicy
    {
        $policies = $this->policyRepository->findAll();
        if (!empty($policies)) {
            return $policies[0];
        }

        $policy = new CreditPolicy();
        $policy->setNewUserLimit(5000);
        $policy->setStandardLimit(10000);
        $policy->setVipLimit(25000);
        $policy->setAutoApproveEnabled(true);
        $policy->setAutoRejectBlacklistEnabled(true);

        $this->em->persist($policy);
        $this->em->flush();

        return $policy;
    }

    public function formatPolicy(CreditPolicy $policy): array
    {
        return [
            'id' => $policy->getId(),
            'newUserLimit' => $policy->getNewUserLimit(),
            'standardLimit' => $policy->getStandardLimit(),
            'vipLimit' => $policy->getVipLimit(),
            'autoApproveEnabled' => $policy->getAutoApproveEnabled(),
            'autoRejectBlacklistEnabled' => $policy->getAutoRejectBlacklistEnabled(),
        ];
    }

    public function updatePolicy($data): CreditPolicy
    {
        $policy = $this->getOrCreatePolicy();
        $dataObj = is_array($data) ? (object)$data : $data;

        if (isset($dataObj->newUserLimit)) {
            $policy->setNewUserLimit((int)$dataObj->newUserLimit);
        }
        if (isset($dataObj->standardLimit)) {
            $policy->setStandardLimit((int)$dataObj->standardLimit);
        }
        if (isset($dataObj->vipLimit)) {
            $policy->setVipLimit((int)$dataObj->vipLimit);
        }
        if (isset($dataObj->autoApproveEnabled)) {
            $policy->setAutoApproveEnabled((bool)$dataObj->autoApproveEnabled);
        }
        if (isset($dataObj->autoRejectBlacklistEnabled)) {
            $policy->setAutoRejectBlacklistEnabled((bool)$dataObj->autoRejectBlacklistEnabled);
        }

        $this->em->flush();

        return $policy;
    }
}
