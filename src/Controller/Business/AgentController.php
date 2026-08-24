<?php

namespace App\Controller\Business;

use App\Entity\Business\Agent;
use App\Manager\Business\AgentManager;
use App\Repository\Business\AgentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/api/private/agent")
 */
#[Route(path: '/api/private/agent')]
class AgentController extends AbstractController
{
    private $agentRepository;
    private $agentManager;

    public function __construct(
        AgentRepository $agentRepository,
        AgentManager $agentManager
    ) {
        $this->agentRepository = $agentRepository;
        $this->agentManager = $agentManager;
    }

    /**
     * @Route("/", name="index_agent", methods={"GET"},
     * options={"description"="Liste des agents de gare", "permission"="AGENT:LIST"})
     */
    #[Route('/', name: 'index_agent', methods: ['GET'], options: ['description' => 'Liste des agents de gare', 'permission' => 'AGENT:LIST'])]
    public function index(Request $request): JsonResponse
    {
        $agents = $this->agentRepository->findBy([], ['id' => 'DESC']);
        $ticketRepo = $this->agentRepository->getEntityManager()->getRepository(\App\Entity\Business\Ticket::class);

        $todayStart = new \DateTime('today 00:00:00');
        $todayEnd = new \DateTime('today 23:59:59');

        $result = [];
        $conn = $this->agentRepository->getEntityManager()->getConnection();
        $today = (new \DateTime('today'))->format('Y-m-d');

        foreach ($agents as $agent) {
            $scansToday = 0;
            $scansTotal = 0;
            try {
                $agentId = $agent->getId();
                $scansTotal = (int) $conn->fetchOne(
                    'SELECT COUNT(*) FROM ticket WHERE validated_by_agent_id = ?',
                    [$agentId]
                );
                $scansToday = (int) $conn->fetchOne(
                    'SELECT COUNT(*) FROM ticket WHERE validated_by_agent_id = ? AND DATE(validated_at) = ?',
                    [$agentId, (new \DateTime('today'))->format('Y-m-d')]
                );
            } catch (\Throwable $e) {
                // fallback : try alternative column names
                try {
                    $agentId = $agent->getId();
                    $rows = $conn->fetchAllAssociative(
                        'SELECT COUNT(*) as cnt FROM ticket WHERE validated_by_agent_id = ?',
                        [$agentId]
                    );
                    $scansTotal = isset($rows[0]['cnt']) ? (int)$rows[0]['cnt'] : 0;
                } catch (\Throwable $e2) {}
            }

            $createdAtStr = $agent->getCreatedAt() ? $agent->getCreatedAt()->format('Y-m-d H:i:s') : '2026-08-01 00:00:00';

            $result[] = [
                'id' => $agent->getId(),
                'uuid' => $agent->getUuid(),
                'firstname' => $agent->getFirstname(),
                'lastname' => $agent->getLastname(),
                'nom' => sprintf('%s %s', $agent->getLastname() ?? '', $agent->getFirstname() ?? ''),
                'phoneNumber' => $agent->getPhoneNumber(),
                'telephone' => $agent->getPhoneNumber(),
                'countryCode' => $agent->getCountryCode(),
                'gender' => $agent->getGender(),
                'residenceAddress' => $agent->getResidenceAddress(),
                'isActivated' => $agent->getIsActivated(),
                'isActive' => $agent->getIsActive(),
                'status' => $agent->getStatus(),
                'statut' => $agent->getStatus(),
                'agentCode' => $agent->getAgentCode(),
                'code' => $agent->getAgentCode(),
                'matricule' => $agent->getAgentCode() ?: sprintf('AG-%03d', $agent->getId()),
                'assignmentDate' => $agent->getAssignmentDate(),
                'shiftStart' => $agent->getShiftStart() ?: '08:00',
                'shiftEnd' => $agent->getShiftEnd() ?: '17:00',
                'createdAt' => $createdAtStr,
                'scansToday' => (int)$scansToday,
                'scansTotal' => (int)$scansTotal,
                'company' => $agent->getCompany() ? [
                    'id' => $agent->getCompany()->getId(),
                    'uuid' => $agent->getCompany()->getUuid(),
                    'name' => $agent->getCompany()->getName(),
                    'nom' => $agent->getCompany()->getName(),
                ] : null,
                'companyName' => $agent->getCompany() ? $agent->getCompany()->getName() : null,
                'stationAssigned' => $agent->getStationAssigned() ? [
                    'id' => $agent->getStationAssigned()->getId(),
                    'uuid' => $agent->getStationAssigned()->getUuid(),
                    'name' => $agent->getStationAssigned()->getName(),
                    'nom' => $agent->getStationAssigned()->getName(),
                ] : null,
                'stationName' => $agent->getStationAssigned() ? $agent->getStationAssigned()->getName() : null,
            ];
        }

        return $this->json(['status' => 'success', 'data' => $result, 'agents' => $result], 200, [], ['groups' => ['agent:read']]);
    }

