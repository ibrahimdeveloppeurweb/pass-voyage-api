<?php

namespace App\Controller\Business;

use App\Exception\ExceptionApi;
use App\Helpers\JsonHelper;
use App\Manager\Business\TariffManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/api/private/tariff")
 */
#[Route(path: '/api/private/tariff')]
class TariffController extends AbstractController
{
    private $tariffManager;

    public function __construct(TariffManager $tariffManager)
    {
        $this->tariffManager = $tariffManager;
    }

    /**
     * @Route("", name="index_tariff_private", methods={"GET"},
     * options={"description"="Grille tarifaire de base", "permission"="TARIFF:READ"})
     */
    #[Route('', name: 'index_tariff_private', methods: ['GET'], options: ['description' => 'Grille tarifaire de base', 'permission' => 'TARIFF:READ'])]
    #[Route('/', name: 'index_tariff_private_slash', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $data = $this->tariffManager->getAllTariffsFormatted();
        $response = (new JsonHelper($data, null, 'success', 200, []))->serialize();
        return $this->json($response, 200);
    }

    /**
     * @Route("/show", name="show_tariff_private", methods={"GET"},
     * options={"description"="Détails d'un tarif", "permission"="TARIFF:READ"})
     */
    #[Route('/show', name: 'show_tariff_private', methods: ['GET'], options: ['description' => 'Détails d\'un tarif', 'permission' => 'TARIFF:READ'])]
    public function show(Request $request): JsonResponse
    {
        $uuid = $request->query->get('uuid');
        $tariff = $this->tariffManager->findTariff($uuid);
        if (!$tariff) {
            $response = (new JsonHelper(null, 'Tarif introuvable.', 'error', 404, []))->serialize();
            return $this->json($response, 404);
        }

        $item = $this->tariffManager->formatTariff($tariff);
        $response = (new JsonHelper($item, null, 'success', 200, []))->serialize();
        return $this->json($response, 200);
    }

    /**
     * @Route("/new", name="new_tariff_private", methods={"POST"},
     * options={"description"="Définir un tarif de base", "permission"="TARIFF:EDIT"})
     */
    #[Route('/new', name: 'new_tariff_private', methods: ['POST'], options: ['description' => 'Définir un tarif de base', 'permission' => 'TARIFF:EDIT'])]
    public function new(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent());
            $tariff = $this->tariffManager->create($data);
            $item = $this->tariffManager->formatTariff($tariff);

            $response = (new JsonHelper($item, 'Tarif défini avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500);
        }
    }

    /**
     * @Route("/{uuid}/edit", name="edit_tariff_private", methods={"POST", "PUT"},
     * options={"description"="Modifier un tarif", "permission"="TARIFF:EDIT"})
     */
    #[Route('/{uuid}/edit', name: 'edit_tariff_private', methods: ['POST', 'PUT'], options: ['description' => 'Modifier un tarif', 'permission' => 'TARIFF:EDIT'])]
    public function edit(Request $request, string $uuid): JsonResponse
    {
        try {
            $data = json_decode($request->getContent());
            $tariff = $this->tariffManager->update($uuid, $data);
            $item = $this->tariffManager->formatTariff($tariff);

            $response = (new JsonHelper($item, 'Tarif modifié avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500);
        }
    }

    /**
     * @Route("/{uuid}/delete", name="delete_tariff_private", methods={"DELETE", "POST"},
     * options={"description"="Supprimer un tarif", "permission"="TARIFF:EDIT"})
     */
    #[Route('/{uuid}/delete', name: 'delete_tariff_private', methods: ['DELETE', 'POST'], options: ['description' => 'Supprimer un tarif', 'permission' => 'TARIFF:EDIT'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $this->tariffManager->deleteByUuid($uuid);
            $response = (new JsonHelper(null, 'Tarif supprimé avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500);
        }
    }
}
