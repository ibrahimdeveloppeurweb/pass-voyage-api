<?php

namespace App\Controller\Business;

use App\Exception\ExceptionApi;
use App\Helpers\JsonHelper;
use App\Manager\Business\RouteManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/api/private/route")
 */
#[Route(path: '/api/private/route')]
class RouteController extends AbstractController
{
    private $routeManager;

    public function __construct(RouteManager $routeManager)
    {
        $this->routeManager = $routeManager;
    }

    /**
     * @Route("", name="index_route_private", methods={"GET"},
     * options={"description"="Liste des trajets", "permission"="ROUTE:READ"})
     */
    #[Route('', name: 'index_route_private', methods: ['GET'], options: ['description' => 'Liste des trajets', 'permission' => 'ROUTE:READ'])]
    #[Route('/', name: 'index_route_private_slash', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $data = $this->routeManager->getAllRoutesFormatted();
        $response = (new JsonHelper($data, null, 'success', 200, []))->serialize();
        return $this->json($response, 200);
    }

    /**
     * @Route("/show", name="show_route_private", methods={"GET"},
     * options={"description"="Détails d'un trajet", "permission"="ROUTE:READ"})
     */
    #[Route('/show', name: 'show_route_private', methods: ['GET'], options: ['description' => 'Détails d\'un trajet', 'permission' => 'ROUTE:READ'])]
    public function show(Request $request): JsonResponse
    {
        $uuid = $request->query->get('uuid');
        $route = $this->routeManager->findRoute($uuid);
        if (!$route) {
            $response = (new JsonHelper(null, 'Trajet introuvable.', 'error', 404, []))->serialize();
            return $this->json($response, 404);
        }

        $item = $this->routeManager->formatRoute($route);
        $response = (new JsonHelper($item, null, 'success', 200, []))->serialize();
        return $this->json($response, 200);
    }

    /**
     * @Route("/new", name="new_route_private", methods={"POST"},
     * options={"description"="Créer un trajet", "permission"="ROUTE:EDIT"})
     */
    #[Route('/new', name: 'new_route_private', methods: ['POST'], options: ['description' => 'Créer un trajet', 'permission' => 'ROUTE:EDIT'])]
    public function new(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent());
            $route = $this->routeManager->create($data);
            $item = $this->routeManager->formatRoute($route);

            $response = (new JsonHelper($item, 'Trajet ' . $route->getTitle() . ' créé avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500);
        }
    }

    /**
     * @Route("/{uuid}/edit", name="edit_route_private", methods={"POST", "PUT"},
     * options={"description"="Modifier un trajet", "permission"="ROUTE:EDIT"})
     */
    #[Route('/{uuid}/edit', name: 'edit_route_private', methods: ['POST', 'PUT'], options: ['description' => 'Modifier un trajet', 'permission' => 'ROUTE:EDIT'])]
    public function edit(Request $request, string $uuid): JsonResponse
    {
        try {
            $data = json_decode($request->getContent());
            $route = $this->routeManager->update($uuid, $data);
            $item = $this->routeManager->formatRoute($route);

            $response = (new JsonHelper($item, 'Trajet ' . $route->getTitle() . ' modifié avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500);
        }
    }

    /**
     * @Route("/{uuid}/delete", name="delete_route_private", methods={"DELETE", "POST"},
     * options={"description"="Supprimer un trajet", "permission"="ROUTE:EDIT"})
     */
    #[Route('/{uuid}/delete', name: 'delete_route_private', methods: ['DELETE', 'POST'], options: ['description' => 'Supprimer un trajet', 'permission' => 'ROUTE:EDIT'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $this->routeManager->deleteByUuid($uuid);
            $response = (new JsonHelper(null, 'Trajet supprimé avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500);
        }
    }

    /**
     * @Route("/{uuid}/toggle", name="toggle_route_private", methods={"PATCH", "POST"},
     * options={"description"="Activer / Désactiver un trajet", "permission"="ROUTE:EDIT"})
     */
    #[Route('/{uuid}/toggle', name: 'toggle_route_private', methods: ['PATCH', 'POST'], options: ['description' => 'Activer / Désactiver un trajet', 'permission' => 'ROUTE:EDIT'])]
    public function toggle(string $uuid): JsonResponse
    {
        try {
            $route = $this->routeManager->toggleByUuid($uuid);
            $response = (new JsonHelper($route, 'Statut du trajet mis à jour.', 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500);
        }
    }
}
