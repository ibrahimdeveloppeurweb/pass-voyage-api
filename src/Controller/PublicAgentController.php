<?php

namespace App\Controller;

use App\Manager\Business\AgentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/api/public/agent")
 */
#[Route(path: '/api/public/agent')]
class PublicAgentController extends AbstractController
{
    private $agentManager;

    public function __construct(AgentManager $agentManager)
    {
        $this->agentManager = $agentManager;
    }

    /**
     * @Route("/register", name="register_agent_public", methods={"POST"},
     * options={"description"="Création d'un compte agent par formulaire public"})
     */
    #[Route('/send-otp', name: 'send_otp_agent_public', methods: ['POST'])]
    public function sendOtp(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $result = $this->agentManager->sendOtp($data);
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/verify-otp', name: 'verify_otp_agent_public', methods: ['POST'])]
    public function verifyOtp(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $result = $this->agentManager->verifyOtp($data);
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/register', name: 'register_agent_public', methods: ['POST'], options: ['description' => 'Création d\'un compte agent par formulaire public'])]
    public function registerAgent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $agent = $this->agentManager->create($data);
            $token = $this->agentManager->generateJwtTokenForAgent($agent, $data->pinCode ?? '1234');

            return $this->json([
                'status' => 'success',
                'message' => 'Compte agent créé avec succès.',
                'token' => $token,
                'agentStatus' => $agent->getStatus() ?? 'PENDING',
                'agent' => [
                    'id' => $agent->getId(),
                    'uuid' => $agent->getUuid(),
                    'firstname' => $agent->getFirstname(),
                    'lastname' => $agent->getLastname(),
                    'phoneNumber' => $agent->getPhoneNumber(),
                    'status' => $agent->getStatus() ?? 'PENDING',
                    'isActivated' => $agent->getIsActivated(),
                    'company' => $agent->getCompany()?->getName(),
                    'station' => $agent->getStationAssigned()?->getName(),
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de l\'inscription de l\'agent : ' . $e->getMessage()], 400);
        }
    }

    #[Route('/login', name: 'login_agent_public', methods: ['POST'])]
    public function loginAgent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $result = $this->agentManager->login($data);
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/fcm-token', name: 'fcm_token_agent_public', methods: ['POST'])]
    public function updateFcmToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $result = $this->agentManager->updateFcmTokenPublic($data);
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }


}
