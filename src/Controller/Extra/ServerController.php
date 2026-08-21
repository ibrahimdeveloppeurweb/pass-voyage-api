<?php

namespace App\Controller\Extra;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route(path="/api/public/server")
 */
#[Route(path: '/api/public/server')]
class ServerController extends AbstractController
{
    /**
     * @Route("/ip", name="server_ip", methods={"GET"})
     */
    #[Route('/ip', name: 'server_ip', methods: ['GET'])]
    public function getIp(Request $request): Response
    {
        $ip = $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? $request->server->get('SERVER_ADDR') ?? 'IP inconnue';
        
        return $this->json([
            'ip' => $ip,
            'host' => $request->getHost(),
            'client_ip' => $request->getClientIp()
        ], 200);
    }
}
