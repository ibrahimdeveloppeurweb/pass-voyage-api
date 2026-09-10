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

    // ==========================================
    // ROUTES POUR L'ESPACE COMPAGNIE (PORTAIL)
    // ==========================================

    /**
     * @Route("/espace/dashboard", name="company_espace_dashboard", methods={"GET"},
     * options={"description"="API - Tableau de bord de la compagnie", "permission"="COMPAGNIE_PORTAIL:DASHBOARD"})
     */
    #[Route('/espace/dashboard', name: 'company_espace_dashboard', methods: ['GET'], options: ['description' => 'API - Dashboard de l\'espace compagnie', 'permission' => 'COMPAGNIE_PORTAIL:HOME'])]
    public function espaceDashboard(\Symfony\Component\HttpFoundation\Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            $company = method_exists($user, 'getCompany') ? $user->getCompany() : null;
            $period = $request->query->get('period', 'all');

            $data = $this->companyManager->getEspaceDashboardData($company, $period);

            $response = (new JsonHelper($data, 'Dashboard Compagnie', 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'error', $e->getCode(), []))->serialize();
            return $this->json($response, $e->getCode());
        }
    }

    /**
     * @Route("/espace/activites-gares", name="company_espace_activites_gares", methods={"GET"},
     * options={"description"="API - Statistiques des Gares de la compagnie", "permission"="COMPAGNIE_PORTAIL:ACTIVITES"})
     */
    #[Route('/espace/activites-gares', name: 'company_espace_activites_gares', methods: ['GET'], options: ['description' => 'API - Statistiques des Gares de la compagnie', 'permission' => 'COMPAGNIE_PORTAIL:ACTIVITES'])]
    public function espaceActivitesGares(\Symfony\Component\HttpFoundation\Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            $company = method_exists($user, 'getCompany') ? $user->getCompany() : null;

            $startDate = $request->query->get('startDate');
            $endDate = $request->query->get('endDate');
            $search = $request->query->get('search');
            $status = $request->query->get('status');

            $data = $this->companyManager->getEspaceActivitesGaresData($company, $startDate, $endDate, $search, $status);

            $response = (new JsonHelper($data, 'Activités et Gares', 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'error', $e->getCode(), []))->serialize();
            return $this->json($response, $e->getCode());
        }
    }

    /**
     * @Route("/espace/activites-gares/{uuid}/stats", name="company_espace_activites_gares_stats", methods={"GET"},
     * options={"description"="API - Statistiques d'une Gare de la compagnie", "permission"="COMPAGNIE_PORTAIL:ACTIVITES"})
     */
    #[Route('/espace/activites-gares/{uuid}/stats', name: 'company_espace_activites_gares_stats', methods: ['GET'], options: ['description' => 'API - Statistiques d\'une Gare de la compagnie', 'permission' => 'COMPAGNIE_PORTAIL:ACTIVITES'])]
    public function espaceActivitesGaresStats(string $uuid): JsonResponse
    {
        try {
            $user = $this->getUser();
            $company = method_exists($user, 'getCompany') ? $user->getCompany() : null;

            $data = $this->companyManager->getStationStats($uuid, $company);

            $response = (new JsonHelper($data, 'Statistiques de la gare', 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'error', $e->getCode(), []))->serialize();
            return $this->json($response, $e->getCode());
        }
    }

    /**
     * @Route("/espace/billets-scannes", name="company_espace_billets_scannes", methods={"GET"},
     * options={"description"="API - Historique des billets scannés", "permission"="COMPAGNIE_PORTAIL:BILLETS"})
     */
    #[Route('/espace/billets-scannes', name: 'company_espace_billets_scannes', methods: ['GET'], options: ['description' => 'API - Historique des billets scannés', 'permission' => 'COMPAGNIE_PORTAIL:BILLETS'])]
    public function espaceBilletsScannes(\Symfony\Component\HttpFoundation\Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            $company = method_exists($user, 'getCompany') ? $user->getCompany() : null;

            $search = $request->query->get('search');
            $station = $request->query->get('station');
            $startDate = $request->query->get('startDate');
            $endDate = $request->query->get('endDate');
            $page = (int) $request->query->get('page', 1);
            $limit = (int) $request->query->get('limit', 10);

            $result = $this->companyManager->getEspaceBilletsScannesData($company, $search, $station, $startDate, $endDate, $page, $limit);

            $response = (new JsonHelper($result['data'], 'Billets Scannés', 'success', 200, $result['meta']))->serialize();
            return $this->json($response, 200);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'error', $e->getCode(), []))->serialize();
            return $this->json($response, $e->getCode());
        }
    }

    /**
     * @Route("/espace/finances", name="company_espace_finances", methods={"GET"},
     * options={"description"="API - Relevé et finances de la compagnie", "permission"="COMPAGNIE_PORTAIL:FINANCES"})
     */
    #[Route('/espace/finances', name: 'company_espace_finances', methods: ['GET'], options: ['description' => 'API - Relevé et finances de la compagnie', 'permission' => 'COMPAGNIE_PORTAIL:FINANCES'])]
    public function espaceFinances(): JsonResponse
    {
        try {
            $user = $this->getUser();
            $company = method_exists($user, 'getCompany') ? $user->getCompany() : null;

            $data = $this->companyManager->getEspaceFinancesData($company);

            $response = (new JsonHelper($data, 'Solde et Finances', 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'error', $e->getCode(), []))->serialize();
            return $this->json($response, $e->getCode());
        }
    }
}
