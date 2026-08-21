<?php

namespace App\Controller\Business;

use App\Exception\ExceptionApi;
use App\Helpers\JsonHelper;
use App\Manager\Business\CompanyManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/api/private/company")
 */
#[Route(path: '/api/private/company')]
class CompanyController extends AbstractController
{
    private $companyManager;

    public function __construct(CompanyManager $companyManager)
    {
        $this->companyManager = $companyManager;
    }

    /**
     * @Route("", name="index_company_private", methods={"GET"},
     * options={"description"="Liste des compagnies partenaires", "permission"="COMPANY:READ"})
     */
    #[Route('', name: 'index_company_private', methods: ['GET'], options: ['description' => 'Liste des compagnies partenaires', 'permission' => 'COMPANY:READ'])]
    #[Route('/', name: 'index_company_private_slash', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $companies = $this->companyManager->getAllCompanies();
        $response = (new JsonHelper($companies, null, 'success', 200, []))->serialize();
        return $this->json($response, 200, [], ['groups' => ['company:read', 'admin', 'user']]);
    }

    /**
     * @Route("/show", name="show_company_private", methods={"GET"},
     * options={"description"="Détails d'une compagnie", "permission"="COMPANY:READ"})
     */
    #[Route('/show', name: 'show_company_private', methods: ['GET'], options: ['description' => 'Détails d\'une compagnie', 'permission' => 'COMPANY:READ'])]
    public function show(Request $request): JsonResponse
    {
        $uuid = $request->query->get('uuid');
        $company = $this->companyManager->findCompany($uuid);
        if (!$company) {
            $response = (new JsonHelper(null, 'Compagnie introuvable.', 'error', 404, []))->serialize();
            return $this->json($response, 404, [], ['groups' => ['company:read']]);
        }
        $response = (new JsonHelper($company, null, 'success', 200, []))->serialize();
        return $this->json($response, 200, [], ['groups' => ['company:read', 'admin', 'user']]);
    }

    /**
     * @Route("/new", name="new_company_private", methods={"POST"},
     * options={"description"="Ajouter une compagnie partenaire", "permission"="COMPANY:EDIT"})
     */
    #[Route('/new', name: 'new_company_private', methods: ['POST'], options: ['description' => 'Ajouter une compagnie partenaire', 'permission' => 'COMPANY:EDIT'])]
    public function new(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent());
            $company = $this->companyManager->create($data);
            $response = (new JsonHelper($company, 'Compagnie ' . $company->getName() . ' ajoutée avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['company:read', 'admin', 'user']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500, [], ['groups' => ['company:read']]);
        }
    }

    /**
     * @Route("/{uuid}/edit", name="edit_company_private", methods={"POST", "PUT"},
     * options={"description"="Modifier une compagnie", "permission"="COMPANY:EDIT"})
     */
    #[Route('/{uuid}/edit', name: 'edit_company_private', methods: ['POST', 'PUT'], options: ['description' => 'Modifier une compagnie', 'permission' => 'COMPANY:EDIT'])]
    public function edit(Request $request, string $uuid): JsonResponse
    {
        try {
            $data = json_decode($request->getContent());
            $company = $this->companyManager->update($uuid, $data);
            $response = (new JsonHelper($company, 'Compagnie ' . $company->getName() . ' modifiée avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['company:read', 'admin', 'user']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500, [], ['groups' => ['company:read']]);
        }
    }

    /**
     * @Route("/{uuid}/delete", name="delete_company_private", methods={"DELETE", "POST"},
     * options={"description"="Supprimer une compagnie", "permission"="COMPANY:EDIT"})
     */
    #[Route('/{uuid}/delete', name: 'delete_company_private', methods: ['DELETE', 'POST'], options: ['description' => 'Supprimer une compagnie', 'permission' => 'COMPANY:EDIT'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $this->companyManager->deleteByUuid($uuid);
            $response = (new JsonHelper(null, 'Compagnie supprimée avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['company:read']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500, [], ['groups' => ['company:read']]);
        }
    }

    /**
     * @Route("/{uuid}/toggle", name="toggle_company_private", methods={"PATCH", "POST"},
     * options={"description"="Activer / Désactiver une compagnie", "permission"="COMPANY:EDIT"})
     */
    #[Route('/{uuid}/toggle', name: 'toggle_company_private', methods: ['PATCH', 'POST'], options: ['description' => 'Activer / Désactiver une compagnie', 'permission' => 'COMPANY:EDIT'])]
    public function toggle(string $uuid): JsonResponse
    {
        try {
            $company = $this->companyManager->toggleByUuid($uuid);
            $response = (new JsonHelper($company, 'Statut mis à jour.', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['company:read', 'admin', 'user']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500, [], ['groups' => ['company:read']]);
        }
    }
}
