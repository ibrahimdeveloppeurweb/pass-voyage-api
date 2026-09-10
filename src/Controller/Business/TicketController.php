<?php

namespace App\Controller\Business;

use App\Manager\Business\TicketManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TicketController extends AbstractController
{
    private $ticketManager;

    public function __construct(TicketManager $ticketManager)
    {
        $this->ticketManager = $ticketManager;
    }

    #[Route('/api/ticket', name: 'index_ticket_pub', methods: ['GET'])]
    #[Route('/api/ticket/', name: 'index_ticket_pub_slash', methods: ['GET'])]
    #[Route('/api/ticket/list', name: 'index_ticket_pub_list', methods: ['GET'])]
    #[Route('/api/private/ticket', name: 'index_ticket', methods: ['GET'], options: ['description' => 'Liste des billets/tickets', 'permission' => 'TICKET:READ'])]
    #[Route('/api/private/ticket/', name: 'index_ticket_slash', methods: ['GET'])]
    #[Route('/api/private/ticket/list', name: 'index_ticket_list', methods: ['GET'])]
    #[Route('/api/public/ticket', name: 'index_ticket_public', methods: ['GET'])]
    #[Route('/api/public/ticket/', name: 'index_ticket_public_slash', methods: ['GET'])]
    #[Route('/api/public/ticket/list', name: 'index_ticket_public_list', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $search = $request->query->get('search');
        $status = $request->query->get('status');
        $company = $request->query->get('company');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 0);

        $result = $this->ticketManager->getFormattedTicketList($search, $status, $company, $page, $limit);
        return $this->json([
            'success' => true, 
            'data' => $result['data'], 
            'tickets' => $result['data'],
            'kpis' => $result['kpis'],
            'meta' => $result['meta']
        ], 200);
    }
}
