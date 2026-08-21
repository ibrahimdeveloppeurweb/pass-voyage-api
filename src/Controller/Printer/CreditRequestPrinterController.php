<?php

namespace App\Controller\Printer;

use App\Repository\CreditRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/printer/credit-request")
 */
class CreditRequestPrinterController extends AbstractController
{
    private $creditRequestRepository;

    public function __construct(CreditRequestRepository $creditRequestRepository)
    {
        $this->creditRequestRepository = $creditRequestRepository;
    }

    /**
     * @Route("/{uuid}/pdf", name="print_credit_request_pdf", methods={"GET"})
     */
    public function printPdf(string $uuid): Response
    {
        $request = $this->creditRequestRepository->findOneBy(['uuid' => $uuid]);

        if (!$request) {
            return new Response('Demande de crédit introuvable.', 404);
        }

        // Logic for PDF Generation goes here.
        return new Response('Génération PDF pour la demande : ' . $request->getMotif());
    }
}
