<?php

namespace App\Manager\Business;

use App\Entity\Business\Company;
use App\Exception\ExceptionApi;
use App\Repository\Business\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class CompanyManager
{
    private $em;
    private $companyRepository;

    public function __construct(
        EntityManagerInterface $em,
        CompanyRepository $companyRepository
    ) {
        $this->em = $em;
        $this->companyRepository = $companyRepository;
    }

    public function create($data): Company
    {
        if ($data instanceof Company) {
            if (!$data->getName()) {
                throw new ExceptionApi('Le nom de la compagnie est obligatoire.', ['msg' => 'Nom obligatoire'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $this->em->persist($data);
            $this->em->flush();
            return $data;
        }

        $dataObj = is_array($data) ? (object)$data : $data;
        $name = '';

        if (is_object($data) && method_exists($data, 'getName')) {
            $name = $data->getName() ?? '';
        }

        if (!$name) {
            $name = trim($dataObj->name ?? $dataObj->nom ?? '');
        }

        if (!$name) {
            throw new ExceptionApi('Le nom de la compagnie est obligatoire.', ['msg' => 'Nom obligatoire'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $company = new Company();
        $company->setName($name);
        $company->setContactEmail(trim($dataObj->contactEmail ?? $dataObj->contact ?? $dataObj->email ?? ''));
        $company->setContactPhone(trim($dataObj->contactPhone ?? $dataObj->telephone ?? $dataObj->phone ?? ''));
        $company->setAddress(trim($dataObj->address ?? $dataObj->adresse ?? ''));
        $company->setStatus(trim($dataObj->status ?? $dataObj->statut ?? 'Partenaire Actif'));
        $company->setIsActive($dataObj->isActive ?? true);

        if (isset($dataObj->logo)) {
            $company->setLogo($dataObj->logo);
        }

        $this->em->persist($company);
        $this->em->flush();

        return $company;
    }

    public function update(string $uuid, $data): Company
    {
        /** @var Company $company */
        $company = $this->companyRepository->findOneBy(['uuid' => $uuid]);
        if (!$company && is_numeric($uuid)) {
            $company = $this->companyRepository->find((int)$uuid);
        }
        
        $dataObj = is_array($data) ? (object)$data : $data;

        $name = '';
        if (is_object($data) && method_exists($data, 'getName')) {
            $name = $data->getName() ?? '';
        }
        if (!$name) {
            $name = trim($dataObj->name ?? $dataObj->nom ?? '');
        }

        if (!$company && $name) {
            $company = $this->companyRepository->findOneBy(['name' => $name]);
        }

        if (!$company) {
            throw new ExceptionApi('Compagnie introuvable.', ['msg' => 'Compagnie introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($name) {
            $company->setName($name);
        }

        $email = $dataObj->contactEmail ?? $dataObj->contact ?? $dataObj->email ?? null;
        if ($email !== null) {
            $company->setContactEmail(trim($email));
        }

        $phone = $dataObj->contactPhone ?? $dataObj->telephone ?? $dataObj->phone ?? null;
        if ($phone !== null) {
            $company->setContactPhone(trim($phone));
        }

        $address = $dataObj->address ?? $dataObj->adresse ?? null;
        if ($address !== null) {
            $company->setAddress(trim($address));
        }

        $status = $dataObj->status ?? $dataObj->statut ?? null;
        if ($status !== null) {
            $company->setStatus(trim($status));
        }

        if (isset($dataObj->isActive)) {
            $company->setIsActive((bool)$dataObj->isActive);
        }

        if (isset($dataObj->logo)) {
            $company->setLogo($dataObj->logo);
        }

        $this->em->persist($company);
        $this->em->flush();

        return $company;
    }

    public function delete(Company $company): Company
    {
        // 1. Nettoyer les factures associées
        $invoiceRepo = $this->em->getRepository(\App\Entity\Business\CompanyInvoice::class);
        foreach ($invoiceRepo->findBy(['company' => $company]) as $inv) {
            $this->em->remove($inv);
        }

        // 2. Nettoyer les fonds associés
        $fundRepo = $this->em->getRepository(\App\Entity\Business\CompanyFund::class);
        foreach ($fundRepo->findBy(['company' => $company]) as $fund) {
            $this->em->remove($fund);
        }

        // 3. Nettoyer les tarifs associés
        $tariffRepo = $this->em->getRepository(\App\Entity\Business\Tariff::class);
        foreach ($tariffRepo->findBy(['company' => $company]) as $tariff) {
            $this->em->remove($tariff);
        }

        // 4. Dissocier les stations
        $stationRepo = $this->em->getRepository(\App\Entity\Business\Station::class);
        foreach ($stationRepo->findBy(['company' => $company]) as $station) {
            $station->setCompany(null);
        }

        // 5. Dissocier les agents
        $agentRepo = $this->em->getRepository(\App\Entity\Business\Agent::class);
        foreach ($agentRepo->findBy(['company' => $company]) as $agent) {
            $agent->setCompany(null);
        }

        // 6. Dissocier les billets
        $ticketRepo = $this->em->getRepository(\App\Entity\Business\Ticket::class);
        foreach ($ticketRepo->findBy(['company' => $company]) as $ticket) {
            $ticket->setCompany(null);
        }

        // 7. Dissocier les demandes de crédit
        $creditRepo = $this->em->getRepository(\App\Entity\Business\Credit::class);
        foreach ($creditRepo->findBy(['company' => $company]) as $credit) {
            $credit->setCompany(null);
        }

        $this->em->remove($company);
        $this->em->flush();
        return $company;
    }

    public function toggle(Company $company): Company
    {
        $company->setIsActive(!$company->getIsActive());
        if (!$company->getIsActive()) {
            $company->setStatus('Inactif');
        } else {
            $company->setStatus('Partenaire Actif');
        }
        $this->em->persist($company);
        $this->em->flush();
        return $company;
    }

    public function getAllCompanies(): array
    {
        return $this->companyRepository->findBy([], ['createdAt' => 'DESC']);
    }

    public function findCompany(?string $uuid): ?Company
    {
        if (!$uuid) {
            return null;
        }
        $company = $this->companyRepository->findOneBy(['uuid' => $uuid]);
        if (!$company && is_numeric($uuid)) {
            $company = $this->companyRepository->find((int)$uuid);
        }
        return $company;
    }

    public function deleteByUuid(string $uuid): Company
    {
        $company = $this->findCompany($uuid);
        if (!$company) {
            throw new ExceptionApi('Compagnie introuvable.', ['msg' => 'Compagnie introuvable'], Response::HTTP_NOT_FOUND);
        }
        return $this->delete($company);
    }

    public function toggleByUuid(string $uuid): Company
    {
        $company = $this->findCompany($uuid);
        if (!$company) {
            throw new ExceptionApi('Compagnie introuvable.', ['msg' => 'Compagnie introuvable'], Response::HTTP_NOT_FOUND);
        }
        return $this->toggle($company);
    }
}
