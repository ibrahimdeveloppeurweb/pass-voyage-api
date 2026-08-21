<?php

namespace App\Controller\Admin;

use App\Helpers\JsonHelper;
use App\Exception\ExceptionApi;
use App\Manager\Extra\RoleManager;
use App\Repository\Extra\RoleRepository;
use App\Utils\TypeVariable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/private/admin/role')]
class RoleController extends AbstractController
{
    private $roleRepository;
    private $roleManager;

    public function __construct(
        RoleRepository $roleRepository,
        RoleManager $roleManager
    ) {
        $this->roleRepository = $roleRepository;
        $this->roleManager = $roleManager;
    }

    #[Route('', name: 'index_role_admin', methods: ['GET'], options: ['description' => 'Liste des roles admin', 'permission' => 'ROLE:ADMIN:LIST'])]
    #[Route('/', name: 'index_role_admin_slash', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $roles = $this->roleRepository->findBy([], ['createdAt' => 'DESC']);
        $response = (new JsonHelper($roles, null, 'success', 200, []))->serialize();
        return $this->json($response, 200, [], ['groups' => ['role', 'path']]);
    }

    #[Route('/show', name: 'role_show_admin', methods: ['GET'], options: ['description' => "Details d'un role admin", 'permission' => 'ROLE:ADMIN:SHOW'])]
    public function show(Request $request): JsonResponse
    {
        $data = json_decode(json_encode($request->query->all()));
        try {
            $role = null;
            if (isset($data->uuid) && !empty($data->uuid)) {
                $role = $this->roleRepository->findOneByUuid($data->uuid);
            }
            if (!$role) {
                $response = (new JsonHelper(null, 'Rôle introuvable', 'error', 404, []))->serialize();
                return $this->json($response, 404, [], ['groups' => ['role', 'path']]);
            }
            $response = (new JsonHelper($role, null, 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['role', 'path']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500, [], ['groups' => ['role', 'path']]);
        }
    }

    #[Route('/new', name: 'new_role_admin', methods: ['POST'], options: ['description' => 'Ajouter un nouveau role admin', 'permission' => 'ROLE:ADMIN:NEW'])]
    public function new(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent());
            $role = $this->roleManager->create($data);
            $response = (new JsonHelper($role, 'Rôle ' . $role->getNom() . ' ajouté avec succès', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['role', 'path']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500, [], ['groups' => ['role', 'path']]);
        }
    }

    #[Route('/{uuid}/edit', name: 'edit_role_admin', methods: ['POST', 'PUT'], options: ['description' => 'Modifier un role admin', 'permission' => 'ROLE:ADMIN:EDIT'])]
    public function edit(Request $request, string $uuid): JsonResponse
    {
        try {
            $data = json_decode($request->getContent());
            $role = $this->roleManager->update($data, $uuid);
            $response = (new JsonHelper($role, 'Le rôle ' . $role->getNom() . ' a été modifié avec succès', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['role', 'path']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500, [], ['groups' => ['role', 'path']]);
        }
    }

    #[Route('/{uuid}/delete', name: 'delete_role_admin', methods: ['DELETE', 'POST'], options: ['description' => 'Supprimer un role admin', 'permission' => 'ROLE:ADMIN:DELETE'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $role = $this->roleManager->delete($uuid);
            $response = (new JsonHelper($role, 'Le rôle ' . $role->getNom() . ' a été supprimé avec succès', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['role', 'path']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 500, [], ['groups' => ['role', 'path']]);
        }
    }
}
