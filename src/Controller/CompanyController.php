<?php

namespace App\Controller;

use App\Entity\Company;
use App\Helpers\JsonHelper;
use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @Route("/api/companies")
 */
class CompanyController extends AbstractController
{
    private $em;
    private $companyRepository;

    public function __construct(EntityManagerInterface $em, CompanyRepository $companyRepository)
    {
        $this->em = $em;
        $this->companyRepository = $companyRepository;
    }

    /**
     * @Route("", name="api_company_list", methods={"GET"})
     */
    public function list(): JsonResponse
    {
        $companies = $this->companyRepository->findAll();
        
        $data = array_map(function(Company $company) {
            return [
                'id' => $company->getId(),
                'uuid' => $company->getUuid(),
                'name' => $company->getName(),
                'contactEmail' => $company->getContactEmail(),
                'contactPhone' => $company->getContactPhone(),
                'address' => $company->getAddress(),
            ];
        }, $companies);

        $response = (new JsonHelper($data, 'Liste des entreprises', 'success', 200, []))->serialize();
        return $this->json($response);
    }
}
