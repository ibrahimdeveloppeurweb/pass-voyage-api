<?php

namespace App\Controller\Business;

use App\Entity\Business\Company;
use App\Entity\Business\CompanyFund;
use App\Exception\ExceptionApi;
use App\Helpers\JsonHelper;
use App\Manager\Business\CompanyFundManager;
use App\Repository\Business\CompanyFundRepository;
use App\Repository\Business\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/api/private/company-fund")
 */
#[Route(path: '/api/private/company-fund')]
class CompanyFundController extends AbstractController
{
    private $companyFundRepository;
    private $companyRepository;
    private $companyFundManager;
    private $em;

    public function __construct(
        CompanyFundRepository $companyFundRepository,
        CompanyRepository $companyRepository,
        CompanyFundManager $companyFundManager,
        EntityManagerInterface $em
    ) {
        $this->companyFundRepository = $companyFundRepository;
        $this->companyRepository = $companyRepository;
        $this->companyFundManager = $companyFundManager;
        $this->em = $em;
    }

    /**
     * @Route("", name="index_company_fund_private", methods={"GET"},
     * options={"description"="Liste des fonds de roulement des compagnies", "permission"="COMPANY:READ"})
     */
    #[Route('', name: 'index_company_fund_private', methods: ['GET'], options: ['description' => 'Liste des fonds de roulement des compagnies', 'permission' => 'COMPANY:READ'])]
    #[Route('/', name: 'index_company_fund_private_slash', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $funds = $this->companyFundRepository->findBy([], ['createdAt' => 'DESC']);
        $response = (new JsonHelper($funds, null, 'success', 200, []))->serialize();
        return $this->json($response, 200, [], ['groups' => ['company_fund:read', 'company:read', 'admin', 'user']]);
    }

    /**
     * @Route("/new", name="new_company_fund_private", methods={"POST"},
     * options={"description"="Allouer un fonds de roulement", "permission"="COMPANY:EDIT"})
     */
    #[Route('/new', name: 'new_company_fund_private', methods: ['POST'], options: ['description' => 'Allouer un fonds de roulement', 'permission' => 'COMPANY:EDIT'])]
    public function new(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent());
            $fund = $this->companyFundManager->create($data);
            $response = (new JsonHelper($fund, 'Fonds alloué avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['company_fund:read', 'company:read', 'admin', 'user']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500, [], ['groups' => ['company_fund:read']]);
        }
    }

    /**
     * @Route("/{uuid}/edit", name="edit_company_fund_private", methods={"POST", "PUT"},
     * options={"description"="Modifier un fonds de roulement", "permission"="COMPANY:EDIT"})
     */
    #[Route('/{uuid}/edit', name: 'edit_company_fund_private', methods: ['POST', 'PUT'], options: ['description' => 'Modifier un fonds de roulement', 'permission' => 'COMPANY:EDIT'])]
    public function edit(Request $request, string $uuid): JsonResponse
    {
        try {
            $data = json_decode($request->getContent());
            $fund = $this->companyFundManager->update($uuid, $data);
            $response = (new JsonHelper($fund, 'Fonds mis à jour avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['company_fund:read', 'company:read', 'admin', 'user']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500, [], ['groups' => ['company_fund:read']]);
        }
    }

    /**
     * @Route("/{uuid}/delete", name="delete_company_fund_private", methods={"DELETE", "POST"},
     * options={"description"="Supprimer un fonds", "permission"="COMPANY:EDIT"})
     */
    #[Route('/{uuid}/delete', name: 'delete_company_fund_private', methods: ['DELETE', 'POST'], options: ['description' => 'Supprimer un fonds', 'permission' => 'COMPANY:EDIT'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $fund = $this->companyFundRepository->findOneBy(['uuid' => $uuid]);
            if (!$fund && is_numeric($uuid)) {
                $fund = $this->companyFundRepository->find((int)$uuid);
            }
            if (!$fund) {
                throw new ExceptionApi('Fonds introuvable.', ['msg' => 'Fonds introuvable'], Response::HTTP_NOT_FOUND);
            }
            $this->companyFundManager->delete($fund);
            $response = (new JsonHelper(null, 'Fonds supprimé avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['company_fund:read']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500, [], ['groups' => ['company_fund:read']]);
        }
    }

    /**
     * @Route("/{uuid}/show", name="show_company_fund_private", methods={"GET"},
     * options={"description"="Détails d'un fonds de roulement", "permission"="COMPANY:READ"})
     */
    #[Route('/{uuid}/show', name: 'show_company_fund_private', methods: ['GET'], options: ['description' => 'Détails d\'un fonds de roulement', 'permission' => 'COMPANY:READ'])]
    public function show(string $uuid): JsonResponse
    {
        try {
            $fund = $this->companyFundRepository->findOneBy(['uuid' => $uuid]);
            if (!$fund && is_numeric($uuid)) {
                $fund = $this->companyFundRepository->find((int)$uuid);
            }
            if (!$fund) {
                throw new ExceptionApi('Fonds introuvable.', ['msg' => 'Fonds introuvable'], Response::HTTP_NOT_FOUND);
            }
            $response = (new JsonHelper($fund, null, 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['company_fund:read', 'company:read', 'admin', 'user']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500, [], ['groups' => ['company_fund:read']]);
        }
    }

    /**
     * @Route("/{uuid}/recharge", name="recharge_company_fund_private", methods={"POST"},
     * options={"description"="Recharger un fonds de roulement", "permission"="COMPANY:EDIT"})
     */
    #[Route('/{uuid}/recharge', name: 'recharge_company_fund_private', methods: ['POST'], options: ['description' => 'Recharger un fonds de roulement', 'permission' => 'COMPANY:EDIT'])]
    public function recharge(Request $request, string $uuid): JsonResponse
    {
        try {
            $data = json_decode($request->getContent());
            $amount = (float)($data->amount ?? $data->montant ?? 0);
            $reason = $data->reason ?? $data->motif ?? $data->comment ?? null;
            $performedBy = $data->performedBy ?? 'Administrateur';

            $fund = $this->companyFundManager->rechargeFund($uuid, $amount, $reason, $performedBy);
            $response = (new JsonHelper($fund, 'Fonds rechargé avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['company_fund:read', 'company:read', 'admin', 'user']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 400, [], ['groups' => ['company_fund:read']]);
        } catch (\Throwable $e) {
            return $this->json(['message' => 'Erreur lors du rechargement : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/{uuid}/history", name="history_company_fund_private", methods={"GET", "POST"},
     * options={"description"="Historique des mouvements d'un fonds", "permission"="COMPANY:READ"})
     */
    #[Route('/{uuid}/history', name: 'history_company_fund_private', methods: ['GET', 'POST'], options: ['description' => 'Historique des mouvements d\'un fonds', 'permission' => 'COMPANY:READ'])]
    public function history(Request $request, string $uuid): JsonResponse
    {
        try {
            $type = $request->query->get('type');
            $search = $request->query->get('search');
            $history = $this->companyFundManager->getHistory($uuid, ['type' => $type, 'search' => $search]);
            
            $formatted = [];
            foreach ($history as $h) {
                $formatted[] = [
                    'id' => $h->getId(),
                    'uuid' => $h->getUuid(),
                    'type' => $h->getType(),
                    'amount' => $h->getAmount(),
                    'previousBalance' => $h->getPreviousBalance(),
                    'newBalance' => $h->getNewBalance(),
                    'reference' => $h->getReference(),
                    'description' => $h->getDescription(),
                    'performedBy' => $h->getPerformedBy(),
                    'createdAt' => $h->getCreatedAt() ? $h->getCreatedAt()->format('Y-m-d H:i:s') : null,
                ];
            }

            $response = (new JsonHelper($formatted, null, 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['company_fund_history:read', 'company_fund:read']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500, [], ['groups' => ['company_fund:read']]);
        } catch (\Throwable $e) {
            return $this->json(['message' => 'Erreur lors de la récupération : ' . $e->getMessage()], 400);
        }
    }
}
