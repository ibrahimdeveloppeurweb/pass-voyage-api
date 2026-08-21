<?php

namespace App\Fixtures;

use App\Entity\Extra\Role;
use App\Entity\Admin\User;

use App\Helpers\RouteHelper;
use Doctrine\Persistence\ObjectManager;
use App\Repository\Admin\UserRepository;
use App\Repository\Extra\PathRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixture de création du Super Administrateur Passe Voyage.
 * Cette fixture est idempotente : elle ne crée l'utilisateur que s'il n'existe pas déjà.
 *
 * Commande : php bin/console doctrine:fixtures:load --append
 */
class UserFixture extends Fixture
{
    private $passwordHasher;
    private $userRepository;
    private $pathRepository;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        PathRepository $pathRepository,
        )
    {
        $this->passwordHasher = $passwordHasher;
        $this->userRepository = $userRepository;
        $this->pathRepository = $pathRepository;
    }

    public function load(ObjectManager $manager): void
    {
        // Idempotence : ne crée le super admin que s'il n'existe pas encore
        $check = $this->userRepository->findOneBy(['isFirst' => true]);
        if ($check) {
            return;
        }

        $pathsRow = $this->pathRepository->findAll();

        // ── 1. Création du Rôle Super Administrateur ──────────────────────────
        $role = new Role();
        $role
            ->setNom('Super Administrateur Passe Voyage')
            ->setDescription('Accès complet à toutes les fonctionnalités de la plateforme Passe Voyage')
            ->setCreatedAt(new \DateTime('now'))
            ->setIsFirst(true)
            ;

        // Assignation de toutes les routes ADMIN et des MENUS au rôle
        $apiPaths = RouteHelper::ADMIN_ROUTE($pathsRow);
        $menuPaths = RouteHelper::MENU_ROUTE($pathsRow);
        $paths = array_merge($apiPaths, $menuPaths);
        
        foreach ($paths as $path) {
            $path->addRole($role);
            $manager->persist($path);
        }
        $manager->persist($role);

        // ── 3. Création du compte Utilisateur (Super Admin) ────────────────────
        $user = new User();
        $user
            ->setUsername('admin@passe-voyage.com')
            ->setPassword($this->passwordHasher->hashPassword($user, 'PasseVoyage@2024!'))
            ->setEmail('admin@passe-voyage.com')
            ->setNom('Passe')
            ->setPrenom('Voyage')
            ->setType(User::TYPE['ADMIN'])
            ->setIsFirst(true)
            ->setCreatedAt(new \DateTime('now'))
            ;
        $manager->persist($user);

        // ── 4. Liaison du User au Rôle ─────────────────────────────────────────
        $role->addUser($user);
        $manager->persist($role);

        $manager->flush();

        echo "\n✅ Super Administrateur Passe Voyage créé avec succès !";
        echo "\n   Login    : admin@passe-voyage.com";
        echo "\n   Password : PasseVoyage@2024!\n";
    }
}