<?php

namespace App\Command;

use App\Entity\Extra\Path;
use App\Repository\Extra\PathRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'z:g:path',
    description: 'Generate path routes for Passe Voyage'
)]
class CommandPath extends Command
{
    private $em;
    private $route;
    private $pathRepository;

    public function __construct(
        RouterInterface $route,
        EntityManagerInterface $em,
        PathRepository $pathRepository
    ) {
        parent::__construct();
        $this->em = $em;
        $this->route = $route;
        $this->pathRepository = $pathRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $routes = $this->route->getRouteCollection()->all();
        
        foreach ($routes as $nom => $route) {
            $options = $route->getOptions();
            $pathStr = $route->getPath();
            
            // On ne s'intéresse qu'aux routes API pour la gestion des rôles Passe Voyage
            if (preg_match('#^/api/#', $pathStr) || preg_match('#^/printer/#', $pathStr)) {
                
                $description = $options["description"] ?? null;
                $permission = $options["permission"] ?? null;
                
                // On enregistre la route uniquement si elle a une description
                if ($description !== null && $description !== "null") {
                    $role = $this->pathRepository->findOneBy(['nom' => $nom]);
                    $path = (!$role) ? new Path() : $role;
                    if (!$path->getUuid()) {
                        $path->setUuid(\Ramsey\Uuid\Uuid::uuid4()->toString());
                    }
                    $path->setNom($nom);
                    $path->setChemin($pathStr);
                    $path->setLibelle($description);
                    $path->setPermission($permission);

                    // Typage métier pour Passe Voyage
                    if (preg_match('#^/api/auth#', $pathStr) || preg_match('#^/api/login#', $pathStr) || preg_match('#^/api/logout#', $pathStr)) {
                        $path->setType(Path::TYPE['AUTH'] ?? 'AUTH');
                    } elseif (preg_match('#^/api/private/admin#', $pathStr) || preg_match('#^/printer/admin#', $pathStr)) {
                        $path->setType(Path::TYPE['ADMIN']);
                    } elseif (preg_match('#^/api/private/client#', $pathStr) || preg_match('#^/api/private/partner#', $pathStr) || preg_match('#^/api/private/company#', $pathStr)) {
                        $path->setType(Path::TYPE['CLIENT']);
                    } elseif (preg_match('#^/api/private/extra#', $pathStr)) {
                        $path->setType(Path::TYPE['EXTRA']);
                    } else {
                        // Valeur par défaut
                        $path->setType(Path::TYPE['CLIENT']);
                    }
                    
                    $this->em->persist($path);
                }
            }
        }
        
        $this->em->flush();
        $output->writeln("La génération des routes API pour Passe Voyage s'est déroulée avec succès.");
        
        return Command::SUCCESS;
    }
}