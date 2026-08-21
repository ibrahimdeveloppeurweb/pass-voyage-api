<?php

namespace App\Controller\Business;

use App\Manager\Business\CreditManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CreditController extends AbstractController
{
    private $creditManager;

    public function __construct(CreditManager $creditManager)
    {
        $this->creditManager = $creditManager;
    }

    /**
     * @Route("/api/private/credit", name="index_credit", methods={"GET"},
     * options={"description"="Liste des demandes de crédit", "permission"="CREDIT:LIST"})
     * @Route("/api/private/credit-request", name="index_credit_request", methods={"GET"})
     */
    #[Route('/api/credit', name: 'index_credit_pub', methods: ['GET'])]
    #[Route('/api/credit/', name: 'index_credit_pub_slash', methods: ['GET'])]
    #[Route('/api/private/credit', name: 'index_credit', methods: ['GET'], options: ['description' => 'Liste des demandes de crédit', 'permission' => 'CREDIT:LIST'])]
    #[Route('/api/private/credit/', name: 'index_credit_slash', methods: ['GET'])]
    #[Route('/api/private/credit-request', name: 'index_credit_request', methods: ['GET'])]
    #[Route('/api/private/credit-request/', name: 'index_credit_request_slash', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $search = $request->query->get('search');
        $status = $request->query->get('status');
        $company = $request->query->get('company');

        $requests = $this->creditManager->getAllCreditRequests($search, $status, $company);
        return $this->json($requests, 200, [], ['groups' => ['credit_request:read', 'creditrequest:read', 'credit:read', 'passenger:read', 'ticket:read', 'company:read']]);
    }

    /**
     * @Route("/api/private/credit/new", name="new_credit", methods={"POST"},
     * options={"description"="Créer une demande de crédit", "permission"="CREDIT:NEW"})
     * @Route("/api/private/credit-request/new", name="new_credit_request", methods={"POST"})
     */
    #[Route('/api/private/credit/new', name: 'new_credit', methods: ['POST'], options: ['description' => 'Créer une demande de crédit', 'permission' => 'CREDIT:NEW'])]
    #[Route('/api/private/credit-request/new', name: 'new_credit_request', methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $creditRequest = $this->creditManager->create($data);
            return $this->json($creditRequest, 201, [], ['groups' => ['credit_request:read', 'credit:read', 'passenger:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de la demande : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/api/private/credit/{uuid}/show", name="show_credit", methods={"GET"},
     * options={"description"="Détails d'une demande de crédit", "permission"="CREDIT:SHOW"})
     * @Route("/api/private/credit-request/{uuid}/show", name="show_credit_request", methods={"GET"})
     */
    #[Route('/api/credit/{uuid}/show', name: 'show_credit_pub_uuid', methods: ['GET'])]
    #[Route('/api/credit/{id}/show', name: 'show_credit_pub_id', methods: ['GET'])]
    #[Route('/api/private/credit/{uuid}/show', name: 'show_credit', methods: ['GET'], options: ['description' => 'Détails d\'une demande de crédit', 'permission' => 'CREDIT:SHOW'])]
    #[Route('/api/private/credit/{id}/show', name: 'show_credit_id', methods: ['GET'])]
    #[Route('/api/private/credit-request/{uuid}/show', name: 'show_credit_request', methods: ['GET'])]
    #[Route('/api/private/credit-request/{id}/show', name: 'show_credit_request_id', methods: ['GET'])]
    public function show(?string $uuid = null, ?string $id = null): JsonResponse
    {
        $identifier = $uuid ?? $id;
        $creditRequest = $this->creditManager->findCredit($identifier);
        if (!$creditRequest) {
            return $this->json(['message' => 'Demande de crédit introuvable'], 404);
        }

        return $this->json($creditRequest, 200, [], ['groups' => ['credit_request:read', 'credit:read', 'passenger:read', 'ticket:read']]);
    }

    /**
     * @Route("/api/private/credit/{uuid}/edit", name="edit_credit", methods={"PUT", "POST"},
     * options={"description"="Modifier une demande de crédit", "permission"="CREDIT:EDIT"})
     * @Route("/api/private/credit-request/{uuid}/edit", name="edit_credit_request", methods={"PUT", "POST"})
     */
    #[Route('/api/credit/{uuid}/edit', name: 'edit_credit_pub_uuid', methods: ['PUT', 'POST'])]
    #[Route('/api/credit/{id}/edit', name: 'edit_credit_pub_id', methods: ['PUT', 'POST'])]
    #[Route('/api/private/credit/{uuid}/edit', name: 'edit_credit', methods: ['PUT', 'POST'], options: ['description' => 'Modifier une demande de crédit', 'permission' => 'CREDIT:EDIT'])]
    #[Route('/api/private/credit/{id}/edit', name: 'edit_credit_id', methods: ['PUT', 'POST'])]
    #[Route('/api/private/credit-request/{uuid}/edit', name: 'edit_credit_request', methods: ['PUT', 'POST'])]
    #[Route('/api/private/credit-request/{id}/edit', name: 'edit_credit_request_id', methods: ['PUT', 'POST'])]
    public function edit(Request $request, ?string $uuid = null, ?string $id = null): JsonResponse
    {
        $identifier = $uuid ?? $id;
        $data = json_decode($request->getContent());
        try {
            $creditRequest = $this->creditManager->update($identifier, $data);
            return $this->json($creditRequest, 200, [], ['groups' => ['credit_request:read', 'credit:read', 'passenger:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de la modification : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/api/private/credit/{uuid}/approve", name="approve_credit", methods={"POST", "PUT"},
     * options={"description"="Approuver une demande de crédit et générer les billets", "permission"="CREDIT:APPROVE"})
     * @Route("/api/private/credit-request/{uuid}/approve", name="approve_credit_request", methods={"POST", "PUT"})
     */
    #[Route('/api/credit/{uuid}/approve', name: 'approve_credit_pub', methods: ['POST', 'PUT'])]
    #[Route('/api/credit/{id}/approve', name: 'approve_credit_pub_id', methods: ['POST', 'PUT'])]
    #[Route('/api/private/credit/{uuid}/approve', name: 'approve_credit', methods: ['POST', 'PUT'], options: ['description' => 'Approuver une demande de crédit et générer les billets', 'permission' => 'CREDIT:APPROVE'])]
    #[Route('/api/private/credit/{id}/approve', name: 'approve_credit_id', methods: ['POST', 'PUT'])]
    #[Route('/api/private/credit-request/{uuid}/approve', name: 'approve_credit_request', methods: ['POST', 'PUT'])]
    #[Route('/api/private/credit-request/{id}/approve', name: 'approve_credit_request_id', methods: ['POST', 'PUT'])]
    public function approve(?string $uuid = null, ?string $id = null): JsonResponse
    {
        $identifier = $uuid ?? $id;
        $creditRequest = $this->creditManager->findCredit($identifier);
        if (!$creditRequest) {
            return $this->json(['message' => 'Demande de crédit introuvable'], 404);
        }

        try {
            $creditRequest = $this->creditManager->approve($creditRequest);
            return $this->json($creditRequest, 200, [], ['groups' => ['credit_request:read', 'credit:read', 'passenger:read', 'ticket:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de la validation : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/api/private/credit/{uuid}/reject", name="reject_credit", methods={"POST", "PUT"},
     * options={"description"="Rejeter une demande de crédit", "permission"="CREDIT:REJECT"})
     * @Route("/api/private/credit-request/{uuid}/reject", name="reject_credit_request", methods={"POST", "PUT"})
     */
    #[Route('/api/credit/{uuid}/reject', name: 'reject_credit_pub', methods: ['POST', 'PUT'])]
    #[Route('/api/credit/{id}/reject', name: 'reject_credit_pub_id', methods: ['POST', 'PUT'])]
    #[Route('/api/private/credit/{uuid}/reject', name: 'reject_credit', methods: ['POST', 'PUT'], options: ['description' => 'Rejeter une demande de crédit', 'permission' => 'CREDIT:REJECT'])]
    #[Route('/api/private/credit/{id}/reject', name: 'reject_credit_id', methods: ['POST', 'PUT'])]
    #[Route('/api/private/credit-request/{uuid}/reject', name: 'reject_credit_request', methods: ['POST', 'PUT'])]
    #[Route('/api/private/credit-request/{id}/reject', name: 'reject_credit_request_id', methods: ['POST', 'PUT'])]
    public function reject(Request $request, ?string $uuid = null, ?string $id = null): JsonResponse
    {
        $identifier = $uuid ?? $id;
        $creditRequest = $this->creditManager->findCredit($identifier);
        if (!$creditRequest) {
            return $this->json(['message' => 'Demande de crédit introuvable'], 404);
        }

        $data = json_decode($request->getContent());
        $reason = $data->reason ?? null;

        try {
            $creditRequest = $this->creditManager->reject($creditRequest, $reason);
            return $this->json($creditRequest, 200, [], ['groups' => ['credit_request:read', 'credit:read', 'passenger:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors du rejet : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/api/private/credit/scan-ticket", name="scan_ticket", methods={"POST"},
     * options={"description"="Scanner et composter un billet par un agent", "permission"="TICKET:SCAN"})
     * @Route("/api/private/credit-request/scan-ticket", name="scan_ticket_legacy", methods={"POST"})
     */
    #[Route('/api/private/credit/scan-ticket', name: 'scan_ticket', methods: ['POST'], options: ['description' => 'Scanner et composter un billet par un agent', 'permission' => 'TICKET:SCAN'])]
    #[Route('/api/private/credit-request/scan-ticket', name: 'scan_ticket_legacy', methods: ['POST'])]
    public function scanTicket(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        if (!isset($data->qrCodeContent)) {
            return $this->json(['message' => 'Contenu du QR Code manquant'], 400);
        }

        try {
            $ticket = $this->creditManager->scanTicket($data->qrCodeContent);
            return $this->json([
                'message' => 'Billet scanné et validé avec succès',
                'ticket' => $ticket
            ], 200, [], ['groups' => ['ticket:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/api/private/credit/{uuid}/reimburse", name="reimburse_credit", methods={"POST"},
     * options={"description"="Enregistrer un remboursement de crédit", "permission"="CREDIT:REIMBURSE"})
     * @Route("/api/private/credit-request/{uuid}/reimburse", name="reimburse_credit_request", methods={"POST"})
     */
    #[Route('/api/credit/{uuid}/reimburse', name: 'reimburse_credit_pub_uuid', methods: ['POST'])]
    #[Route('/api/credit/{id}/reimburse', name: 'reimburse_credit_pub_id', methods: ['POST'])]
    #[Route('/api/private/credit/{uuid}/reimburse', name: 'reimburse_credit', methods: ['POST'], options: ['description' => 'Enregistrer un remboursement de crédit', 'permission' => 'CREDIT:REIMBURSE'])]
    #[Route('/api/private/credit/{id}/reimburse', name: 'reimburse_credit_id', methods: ['POST'])]
    #[Route('/api/private/credit-request/{uuid}/reimburse', name: 'reimburse_credit_request', methods: ['POST'])]
    #[Route('/api/private/credit-request/{id}/reimburse', name: 'reimburse_credit_request_id', methods: ['POST'])]
    public function reimburse(Request $request, ?string $uuid = null, ?string $id = null): JsonResponse
    {
        $identifier = $uuid ?? $id;
        $creditRequest = $this->creditManager->findCredit($identifier);
        if (!$creditRequest) {
            return $this->json(['message' => 'Demande de crédit introuvable'], 404);
        }

        $data = json_decode($request->getContent());
        if (!isset($data->amount) || (int)$data->amount <= 0) {
            return $this->json(['message' => 'Montant de remboursement invalide'], 400);
        }

        $paymentMethod = $data->paymentMethod ?? 'MOBILE_MONEY';

        try {
            $payment = $this->creditManager->reimburse($creditRequest, (int)$data->amount, $paymentMethod);
            return $this->json([
                'message' => 'Remboursement enregistré avec succès',
                'payment' => $payment
            ], 200);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors du remboursement : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/api/private/credit/{uuid}/delete", name="delete_credit", methods={"DELETE"},
     * options={"description"="Supprimer une demande de crédit", "permission"="CREDIT:DELETE"})
     * @Route("/api/private/credit-request/{uuid}/delete", name="delete_credit_request", methods={"DELETE"})
     */
    #[Route('/api/credit/{uuid}/delete', name: 'delete_credit_pub_uuid', methods: ['DELETE'])]
    #[Route('/api/credit/{id}/delete', name: 'delete_credit_pub_id', methods: ['DELETE'])]
    #[Route('/api/private/credit/{uuid}/delete', name: 'delete_credit', methods: ['DELETE'], options: ['description' => 'Supprimer une demande de crédit', 'permission' => 'CREDIT:DELETE'])]
    #[Route('/api/private/credit/{id}/delete', name: 'delete_credit_id', methods: ['DELETE'])]
    #[Route('/api/private/credit-request/{uuid}/delete', name: 'delete_credit_request', methods: ['DELETE'])]
    #[Route('/api/private/credit-request/{id}/delete', name: 'delete_credit_request_id', methods: ['DELETE'])]
    public function delete(?string $uuid = null, ?string $id = null): JsonResponse
    {
        $identifier = $uuid ?? $id;
        $creditRequest = $this->creditManager->findCredit($identifier);
        if (!$creditRequest) {
            return $this->json(['message' => 'Demande de crédit introuvable'], 404);
        }

        try {
            $this->creditManager->delete($creditRequest);
            return $this->json(['message' => 'Demande de crédit supprimée avec succès'], 200);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de la suppression : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/api/public/passenger/credit/pending", name="get_passenger_pending_credit_public", methods={"GET", "POST"})
     * @Route("/api/public/passenger/credit-request/pending", name="get_passenger_pending_credit_request_public", methods={"GET", "POST"})
     */
    #[Route('/api/private/passenger/credit/pending', name: 'get_passenger_pending_credit_private', methods: ['GET', 'POST'])]
    #[Route('/api/private/passenger/credit-request/pending', name: 'get_passenger_pending_credit_request_private', methods: ['GET', 'POST'])]
    #[Route('/api/public/passenger/credit/pending', name: 'get_passenger_pending_credit_public', methods: ['GET', 'POST'])]
    #[Route('/api/public/passenger/credit-request/pending', name: 'get_passenger_pending_credit_request_public', methods: ['GET', 'POST'])]
    public function getPendingCreditRequestPublic(Request $request): JsonResponse
    {
        $phone = $request->query->get('phone');
        if (!$phone) {
            $data = json_decode($request->getContent());
            $phone = $data->phone ?? $data->phoneNumber ?? null;
        }

        $result = $this->creditManager->getPendingCreditRequestData($this->getUser(), $phone);
        return $this->json($result, 200);
    }

    #[Route('/api/private/passenger/credit/submit', name: 'submit_passenger_credit_private', methods: ['POST'])]
    #[Route('/api/private/passenger/credit-request/submit', name: 'submit_passenger_credit_request_private', methods: ['POST'])]
    #[Route('/api/public/passenger/credit/submit', name: 'submit_passenger_credit_public', methods: ['POST'])]
    #[Route('/api/public/passenger/credit-request/submit', name: 'submit_passenger_credit_request_public', methods: ['POST'])]
    public function submitCreditRequestPublic(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        $res = $this->creditManager->submitPassengerCreditRequest($data, $this->getUser());
        return $this->json($res['payload'], $res['code']);
    }

    #[Route('/api/private/passenger/passes', name: 'get_passenger_passes_private', methods: ['GET', 'POST'])]
    #[Route('/api/public/passenger/passes', name: 'get_passenger_passes_public', methods: ['GET', 'POST'])]
    public function getPassengerPassesPublic(Request $request): JsonResponse
    {
        $phone = $request->query->get('phone');
        if (!$phone) {
            $data = json_decode($request->getContent());
            $phone = $data->phone ?? $data->phoneNumber ?? null;
        }

        $result = $this->creditManager->getPassengerPasses($this->getUser(), $phone);
        return $this->json($result, 200);
    }

    #[Route('/api/private/payment', name: 'index_payment', methods: ['GET'])]
    #[Route('/api/private/payment/', name: 'index_payment_slash', methods: ['GET'])]
    public function listPayments(Request $request): JsonResponse
    {
        $search = $request->query->get('search');
        $paymentMethod = $request->query->get('paymentMethod');
        $type = $request->query->get('type');

        $payments = $this->creditManager->getAllPayments($search, $paymentMethod, $type);
        return $this->json($payments, 200, [], ['groups' => ['payment:read', 'passenger:read', 'credit_request:read', 'credit:read']]);
    }
}
