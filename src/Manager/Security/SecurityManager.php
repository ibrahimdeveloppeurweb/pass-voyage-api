<?php

namespace App\Manager\Security;

use App\Utils\Constants;
use App\Entity\Admin\User;
use App\Exception\ExceptionApi;
use App\Entity\Extra\RefreshToken;
use App\Repository\Admin\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\Extra\RefreshTokenRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SecurityManager
{
    private $em;
    private $userRepository;
    private $passwordHasher;
    private $jwtManager;
    private $refreshTokenRepository;

    private $user;
    private $username;
    private $roles;
    private $isFirstUser;
    private $refreshToken;

    public function __construct(
        EntityManagerInterface $em,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager,
        RefreshTokenRepository $refreshTokenRepository,
        TokenStorageInterface $tokenStorage
    ) {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->jwtManager = $jwtManager;
        $this->refreshTokenRepository = $refreshTokenRepository;

        if ($tokenStorage->getToken()) {
            $this->user = $tokenStorage->getToken()->getUser();
        }
    }

    /**
     * @param Request $request
     * @return $this
     * @throws ExceptionApi
     */
    public function checkCredential(Request $request): self
    {
        $body = json_decode($request->getContent(), true);

        if (!isset($body['username']) && !isset($body['password'])) {
            throw new ExceptionApi(
                "L'email ou le numéro de téléphone et le mot de passe sont obligatoires.",
                ['msg' => "L'email ou le numéro de téléphone et le mot de passe sont obligatoires."],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $username = $body['username'] ?? null;
        $password = $body['password'] ?? null;

        if (!$username) {
            throw new ExceptionApi(
                "L'email ou le numéro de téléphone est obligatoire.",
                ['msg' => "L'email ou le numéro de téléphone est obligatoire."],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if (!$password) {
            throw new ExceptionApi(
                'Le password est obligatoire.',
                ['msg' => 'Le password est obligatoire.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $user = $this->userRepository->findOneBy(['username' => $username]);
            if (!$user) {
                $user = $this->userRepository->findOneBy(['telephone' => $username]);
            }
        } catch (NonUniqueResultException $e) {
            $user = null;
        }

        if (!$user) {
            throw new ExceptionApi(
                'Cet utilisateur est introuvable',
                ['msg' => "Cet utilisateur est introuvable"],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $isValid = $this->passwordHasher->isPasswordValid($user, $password);
        if (!$isValid) {
            throw new ExceptionApi(
                'Accès incorrectes.',
                ['msg' => ['Le mot de passe est incorrect.']],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if (method_exists($user, 'isEnabled') && !$user->isEnabled()) {
            throw new ExceptionApi(
                'Accès refusé',
                ['msg' => ["Votre compte a été désactivé. Veuillez contacter votre administrateur."]],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Vérifier les autorisations d'accès plateforme ADMIN
        if (isset($body['type']) && $body['type'] === Constants::PLATFORMS['ADMIN']) {
            $allowedRoles = [Constants::USER_ROLES['ADMIN'], Constants::USER_ROLES['AGENT']];
            $userRole = $user->getRoles()[0] ?? '';
            if (!in_array($userRole, $allowedRoles, true)) {
                throw new ExceptionApi(
                    'Accès refusé',
                    ['msg' => ["Cet utilisateur n'a pas accès à la plateforme d'administration Passe Voyage."]],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }

        // Vérifier les autorisations d'accès MOBILE ou PARTENAIRE
        if (isset($body['type']) && $body['type'] === Constants::PLATFORMS['PARTNER_PORTAL']) {
            if ($user->getType() !== Constants::USER_TYPE['PARTNER']) {
                throw new ExceptionApi(
                    'Accès refusé',
                    ['msg' => ["Cet utilisateur n'a pas accès à l'application partenaire Passe Voyage."]],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }

        $this->username = $user->getUsername();
        $this->isFirstUser = $user->getIsFirst();
        $this->roles = $user->getRoles()[0] ?? 'ROLE_USER';

        // Générer un RefreshToken (Si Entité RefreshToken existe et configurée dans l'User)
        if (method_exists($user, 'generateRefreshToken')) {
            $refreshToken = $user->generateRefreshToken();
            $this->em->persist($refreshToken);
            $this->em->flush();
            $this->refreshToken = (string)$refreshToken->getId();
        }

        return $this;
    }

    public function getAccessToken(): array
    {
        $user = $this->userRepository->findOneBy(['username' => $this->username]);
        $token = $this->jwtManager->create($user);

        // Update last login
        if (method_exists($user, 'setIsOnline')) {
            $user->setIsOnline(true);
        }
        if (method_exists($user, 'setLastLogin')) {
            $user->setLastLogin(new \DateTime());
        }
        $this->em->persist($user);
        $this->em->flush();

        $data = [
            'firstname' => $user->getPrenom(),
            'name' => $user->getNom(),
            'isFirst' => $user->getIsFirst(),
            'photo' => $user->getAvatar(),
            'contact' => $user->getTelephone(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'role' => $this->roles,
            'isFirstUser' => $this->isFirstUser,
            'uuid' => $user->getUuid() ?? null,
            'permissions' => $user->getPermissions(),
            'token' => $token,
        ];

        if ($this->refreshToken) {
            $data['refreshToken'] = $this->refreshToken;
        }

        return $data;
    }

    public function forgot(object $data): ?User
    {
        if (!isset($data->email) || empty($data->email)) {
            throw new ExceptionApi(
                'Veuillez renseigner correctement le nom d\'utilisateur.',
                ['msg' => 'Veuillez renseigner correctement le nom d\'utilisateur.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $user = $this->userRepository->findOneBy(['username' => $data->email]);
        if (!$user) {
            throw new ExceptionApi(
                'Il n\'existe aucun utilisateur avec ces identifiants.',
                ['msg' => 'Il n\'existe aucun utilisateur avec ces identifiants.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $password = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        
        if (method_exists($user, 'setLastLogin')) {
            $user->setLastLogin(null);
        }
        
        $this->em->persist($user);
        $this->em->flush();

        // TODO: Mettre en place l'envoi de l'email ici (ex: MailerInterface)

        return $user;
    }

    public function logout(object $data, Request $request)
    {
        try {
            if (isset($data->refreshToken)) {
                $refreshToken = $this->refreshTokenRepository->find($data->refreshToken);
                if ($refreshToken) {
                    $this->em->remove($refreshToken);
                }
            }

            if (isset($data->user)) {
                $user = $this->userRepository->findOneByUuid($data->user);
                if ($user) {
                    if (method_exists($user, 'setLastLogin')) {
                        $user->setLastLogin(new \DateTime());
                    }
                    if (method_exists($user, 'setIsOnline')) {
                        $user->setIsOnline(false);
                    }
                    $this->em->persist($user);
                }
            }
            
            $this->em->flush();
            return true;
        } catch (\Exception $e) {
            throw new ExceptionApi(
                'Erreur lors de la déconnexion.',
                ['msg' => 'Erreur lors de la déconnexion.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function editPassword(object $data, User $user): User
    {
        $this->checkRequirements($data, $user, 'edit');
        $user->setPassword($this->passwordHasher->hashPassword($user, $data->new));
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    public function resetPassword(object $data): User
    {
        $user = $this->userRepository->findOneByUuid($data->user);
        $this->checkRequirements($data, $user, 'reset');
        $user->setPassword($this->passwordHasher->hashPassword($user, $data->new));
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    public function checkRequirements(object $data, ?User $user, string $verif)
    {
        if (!$user) {
            throw new ExceptionApi(
                "Cet utilisateur est introuvable.",
                ['msg' => "Cet utilisateur est introuvable"],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($verif === 'edit') {
            if (!isset($data->actuel) || !$this->passwordHasher->isPasswordValid($user, $data->actuel)) {
                throw new ExceptionApi(
                    "L'ancien mot de passe est incorrect.",
                    ['msg' => "L'ancien mot de passe est incorrect."],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            if (!isset($data->new) || !isset($data->confirme) || $data->new !== $data->confirme) {
                throw new ExceptionApi(
                    'Les mots de passes ne correspondent pas.',
                    ['msg' => 'Les mots de passes ne correspondent pas.'],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }
    }
}
