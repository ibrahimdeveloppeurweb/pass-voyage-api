<?php

namespace App\Manager\Business;

use App\Entity\Business\Company;
use App\Entity\Business\CompanyFund;
use App\Exception\ExceptionApi;
use App\Repository\Business\CompanyFundRepository;
use App\Repository\Business\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class CompanyFundManager
{
    private $em;
    private $companyFundRepository;
    private $companyRepository;

    public function __construct(
        EntityManagerInterface $em,
        CompanyFundRepository $companyFundRepository,
        CompanyRepository $companyRepository
    ) {
        $this->em = $em;
        $this->companyFundRepository = $companyFundRepository;
        $this->companyRepository = $companyRepository;
    }

    public function create($data): CompanyFund
    {
        $dataObj = is_array($data) ? (object)$data : $data;

        $companyKey = $dataObj->companyUuid ?? $dataObj->companyId ?? $dataObj->compagnie ?? $dataObj->company ?? null;
        
        $company = null;
        if ($companyKey) {
            $company = $this->companyRepository->findOneBy(['uuid' => $companyKey]);
            if (!$company && is_numeric($companyKey)) {
                $company = $this->companyRepository->find((int)$companyKey);
            }
            if (!$company) {
                $company = $this->companyRepository->findOneBy(['name' => trim($companyKey)]);
            }
        }

        if (!$company) {
            throw new ExceptionApi('Veuillez sélectionner une compagnie valide.', ['msg' => 'Compagnie invalide'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $montant = (float)($dataObj->montant ?? $dataObj->totalAmount ?? $dataObj->amount ?? 0);
        if ($montant <= 0) {
            throw new ExceptionApi('Le montant du fonds doit être supérieur à 0 XOF.', ['msg' => 'Montant invalide'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Vérifier si un fonds existe déjà pour cette compagnie, sinon créer
        $existingFund = $this->companyFundRepository->findOneBy(['company' => $company]);
        if ($existingFund) {
            $existingFund->setTotalAmount($montant);
            if (isset($dataObj->consumedAmount)) {
                $existingFund->setConsumedAmount((float)$dataObj->consumedAmount);
            }
            $this->em->persist($existingFund);
            $this->em->flush();
            return $existingFund;
        }

        $fund = new CompanyFund();
        $fund->setCompany($company);
        $fund->setTotalAmount($montant);
        $fund->setConsumedAmount((float)($dataObj->consumedAmount ?? $dataObj->consomme ?? 0));

        $this->em->persist($fund);
        $this->em->flush();

        return $fund;
    }

    public function update(string $uuid, $data): CompanyFund
    {
        /** @var CompanyFund $fund */
        $fund = $this->companyFundRepository->findOneBy(['uuid' => $uuid]);
        if (!$fund && is_numeric($uuid)) {
            $fund = $this->companyFundRepository->find((int)$uuid);
        }

        if (!$fund) {
            throw new ExceptionApi('Fonds introuvable.', ['msg' => 'Fonds introuvable'], Response::HTTP_NOT_FOUND);
        }

        $dataObj = is_array($data) ? (object)$data : $data;

        if (isset($dataObj->montant) || isset($dataObj->totalAmount)) {
            $fund->setTotalAmount((float)($dataObj->montant ?? $dataObj->totalAmount));
        }

        if (isset($dataObj->consumedAmount) || isset($dataObj->consomme)) {
            $fund->setConsumedAmount((float)($dataObj->consumedAmount ?? $dataObj->consomme));
        }

        $this->em->persist($fund);
        $this->em->flush();

        return $fund;
    }

    public function delete(CompanyFund $fund): CompanyFund
    {
        $this->em->remove($fund);
        $this->em->flush();
        return $fund;
    }
}
