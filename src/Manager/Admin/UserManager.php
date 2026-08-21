<?php

namespace App\Manager\Admin;

use App\Entity\Admin\User;
use App\Exception\ExceptionApi;
use App\Repository\Admin\UserRepository;
use App\Repository\Extra\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserManager
{
    private $em;
    private $userRepository;
    private $roleRepository;
    private $passwordHasher;

    public function __construct(
        EntityManagerInterface $em,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->passwordHasher = $passwordHasher;
    }

    public function create(object $data): User
    {
        $email = trim($data->email ?? '');
        if (!$email) {
            throw new ExceptionApi('L\'adresse e-mail est obligatoire.', ['msg' => 'Email obligatoire'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if (!$existingUser) {
            $existingUser = $this->userRepository->findOneBy(['username' => $email]);
        }
        if ($existingUser) {
            // S'il existe déjà et qu'on essaie de créer, rediriger vers update
            $uuid = $existingUser->getUuid() ?? (string)$existingUser->getId();
            return $this->update($uuid, $data);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($email);
        
        $fullName = trim($data->nom ?? '');
        $parts = explode(' ', $fullName, 2);
        $user->setNom($parts[0] ?? $fullName);
        $user->setPrenom($parts[1] ?? ($data->prenom ?? ''));

        if (isset($data->password) && !empty($data->password)) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $data->password));
        } else {
            $user->setPassword($this->passwordHasher->hashPassword($user, 'PasseVoyage2026!'));
        }

        $user->setIsEnabled(true);
        $user->setIsFirst(false);
        $user->setType('ADMIN');

        // Attribuer les rôles
        if (isset($data->roles) && is_array($data->roles)) {
            foreach ($data->roles as $roleItem) {
                $roleVal = is_object($roleItem) ? ($roleItem->uuid ?? $roleItem->id ?? null) : (is_array($roleItem) ? ($roleItem['uuid'] ?? $roleItem['id'] ?? null) : $roleItem);
                $role = $this->findRoleByVal($roleVal);
                if ($role) {
                    $user->addDroit($role);
                }
            }
        } elseif (isset($data->role)) {
            $role = $this->findRoleByVal($data->role);
            if ($role) {
                $user->addDroit($role);
            }
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function update(string $uuid, object $data): User
    {
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['uuid' => $uuid]);
        if (!$user && is_numeric($uuid)) {
            $user = $this->userRepository->find((int)$uuid);
        }
        if (!$user && isset($data->email)) {
            $user = $this->userRepository->findOneBy(['email' => trim($data->email)]);
        }
        if (!$user) {
            throw new ExceptionApi('Cet utilisateur est introuvable.', ['msg' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        if (isset($data->email) && !empty($data->email)) {
            $newEmail = trim($data->email);
            $checkUser = $this->userRepository->findOneBy(['email' => $newEmail]);
            if ($checkUser && $checkUser->getId() !== $user->getId()) {
                throw new ExceptionApi('Un autre utilisateur existe déjà avec cet e-mail.', ['msg' => 'Email existe déjà'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $user->setEmail($newEmail);
            $user->setUsername($newEmail);
        }

        if (isset($data->nom)) {
            $fullName = trim($data->nom);
            $parts = explode(' ', $fullName, 2);
            $user->setNom($parts[0] ?? $fullName);
            $user->setPrenom($parts[1] ?? ($data->prenom ?? $user->getPrenom()));
        }

        if (isset($data->password) && !empty($data->password)) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $data->password));
        }

        // Mettre à jour les rôles
        if (isset($data->roles) && is_array($data->roles)) {
            $user->getDroits()->clear();
            foreach ($data->roles as $roleItem) {
                $roleVal = is_object($roleItem) ? ($roleItem->uuid ?? $roleItem->id ?? null) : (is_array($roleItem) ? ($roleItem['uuid'] ?? $roleItem['id'] ?? null) : $roleItem);
                $role = $this->findRoleByVal($roleVal);
                if ($role) {
                    $user->addDroit($role);
                }
            }
        } elseif (isset($data->role)) {
            $user->getDroits()->clear();
            $role = $this->findRoleByVal($data->role);
            if ($role) {
                $user->addDroit($role);
            }
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function delete(User $user): User
    {
        $this->em->remove($user);
        $this->em->flush();
        return $user;
    }

    private function findRoleByVal($val)
    {
        if (!$val) return null;
        
        $role = $this->roleRepository->findOneBy(['uuid' => $val]);
        if ($role) return $role;

        $role = $this->roleRepository->findOneBy(['nom' => $val]);
        if ($role) return $role;

        if (is_numeric($val)) {
            $role = $this->roleRepository->find((int)$val);
            if ($role) return $role;
        }

        return null;
    }
}