    /**
     * @Route("/new", name="new_agent", methods={"POST"},
     * options={"description"="Ajouter un agent", "permission"="AGENT:NEW"})
     */
    #[Route('/new', name: 'new_agent', methods: ['POST'], options: ['description' => 'Ajouter un agent', 'permission' => 'AGENT:NEW'])]
    public function new(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $agent = $this->agentManager->create($data);
            return $this->json($agent, 201, [], ['groups' => ['agent:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de la création : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/{uuid}/show", name="show_agent", methods={"GET"},
     * options={"description"="Détails d'un agent", "permission"="AGENT:SHOW"})
     */
    #[Route('/{uuid}/show', name: 'show_agent', methods: ['GET'], options: ['description' => 'Détails d\'un agent', 'permission' => 'AGENT:SHOW'])]
    public function show(string $uuid): JsonResponse
    {
        try {
            $agent = $this->agentManager->show($uuid);
            return $this->json($agent, 200, [], ['groups' => ['agent:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * @Route("/{uuid}/edit", name="edit_agent", methods={"PUT", "POST"},
     * options={"description"="Modifier un agent", "permission"="AGENT:EDIT"})
     */
    #[Route('/{uuid}/edit', name: 'edit_agent', methods: ['PUT', 'POST'], options: ['description' => 'Modifier un agent', 'permission' => 'AGENT:EDIT'])]
    public function edit(Request $request, string $uuid): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $agent = $this->agentManager->update($uuid, $data);
            return $this->json($agent, 200, [], ['groups' => ['agent:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de la modification : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/{uuid}/assign", name="assign_agent", methods={"POST", "PUT"},
     * options={"description"="Affecter gare et compagnie à un agent", "permission"="AGENT:ASSIGN"})
     */
    #[Route('/{uuid}/assign', name: 'assign_agent', methods: ['POST', 'PUT'], options: ['description' => 'Affecter gare et compagnie à un agent', 'permission' => 'AGENT:ASSIGN'])]
    public function assign(Request $request, string $uuid): JsonResponse
    {
        $data = json_decode($request->getContent());
        if (!isset($data->companyUuid)) {
            return $this->json(['message' => 'Veuillez spécifier la compagnie'], 400);
        }

        $stationUuid = $data->stationUuid ?? '';
        $date = $data->date ?? $data->assignmentDate ?? null;
        $heureDebut = $data->heureDebut ?? $data->shiftStart ?? null;
        $heureFin = $data->heureFin ?? $data->shiftEnd ?? null;

        try {
            $agent = $this->agentManager->assignStationAndCompany($uuid, $data->companyUuid, $stationUuid, $date, $heureDebut, $heureFin);
            return $this->json($agent, 200, [], ['groups' => ['agent:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de l\'affectation : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/{uuid}/toggle-status", name="toggle_status_agent", methods={"POST", "PUT"},
     * options={"description"="Activer ou désactiver un agent", "permission"="AGENT:TOGGLE"})
     */
    #[Route('/{uuid}/toggle-status', name: 'toggle_status_agent', methods: ['POST', 'PUT'], options: ['description' => 'Activer ou désactiver un agent', 'permission' => 'AGENT:TOGGLE'])]
    public function toggleStatus(string $uuid): JsonResponse
    {
        try {
            $agent = $this->agentManager->toggleStatus($uuid);
            return $this->json($agent, 200, [], ['groups' => ['agent:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors du changement de statut : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/{uuid}/delete", name="delete_agent", methods={"DELETE"},
     * options={"description"="Supprimer un agent", "permission"="AGENT:DELETE"})
     */
    #[Route('/{uuid}/delete', name: 'delete_agent', methods: ['DELETE'], options: ['description' => 'Supprimer un agent', 'permission' => 'AGENT:DELETE'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $this->agentManager->delete($uuid);
            return $this->json(['message' => 'Agent supprimé avec succès'], 200);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de la suppression : ' . $e->getMessage()], 400);
        }
    }

    private function getAgentIdentifier(Request $request): ?string
    {
        $uuid = $request->query->get('uuid') ?? $request->query->get('agentUuid');
        if ($uuid) {
            return $uuid;
        }

        $phone = $request->query->get('phone') ?? $request->query->get('phoneNumber');
        if ($phone) {
            return $phone;
        }

        $data = json_decode($request->getContent());
        if ($data) {
            return $data->uuid ?? $data->agentUuid ?? $data->phone ?? $data->phoneNumber ?? null;
        }

        return null;
    }

    /**
     * @Route("/dashboard", name="get_agent_dashboard", methods={"GET", "POST"},
     * options={"description"="Tableau de bord de l'agent", "permission"="AGENT:DASHBOARD"})
     */
    #[Route('/dashboard', name: 'get_agent_dashboard', methods: ['GET', 'POST'], options: ['description' => 'Tableau de bord de l\'agent', 'permission' => 'AGENT:DASHBOARD'])]
    public function getDashboard(Request $request): JsonResponse
    {
        $identifier = $this->getAgentIdentifier($request);

        try {
            $result = $this->agentManager->getDashboardData($identifier);
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/ticket/verify", name="verify_agent_ticket", methods={"POST"},
     * options={"description"="Vérifier la nomenclature d'un billet", "permission"="AGENT:TICKET_VERIFY"})
     */
    #[Route('/ticket/verify', name: 'verify_agent_ticket', methods: ['POST'], options: ['description' => 'Vérifier la nomenclature d\'un billet', 'permission' => 'AGENT:TICKET_VERIFY'])]
    public function verifyTicket(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $result = $this->agentManager->verifyTicket($data);
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/ticket/scan", name="scan_agent_ticket", methods={"POST"},
     * options={"description"="Valider ou refuser l'embarquement d'un billet", "permission"="AGENT:TICKET_SCAN"})
     */
    #[Route('/ticket/scan', name: 'scan_agent_ticket', methods: ['POST'], options: ['description' => 'Valider ou refuser l\'embarquement d\'un billet', 'permission' => 'AGENT:TICKET_SCAN'])]
    public function scanTicket(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $result = $this->agentManager->scanTicket($data);
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/scans/history", name="get_agent_scan_history", methods={"GET", "POST"},
     * options={"description"="Historique des scans d'un agent", "permission"="AGENT:SCAN_HISTORY"})
     */
    #[Route('/scans/history', name: 'get_agent_scan_history', methods: ['GET', 'POST'], options: ['description' => 'Historique des scans d\'un agent', 'permission' => 'AGENT:SCAN_HISTORY'])]
    public function getScanHistory(Request $request): JsonResponse
    {
        $identifier = $this->getAgentIdentifier($request);

        try {
            $result = $this->agentManager->getScanHistory($identifier);
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/notifications", name="get_agent_notifications", methods={"GET", "POST"},
     * options={"description"="Liste des notifications d'un agent", "permission"="AGENT:NOTIFICATIONS"})
     */
    #[Route('/notifications', name: 'get_agent_notifications', methods: ['GET', 'POST'], options: ['description' => 'Liste des notifications d\'un agent', 'permission' => 'AGENT:NOTIFICATIONS'])]
    public function getNotifications(Request $request): JsonResponse
    {
        $identifier = $this->getAgentIdentifier($request);

        try {
            $result = $this->agentManager->getNotifications($identifier, $this->getUser());
            return $this->json($result, 200, [], ['groups' => ['notification:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/fcm-token", name="fcm_token_agent_private", methods={"POST"},
     * options={"description"="Mettre à jour le jeton FCM de l'agent", "permission"="AGENT:FCM_TOKEN"})
     */
    #[Route('/fcm-token', name: 'fcm_token_agent_private', methods: ['POST'], options: ['description' => 'Mettre à jour le jeton FCM de l\'agent', 'permission' => 'AGENT:FCM_TOKEN'])]
    public function updateFcmToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        $fcmToken = $data->fcmToken ?? $data->fcm_token ?? null;
        $identifier = $data->uuid ?? $data->agentUuid ?? $data->phone ?? $data->phoneNumber ?? null;

        try {
            $result = $this->agentManager->updateFcmToken($fcmToken, $identifier, $this->getUser());
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/rating", name="submit_agent_rating", methods={"POST"},
     * options={"description"="Évaluer l'application agent", "permission"="AGENT:RATING"})
     */
    #[Route('/rating', name: 'submit_agent_rating', methods: ['POST'], options: ['description' => 'Évaluer l\'application agent', 'permission' => 'AGENT:RATING'])]
    public function submitRating(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $result = $this->agentManager->submitRating($data);
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/notifications/read", name="mark_agent_notifications_read", methods={"POST"},
     * options={"description"="Marquer les notifications comme lues", "permission"="AGENT:NOTIFICATIONS_READ"})
     */
    #[Route('/notifications/read', name: 'mark_agent_notifications_read', methods: ['POST'], options: ['description' => 'Marquer les notifications comme lues', 'permission' => 'AGENT:NOTIFICATIONS_READ'])]
    public function markNotificationsRead(Request $request): JsonResponse
    {
        $identifier = $this->getAgentIdentifier($request);
        try {
            $this->agentManager->markNotificationsAsRead($identifier, $this->getUser());
            return $this->json(['status' => 'success', 'message' => 'Notifications marquées comme lues'], 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/performances", name="agent_performances_private", methods={"GET", "POST"},
     * options={"description"="Performances d'un agent", "permission"="AGENT:PERFORMANCES"})
     */
    #[Route('/performances', name: 'agent_performances_private', methods: ['GET', 'POST'], options: ['description' => 'Performances d\'un agent', 'permission' => 'AGENT:PERFORMANCES'])]
    public function getPerformances(Request $request): JsonResponse
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
