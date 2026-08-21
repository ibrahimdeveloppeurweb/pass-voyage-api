<?php

namespace App\Controller\Security;

use App\Entity\Admin\User;
use App\Helpers\JsonHelper;
use App\Exception\ExceptionApi;
use App\Entity\Extra\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;
use App\Manager\Security\SecurityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Extra\RefreshTokenRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SecurityController extends AbstractController
{
    private $em;
    private $securityManager;
    private $refreshTokenRepository;

    public function __construct(
        EntityManagerInterface $em,
        SecurityManager $securityManager,
        RefreshTokenRepository $refreshTokenRepository
    ) {
        $this->em = $em;
        $this->securityManager = $securityManager;
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    #[Route(path: '/api/login', name: 'login', methods: ['POST', 'GET'], options: ['description' => 'Se connecter'])]
    public function login(Request $request)
    {
        try {
            $data = $this->securityManager->checkCredential($request)->getAccessToken();
            $response = (new JsonHelper($data, 'Connexion réussie', 'success', 200, []))->serialize();
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, $e->getCode());
        } catch (\Exception $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'error', 500, []))->serialize();
            return $this->json($response, 500);
        }
        
        return $this->json($response);
    }

    #[Route(path: '/api/auth/me', name: 'auth_me', methods: ['GET'])]
    public function me()
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json((new JsonHelper(null, 'Utilisateur non connecté', 'unauthorized', 401, []))->serialize(), 401);
        }

        $response = (new JsonHelper($user, 'Utilisateur récupéré', 'success', 200, []))->serialize();
        return $this->json($response, 200, [], ['groups' => ["user"]]);
    }

    #[Route(path: '/api/logout', name: 'logout', methods: ['POST'], options: ['description' => 'Se deconnecter'])]
    public function logout(Request $request)
    {
        $content = json_decode($request->getContent());
        try {
            $this->securityManager->logout($content, $request);
            $response = (new JsonHelper(null, 'Déconnexion réussie', 'success', 200, []))->serialize();
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, $e->getCode());
        }
        return $this->json($response);
    }

    #[Route(path: '/api/forgot', name: 'forgot_password', methods: ['POST', 'GET'], options: ['description' => 'Mot de passe oublié'])]
    public function forgot(Request $request)
    {
        try {
            $data = json_decode($request->getContent());
            $user = $this->securityManager->forgot($data);
            $response = (new JsonHelper($user, 'Email envoyé avec succès', 'success', 200, []))->serialize();
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, $e->getCode());
        }
        return $this->json($response, 200, [], ['groups' => ["user"]]);
    }

    #[Route(path: '/api/token/refresh', name: 'api_refresh', methods: ['POST'])]
    public function refreshTokenAction(Request $request, JWTTokenManagerInterface $jwtManager)
    {
        $content = json_decode($request->getContent(), true);
        if (!$request->headers->has('Authorization')) {
            return $this->json(['status' => 'error', 'message' => 'No Token Found'], 401);
        }
        if (!isset($content['refreshToken'])) {
            return $this->json(['status' => 'error', 'message' => 'Request is empty (refreshToken manquant)'], 400);
        }

        /** @var RefreshToken $refreshToken */
        $refreshToken = $this->refreshTokenRepository->find($content['refreshToken']);
        if (!$refreshToken) {
            return $this->json(['status' => 'error', 'message' => 'Unknown refresh token'], 404);
        }

        $now = new \DateTime();
        if ($refreshToken->getExpireAt() < $now) {
            $when = clone $refreshToken->getExpireAt();
            $this->em->remove($refreshToken);
            $this->em->flush();
            return $this->json(['status' => 'error', 'message' => 'Refresh token expired at ' . $when->format('d-m-Y h:i:s')], 401);
        }

        /** @var User $user */
        $user = $refreshToken->getCreateBy();
        if (!$user) {
            return $this->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $this->em->remove($refreshToken);
        
        $newRefreshToken = null;
        if (method_exists($user, 'generateRefreshToken')) {
            $newRefreshToken = $user->generateRefreshToken();
            $this->em->persist($newRefreshToken);
        }

        $token = $jwtManager->create($user);
        $this->em->flush();

        $data = [
            'token' => $token,
            'isFirstUser' => $user->getIsFirst(),
            'role' => $user->getRoles()[0] ?? 'ROLE_USER',
        ];

        if ($newRefreshToken) {
            $data['refreshToken'] = (string)$newRefreshToken->getId();
        }

        $response = (new JsonHelper($data, 'Token rafraîchi avec succès', 'success', 200, []))->serialize();
        return $this->json($response);
    }

    #[Route(path: '/api/auth/edit/password', name: 'edit_passord', methods: ['POST'], options: ['description' => 'Changer de mot de passe', 'permission' => 'USER:PASSWORD:EDIT'])]
    public function editPassword(Request $request)
    {
        try {
            $user = $this->getUser();
            $data = json_decode($request->getContent());
            $user = $this->securityManager->editPassword($data, $user);
            $response = (new JsonHelper($user, 'Votre mot de passe a été modifié avec succès', 'success', 200, []))->serialize();
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, $e->getCode());
        }
        return $this->json($response, 200, [], ['groups' => ["user"]]);
    }

    #[Route(path: '/api/auth/rest/password', name: 'reset_passord', methods: ['POST'], options: ['description' => 'Réinitialiser un mot de passe', 'permission' => 'USER:PASSWORD:RESET'])]
    public function resetPassword(Request $request)
    {
        try {
            $data = json_decode($request->getContent());
            $user = $this->securityManager->resetPassword($data);
            $response = (new JsonHelper($user, 'Le mot de passe a été réinitialisé avec succès', 'success', 200, []))->serialize();
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, $e->getCode());
        }
        return $this->json($response, 200, [], ['groups' => ["user"]]);
    }

    #[Route(path: '/api/auth/fcm-token', name: 'update_fcm_token', methods: ['POST'], options: ['description' => 'Mettre à jour le token FCM'])]
    public function updateFcmToken(Request $request)
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            if (!$user) {
                throw new ExceptionApi("Utilisateur non connecté", [], 401);
            }

            $data = json_decode($request->getContent());
            if (!isset($data->fcmToken)) {
                throw new ExceptionApi("Token FCM manquant", [], 400);
            }

            if (method_exists($user, 'setFcmToken')) {
                $user->setFcmToken($data->fcmToken);
                $this->em->persist($user);
                $this->em->flush();
            }

            $response = (new JsonHelper($user, 'Token FCM mis à jour avec succès', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ["user"]]);
        } catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, $e->getCode());
        }
    }
}