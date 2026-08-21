<?php

namespace App\Events;

use App\Entity\Admin\User;
use App\Repository\Admin\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsEventListener(event: 'lexik_jwt_authentication.on_authentication_success', method: 'attachDataToToken', priority: 10)]
class LoginSubscriber
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function attachDataToToken(AuthenticationSuccessEvent $event): void
    {
        $array = $event->getData();
        /** @var User $user */
        $user = $event->getUser();
        
        if (!$user instanceof UserInterface) {
            return;
        }

        $userEntity = $this->userRepository->findOneBy(['username' => $user->getUserIdentifier()]);
        
        if (!$userEntity) {
            return;
        }

        $data = [
            'data' => [
                'token' => $array['token'],
                'isFirstUser' => method_exists($userEntity, 'getIsFirst') ? $userEntity->getIsFirst() : null,
                'role' => $userEntity->getRoles()[0] ?? 'ROLE_USER',
                'refreshToken' => '',
            ],
            'status' => 'success',
            'code' => 200,
            'message' => 'Connexion réussie'
        ];
        
        $event->setData($data);
    }
}
