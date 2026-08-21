<?php

namespace App\Controller\Admin;

use App\Helpers\JsonHelper;
use App\Exception\ExceptionApi;
use App\Manager\Admin\UserManager;
use App\Repository\Admin\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/private/admin/user')]
class UserController extends AbstractController
{
    private $userRepository;
    private $userManager;
    private $em;

    public function __construct(
        UserRepository $userRepository,
        UserManager $userManager,
        EntityManagerInterface $em
    ) {
        $this->userRepository = $userRepository;
        $this->userManager = $userManager;
        $this->em = $em;
    }

    #[Route('', name: 'index_user_admin', methods: ['GET'], options: ['description' => 'Liste des utilisateurs admin', 'permission' => 'USER:ADMIN:LIST'])]
    #[Route('/', name: 'index_user_admin_slash', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->userRepository->findByType('ADMIN');
        if (empty($users)) {
            $users = $this->userRepository->findAll();
        }
        $response = (new JsonHelper($users, null, 'success', 200, []))->serialize();
        return $this->json($response, 200, [], ['groups' => ['user', 'role']]);
    }

    #[Route('/new', name: 'new_user_admin', methods: ['POST'], options: ['description' => 'Ajouter un nouveau utilisateur', 'permission' => 'USER:ADMIN:NEW'])]
    public function new(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent());
            $user = $this->userManager->create($data);
            $response = (new JsonHelper($user, 'Utilisateur ' . $user->getNom() . ' ajouté avec succès', 'success', 201, []))->serialize();
            return $this->json($response, 201, [], ['groups' => ['user', 'role']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 422, [], ['groups' => ['user', 'role']]);
        } catch (\Exception $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'server_error', 500, []))->serialize();
            return $this->json($response, 500, [], ['groups' => ['user', 'role']]);
        }
    }

    #[Route('/show', name: 'show_user_admin', methods: ['GET'], options: ['description' => 'Détails d\'un utilisateur', 'permission' => 'USER:ADMIN:SHOW'])]
    public function show(Request $request): JsonResponse
    {
        $uuid = $request->query->get('uuid');
        $user = $this->userRepository->findOneBy(['uuid' => $uuid]);
        if (!$user && is_numeric($uuid)) {
            $user = $this->userRepository->find((int)$uuid);
        }
        if (!$user) {
            $response = (new JsonHelper(null, 'Utilisateur introuvable.', 'error', 404, []))->serialize();
            return $this->json($response, 404, [], ['groups' => ['user', 'role']]);
        }
        $response = (new JsonHelper($user, null, 'success', 200, []))->serialize();
        return $this->json($response, 200, [], ['groups' => ['user', 'role']]);
    }

    #[Route('/{uuid}/edit', name: 'edit_user_admin', methods: ['POST', 'PUT'], options: ['description' => 'Modifier un utilisateur', 'permission' => 'USER:ADMIN:EDIT'])]
    public function edit(Request $request, string $uuid): JsonResponse
    {
        try {
            $data = json_decode($request->getContent());
            $user = $this->userManager->update($uuid, $data);
            $response = (new JsonHelper($user, 'Utilisateur ' . $user->getNom() . ' modifié avec succès', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['user', 'role']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 422, [], ['groups' => ['user', 'role']]);
        } catch (\Exception $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'server_error', 500, []))->serialize();
            return $this->json($response, 500, [], ['groups' => ['user', 'role']]);
        }
    }

    #[Route('/{uuid}/delete', name: 'delete_user_admin', methods: ['DELETE', 'POST'], options: ['description' => 'Supprimer un utilisateur', 'permission' => 'USER:ADMIN:DELETE'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $user = $this->userRepository->findOneBy(['uuid' => $uuid]);
            if (!$user && is_numeric($uuid)) {
                $user = $this->userRepository->find((int)$uuid);
            }
            if (!$user) {
                $response = (new JsonHelper(null, 'Utilisateur introuvable.', 'error', 404, []))->serialize();
                return $this->json($response, 404, [], ['groups' => ['user', 'role']]);
            }
            $this->userManager->delete($user);
            $response = (new JsonHelper(null, 'Utilisateur supprimé avec succès.', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['user', 'role']]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, 422, [], ['groups' => ['user', 'role']]);
        }
    }

    #[Route('/{uuid}/toggle', name: 'toggle_user_admin', methods: ['PATCH', 'POST'], options: ['description' => 'Activer/Désactiver un utilisateur', 'permission' => 'USER:ADMIN:EDIT'])]
    public function toggle(string $uuid): JsonResponse
    {
        try {
            $user = $this->userRepository->findOneBy(['uuid' => $uuid]);
            if (!$user && is_numeric($uuid)) {
                $user = $this->userRepository->find((int)$uuid);
            }
            if (!$user) {
                $response = (new JsonHelper(null, 'Utilisateur introuvable.', 'error', 404, []))->serialize();
                return $this->json($response, 404, [], ['groups' => ['user', 'role']]);
            }
            $user->setIsEnabled(!$user->isEnabled());
            $this->em->persist($user);
            $this->em->flush();
            $status = $user->isEnabled() ? 'activé' : 'désactivé';
            $response = (new JsonHelper($user, "Le compte a été {$status} avec succès.", 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['user', 'role']]);
        } catch (\Exception $e) {
            $response = (new JsonHelper(null, 'Erreur lors du changement de statut.', 'server_error', 500, []))->serialize();
            return $this->json($response, 500, [], ['groups' => ['user', 'role']]);
        }
    }
}
