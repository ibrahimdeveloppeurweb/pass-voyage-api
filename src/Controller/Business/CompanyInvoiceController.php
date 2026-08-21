<?php

namespace App\Controller\Business;

use App\Exception\ExceptionApi;
use App\Helpers\JsonHelper;
use App\Manager\Business\CompanyInvoiceManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/api/private/company-invoice")
 */
#[Route(path: '/api/private/company-invoice')]
class CompanyInvoiceController extends AbstractController
{
    private $companyInvoiceManager;

    public function __construct(CompanyInvoiceManager $companyInvoiceManager)
    {
        $this->companyInvoiceManager = $companyInvoiceManager;
    }

    /**
     * @Route("", name="index_company_invoice_private", methods={"GET"},
     * options={"description"="Liste des factures et reversements", "permission"="COMPANY:READ"})
     */
    #[Route('', name: 'index_company_invoice_private', methods: ['GET'], options: ['description' => 'Liste des factures et reversements', 'permission' => 'COMPANY:READ'])]
    #[Route('/', name: 'index_company_invoice_private_slash', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $invoices = $this->companyInvoiceManager->getAllInvoices();

        $response = (new JsonHelper($invoices, null, 'success', 200, []))->serialize();
        return $this->json($response, 200, [], ['groups' => ['company_invoice:read', 'company:read', 'admin', 'user']]);
    }

    /**
     * @Route("/new", name="new_company_invoice_private", methods={"POST"},
     * options={"description"="Générer une facture mensuelle", "permission"="COMPANY:EDIT"})
     */
    #[Route('/new', name: 'new_company_invoice_private', methods: ['POST'], options: ['description' => 'Générer une facture mensuelle', 'permission' => 'COMPANY:EDIT'])]
    public function new(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent());
            $invoice = $this->companyInvoiceManager->create($data);
            $response = (new JsonHelper($invoice, 'Facture générée avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['company_invoice:read', 'company:read', 'admin', 'user']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500, [], ['groups' => ['company_invoice:read']]);
        }
    }

    /**
     * @Route("/{uuid}/toggle-paid", name="toggle_paid_company_invoice_private", methods={"PATCH", "POST"},
     * options={"description"="Marquer une facture comme payée / en attente", "permission"="COMPANY:EDIT"})
     */
    #[Route('/{uuid}/toggle-paid', name: 'toggle_paid_company_invoice_private', methods: ['PATCH', 'POST'], options: ['description' => 'Marquer une facture comme payée / en attente', 'permission' => 'COMPANY:EDIT'])]
    public function togglePaid(string $uuid): JsonResponse
    {
        try {
            $invoice = $this->companyInvoiceManager->togglePaidByUuid($uuid);
            $response = (new JsonHelper($invoice, 'Statut de paiement mis à jour.', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['company_invoice:read', 'company:read', 'admin', 'user']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500, [], ['groups' => ['company_invoice:read']]);
        }
    }

    /**
     * @Route("/{uuid}/delete", name="delete_company_invoice_private", methods={"DELETE", "POST"},
     * options={"description"="Supprimer une facture", "permission"="COMPANY:EDIT"})
     */
    #[Route('/{uuid}/delete', name: 'delete_company_invoice_private', methods: ['DELETE', 'POST'], options: ['description' => 'Supprimer une facture', 'permission' => 'COMPANY:EDIT'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $this->companyInvoiceManager->deleteByUuid($uuid);
            $response = (new JsonHelper(null, 'Facture supprimée avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['company_invoice:read']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500, [], ['groups' => ['company_invoice:read']]);
        }
    }
}
