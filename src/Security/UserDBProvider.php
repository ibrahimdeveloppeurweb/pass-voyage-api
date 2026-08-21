<?php
namespace App\Security;

use App\Repository\Admin\UserRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserDBProvider implements UserProviderInterface
{
    private $userRepository;
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            $user = $this->userRepository->findOneBy(['username' => $identifier]);
            if (!$user) {
                throw new \Exception("User not found");
            }
        } catch (\Exception $exception) {
            throw new \Symfony\Component\Security\Core\Exception\UserNotFoundException();
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === 'App\Entity\Admin\User';
    }
}
