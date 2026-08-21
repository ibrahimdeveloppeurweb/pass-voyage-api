<?php

namespace App\Controller\Business;

use App\Manager\Business\CreditPolicyManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CreditPolicyController extends AbstractController
{
    private $creditPolicyManager;

    public function __construct(CreditPolicyManager $creditPolicyManager)
    {
        $this->creditPolicyManager = $creditPolicyManager;
    }

    #[Route('/api/credit-policy', name: 'index_credit_policy', methods: ['GET'], options: ['description' => 'Afficher la politique de crédit', 'permission' => 'CREDIT:READ'])]
    #[Route('/api/credit-policy/', name: 'index_credit_policy_slash', methods: ['GET'])]
    #[Route('/api/private/credit-policy', name: 'index_credit_policy_priv', methods: ['GET'])]
    #[Route('/api/private/credit-policy/', name: 'index_credit_policy_priv_slash', methods: ['GET'])]
    #[Route('/api/public/credit-policy', name: 'index_credit_policy_pub', methods: ['GET'])]
    #[Route('/api/public/credit-policy/', name: 'index_credit_policy_pub_slash', methods: ['GET'])]
    public function show(): JsonResponse
    {
        $policy = $this->creditPolicyManager->getOrCreatePolicy();
        return $this->json([
            'success' => true,
            'policy' => $this->creditPolicyManager->formatPolicy($policy)
        ], 200);
    }

    #[Route('/api/credit-policy/update', name: 'update_credit_policy', methods: ['POST', 'PUT'], options: ['description' => 'Mettre à jour la politique de crédit', 'permission' => 'CREDIT:WRITE'])]
    #[Route('/api/credit-policy/update/', name: 'update_credit_policy_slash', methods: ['POST', 'PUT'])]
    #[Route('/api/private/credit-policy/update', name: 'update_credit_policy_priv', methods: ['POST', 'PUT'])]
    #[Route('/api/private/credit-policy/update/', name: 'update_credit_policy_priv_slash', methods: ['POST', 'PUT'])]
    #[Route('/api/public/credit-policy/update', name: 'update_credit_policy_pub', methods: ['POST', 'PUT'])]
    #[Route('/api/public/credit-policy/update/', name: 'update_credit_policy_pub_slash', methods: ['POST', 'PUT'])]
    public function update(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        $policy = $this->creditPolicyManager->updatePolicy($data);

        return $this->json([
            'success' => true,
            'message' => 'Paramètres de crédit mis à jour avec succès',
            'policy' => $this->creditPolicyManager->formatPolicy($policy)
        ], 200);
    }
}
