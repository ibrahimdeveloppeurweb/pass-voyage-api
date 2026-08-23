<?php

namespace App\Controller\Dashboard;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Manager\Admin\DashboardManager;
use App\Helpers\JsonHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route(path: '/api/private/dashboard')]
class DashboardController extends AbstractController
{
    private $dashboardManager;

    public function __construct(DashboardManager $dashboardManager)
    {
        $this->dashboardManager = $dashboardManager;
    }

    #[Route('/main', name: 'dashboard_main', methods: ['GET'], options: ['description' => 'Statistiques générales du Passe Voyage Dashboard', 'permission' => 'DASHBOARD:MAIN'])]
    public function mainDashboard(Request $request): JsonResponse
    {
        try {
            $months = (int)$request->query->get('month', 6);
            $data = $this->dashboardManager->getMainDashboardData($months);
            $response = (new JsonHelper($data, null, 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (\Exception $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'server_error', 500, []))->serialize();
            return $this->json($response, 500);
        }
    }

    #[Route('/admin', name: 'dashboard_admin', methods: ['GET'], options: ['description' => 'Statistiques du tableau de bord d\'administration', 'permission' => 'DASHBOARD:ADMIN'])]
    public function adminDashboard(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardManager->getAdminDashboardData();
            $response = (new JsonHelper($data, null, 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (\Exception $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'server_error', 500, []))->serialize();
            return $this->json($response, 500);
        }
    }

    #[Route('/passe-voyage', name: 'dashboard_passe_voyage', methods: ['GET'], options: ['description' => 'Statistiques du tableau de bord principal Passe Voyage', 'permission' => 'DASHBOARD:PASSE_VOYAGE'])]
    public function passeVoyageDashboard(Request $request): JsonResponse
    {
        try {
            $date = $request->query->get('date');
            $data = $this->dashboardManager->getPasseVoyageDashboardData($date);
            $response = (new JsonHelper($data, null, 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (\Exception $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'server_error', 500, []))->serialize();
            return $this->json($response, 500);
        }
    }
}