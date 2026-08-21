<?php

namespace App\Manager\Extra;

use App\Entity\Admin\User;
use App\Entity\Extra\Path;
use App\Entity\Extra\Role;
use App\Exception\ExceptionApi;
use App\Repository\Admin\UserRepository;
use App\Repository\Extra\PathRepository as ExtraPathRepository;
use App\Repository\Extra\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RoleManager
{
    private $user;
    private $em;
    private $roleRepository;
    private $pathRepository;
    private $userRepository;

    public function __construct(
        EntityManagerInterface $em,
        ExtraPathRepository $pathRepository,
        UserRepository $userRepository,
        TokenStorageInterface $tokenStorage,
        RoleRepository $roleRepository
    ) {
        $this->em = $em;
        $this->pathRepository = $pathRepository;
        $this->roleRepository = $roleRepository;
        $this->userRepository = $userRepository;
        if ($tokenStorage->getToken()) {
            $this->user = $tokenStorage->getToken()->getUser();
        }
    }

    public function create($data): Role
    {
        $nom = trim($data->nom ?? '');
        if ($nom) {
            $existingRole = $this->roleRepository->findOneBy(['nom' => $nom]);
            if ($existingRole) {
                $uuid = $existingRole->getUuid() ?? (string)$existingRole->getId();
                return $this->update($data, $uuid);
            }
        }

        $role = new Role();
        $this->add($data->nom, $data->description ?? '', $role);
        
        if (isset($data->paths) && is_array($data->paths)) {
            foreach ($data->paths as $item) {
                $this->attachPathToRole($role, $item);
            }
        }

        $this->em->persist($role);
        $this->em->flush();
        return $role;
    }

    public function update($data, string $uuid): Role
    {
        /** @var Role $role */
        $role = $this->roleRepository->findOneBy(['uuid' => $uuid]);
        if (!$role && is_numeric($uuid)) {
            $role = $this->roleRepository->find((int)$uuid);
        }
        if (!$role && isset($data->id) && is_numeric($data->id)) {
            $role = $this->roleRepository->find((int)$data->id);
        }
        if (!$role && isset($data->nom)) {
            $role = $this->roleRepository->findOneBy(['nom' => trim($data->nom)]);
        }

        if (!$role) {
            $role = new Role();
        }

        // Vider proprement la collection sur l'entité propriétaire
        $role->getPaths()->clear();

        // Réattacher la nouvelle sélection
        if (isset($data->paths) && is_array($data->paths)) {
            foreach ($data->paths as $pathData) {
                $this->attachPathToRole($role, $pathData);
            }
        }

        $this->add($data->nom ?? $role->getNom(), $data->description ?? $role->getDescription(), $role);
        $this->em->persist($role);
        $this->em->flush();
        return $role;
    }

    public function delete(string $uuid): Role
    {
        $role = $this->roleRepository->findOneBy(['uuid' => $uuid]);
        if (!$role && is_numeric($uuid)) {
            $role = $this->roleRepository->find((int)$uuid);
        }
        if (!$role) {
            throw new ExceptionApi('Rôle introuvable', ['msg' => 'Rôle introuvable'], Response::HTTP_NOT_FOUND);
        }
        if (!$role->getUsers()->isEmpty()) {
            throw new ExceptionApi('Vous ne pouvez pas supprimer ce rôle car il est attribué à un ou plusieurs utilisateurs.', ['msg' => 'Rôle attribué'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $this->em->remove($role);
        $this->em->flush();
        return $role;
    }

    public function add($nom, $description, Role $role): Role
    {
        $role->setNom($nom);
        $role->setDescription($description);
        return $role;
    }

    private function attachPathToRole(Role $role, $item): void
    {
        $vals = [];
        if (is_object($item)) {
            if (isset($item->uuid)) $vals[] = $item->uuid;
            if (isset($item->permission)) $vals[] = $item->permission;
            if (isset($item->nom)) $vals[] = $item->nom;
            if (isset($item->id)) $vals[] = $item->id;
        } elseif (is_array($item)) {
            if (isset($item['uuid'])) $vals[] = $item['uuid'];
            if (isset($item['permission'])) $vals[] = $item['permission'];
            if (isset($item['nom'])) $vals[] = $item['nom'];
            if (isset($item['id'])) $vals[] = $item['id'];
        } else {
            $vals[] = $item;
        }

        foreach ($vals as $val) {
            $path = $this->findPathByVal($val);
            if ($path) {
                $role->addPath($path);
                break;
            }
        }
    }

    private function findPathByVal($val): ?Path
    {
        if (!$val) return null;

        $path = $this->pathRepository->findOneBy(['uuid' => $val]);
        if ($path) return $path;
        
        $path = $this->pathRepository->findOneBy(['permission' => $val]);
        if ($path) return $path;

        $path = $this->pathRepository->findOneBy(['nom' => $val]);
        if ($path) return $path;

        if (is_numeric($val)) {
            $path = $this->pathRepository->find((int)$val);
            if ($path) return $path;
        }

        return null;
    }
}