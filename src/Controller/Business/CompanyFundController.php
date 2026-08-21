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
}
