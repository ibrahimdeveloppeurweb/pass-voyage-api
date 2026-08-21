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

    #[Route('/dashboard', name: 'get_agent_dashboard_public', methods: ['GET', 'POST'])]
    public function getDashboardPublic(Request $request): JsonResponse
    {
        $phone = $request->query->get('phone');
        if (!$phone) {
            $data = json_decode($request->getContent());
            $phone = $data->phone ?? $data->phoneNumber ?? null;
        }

        try {
            $result = $this->agentManager->getDashboardData($phone);
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/ticket/verify', name: 'verify_agent_ticket_public', methods: ['POST'])]
    public function verifyTicketPublic(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $result = $this->agentManager->verifyTicket($data);
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/ticket/scan', name: 'scan_agent_ticket_public', methods: ['POST'])]
    public function scanTicketPublic(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $result = $this->agentManager->scanTicket($data);
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/scans/history', name: 'get_agent_scan_history_public', methods: ['GET', 'POST'])]
    public function getScanHistoryPublic(Request $request): JsonResponse
    {
        $phone = $request->query->get('phone');
        if (!$phone) {
            $data = json_decode($request->getContent());
            $phone = $data->phone ?? $data->phoneNumber ?? null;
        }

        try {
            $result = $this->agentManager->getScanHistory($phone);
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/notifications', name: 'get_agent_notifications_public', methods: ['GET', 'POST'])]
    public function getNotificationsPublic(Request $request): JsonResponse
    {
        $phone = $request->query->get('phone');
        if (!$phone) {
            $data = json_decode($request->getContent());
            $phone = $data->phone ?? $data->phoneNumber ?? null;
        }

        try {
            $result = $this->agentManager->getNotifications($phone);
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/performances', name: 'agent_performances_public', methods: ['GET', 'POST'])]
    public function getPerformancesPublic(Request $request): JsonResponse
    {
        $date = $request->query->get('date') ?? $request->query->get('filterDate');
        $search = $request->query->get('search') ?? $request->query->get('advSearchTerm');
        $appreciation = $request->query->get('appreciation') ?? $request->query->get('advAppreciationFilter');

        if (!$date && !$search && !$appreciation) {
            $data = json_decode($request->getContent());
            if ($data) {
                $date = $data->date ?? $data->filterDate ?? null;
                $search = $data->search ?? $data->advSearchTerm ?? null;
                $appreciation = $data->appreciation ?? $data->advAppreciationFilter ?? null;
            }
        }

        try {
            $result = $this->agentManager->getPerformances($date, $search, $appreciation);
            return $this->json(['status' => 'success', 'data' => $result, 'performances' => $result], 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }
}
