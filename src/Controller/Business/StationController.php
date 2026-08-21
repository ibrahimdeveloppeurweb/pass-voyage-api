<?php

namespace App\Controller\Business;

use App\Exception\ExceptionApi;
use App\Helpers\JsonHelper;
use App\Manager\Business\StationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/api/private/station")
 */
#[Route(path: '/api/private/station')]
class StationController extends AbstractController
{
    private $stationManager;

    public function __construct(StationManager $stationManager)
    {
        $this->stationManager = $stationManager;
    }

    /**
     * @Route("", name="index_station_private", methods={"GET"},
     * options={"description"="Liste des gares d'embarquement", "permission"="STATION:READ"})
     */
    #[Route('', name: 'index_station_private', methods: ['GET'], options: ['description' => 'Liste des gares d\'embarquement', 'permission' => 'STATION:READ'])]
    #[Route('/', name: 'index_station_private_slash', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $data = $this->stationManager->getAllStationsFormatted();
        $response = (new JsonHelper($data, null, 'success', 200, []))->serialize();
        return $this->json($response, 200);
    }

    /**
     * @Route("/show", name="show_station_private", methods={"GET"},
     * options={"description"="Détails d'une gare", "permission"="STATION:READ"})
     */
    #[Route('/show', name: 'show_station_private', methods: ['GET'], options: ['description' => 'Détails d\'une gare', 'permission' => 'STATION:READ'])]
    public function show(Request $request): JsonResponse
    {
        $uuid = $request->query->get('uuid');
        $station = $this->stationManager->findStation($uuid);
        if (!$station) {
            $response = (new JsonHelper(null, 'Gare introuvable.', 'error', 404, []))->serialize();
            return $this->json($response, 404);
        }

        $item = $this->stationManager->formatStation($station);
        $response = (new JsonHelper($item, null, 'success', 200, []))->serialize();
        return $this->json($response, 200);
    }

    /**
     * @Route("/new", name="new_station_private", methods={"POST"},
     * options={"description"="Ajouter une gare", "permission"="STATION:EDIT"})
     */
    #[Route('/new', name: 'new_station_private', methods: ['POST'], options: ['description' => 'Ajouter une gare', 'permission' => 'STATION:EDIT'])]
    public function new(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent());
            $station = $this->stationManager->create($data);
            $item = $this->stationManager->formatStation($station);

            $response = (new JsonHelper($item, 'Gare ' . $station->getName() . ' ajoutée avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500);
        }
    }

    /**
     * @Route("/{uuid}/edit", name="edit_station_private", methods={"POST", "PUT"},
     * options={"description"="Modifier une gare", "permission"="STATION:EDIT"})
     */
    #[Route('/{uuid}/edit', name: 'edit_station_private', methods: ['POST', 'PUT'], options: ['description' => 'Modifier une gare', 'permission' => 'STATION:EDIT'])]
    public function edit(Request $request, string $uuid): JsonResponse
    {
        try {
            $data = json_decode($request->getContent());
            $station = $this->stationManager->update($uuid, $data);
            $item = $this->stationManager->formatStation($station);

            $response = (new JsonHelper($item, 'Gare ' . $station->getName() . ' modifiée avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500);
        }
    }

    /**
     * @Route("/{uuid}/delete", name="delete_station_private", methods={"DELETE", "POST"},
     * options={"description"="Supprimer une gare", "permission"="STATION:EDIT"})
     */
    #[Route('/{uuid}/delete', name: 'delete_station_private', methods: ['DELETE', 'POST'], options: ['description' => 'Supprimer une gare', 'permission' => 'STATION:EDIT'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $this->stationManager->deleteByUuid($uuid);
            $response = (new JsonHelper(null, 'Gare supprimée avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500);
        }
    }

    /**
     * @Route("/{uuid}/toggle", name="toggle_station_private", methods={"PATCH", "POST"},
     * options={"description"="Activer / Désactiver une gare", "permission"="STATION:EDIT"})
     */
    #[Route('/{uuid}/toggle', name: 'toggle_station_private', methods: ['PATCH', 'POST'], options: ['description' => 'Activer / Désactiver une gare', 'permission' => 'STATION:EDIT'])]
    public function toggle(string $uuid): JsonResponse
    {
        try {
            $station = $this->stationManager->toggleByUuid($uuid);
            $response = (new JsonHelper($station, 'Statut de la gare mis à jour.', 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500);
        }
    }
}
