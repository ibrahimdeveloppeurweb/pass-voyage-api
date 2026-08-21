<?php

namespace App\Controller\Admin;

use App\Entity\Extra\Path;
use App\Helpers\JsonHelper;
use App\Repository\Extra\PathRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/private/admin/path')]
class PathController extends AbstractController
{
    private $pathRepository;

    public function __construct(PathRepository $pathRepository)
    {
        $this->pathRepository = $pathRepository;
    }

    #[Route('', name: 'index_path', methods: ['GET'], options: ['description' => 'Liste des routes', 'permission' => 'PATH:LIST'])]
    #[Route('/', name: 'index_path_slash', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $type = $request->query->get('type');
        if ($type && isset(Path::TYPE[$type])) {
            $paths = $this->pathRepository->findBy(['type' => Path::TYPE[$type]]);
        } else {
            $allPaths = $this->pathRepository->findAll();
            // Exclure les routes publiques / auth qui n'ont pas besoin d'être configurées dans la matrice des permissions
            $paths = array_values(array_filter($allPaths, function($path) {
                $nom = strtolower($path->getNom() ?? '');
                $type = strtoupper($path->getType() ?? '');
                if ($type === 'AUTH') {
                    return false;
                }
                if (in_array($nom, ['login', 'logout', 'forgot_password', 'update_fcm_token', 'refresh_token', 'reset_password'])) {
                    return false;
                }
                return true;
            }));
        }
        $response = (new JsonHelper($paths, null, 'success', 200, []))->serialize();
        return $this->json($response, 200, [], ['groups' => ['path']]);
    }
}
