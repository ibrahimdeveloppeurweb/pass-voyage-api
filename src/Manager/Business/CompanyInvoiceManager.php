<?php

namespace App\Manager\Business;

use App\Entity\Business\Company;
use App\Entity\Business\CompanyInvoice;
use App\Exception\ExceptionApi;
use App\Repository\Business\CompanyInvoiceRepository;
use App\Repository\Business\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class CompanyInvoiceManager
{
    private $em;
    private $companyInvoiceRepository;
    private $companyRepository;

    public function __construct(
        EntityManagerInterface $em,
        CompanyInvoiceRepository $companyInvoiceRepository,
        CompanyRepository $companyRepository
    ) {
        $this->em = $em;
        $this->companyInvoiceRepository = $companyInvoiceRepository;
        $this->companyRepository = $companyRepository;
    }

    public function create($data): CompanyInvoice
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

        $montant = (float)($dataObj->montant ?? $dataObj->amount ?? 0);
        if ($montant <= 0) {
            throw new ExceptionApi('Le montant de la facture doit être supérieur à 0 XOF.', ['msg' => 'Montant invalide'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $period = trim($dataObj->periode ?? $dataObj->period ?? date('Y-m'));

        $invoice = new CompanyInvoice();
        $invoice->setCompany($company);
        $invoice->setAmount($montant);
        $invoice->setPeriod($period);
        $invoice->setStatus('En attente');

        // Générer une référence unique s'il n'y en a pas
        if (!empty($dataObj->reference)) {
            $invoice->setReference(trim($dataObj->reference));
        } else {
            $datePrefix = date('Y-m');
            $randomNum = sprintf('%03d', rand(1, 999));
            $invoice->setReference('FAC-' . $datePrefix . '-' . $randomNum);
        }

        $this->em->persist($invoice);
        $this->em->flush();

        return $invoice;
    }

    public function update(string $uuid, $data): CompanyInvoice
    {
        /** @var CompanyInvoice $invoice */
        $invoice = $this->companyInvoiceRepository->findOneBy(['uuid' => $uuid]);
        if (!$invoice && is_numeric($uuid)) {
            $invoice = $this->companyInvoiceRepository->find((int)$uuid);
        }

        if (!$invoice) {
            throw new ExceptionApi('Facture introuvable.', ['msg' => 'Facture introuvable'], Response::HTTP_NOT_FOUND);
        }

        $dataObj = is_array($data) ? (object)$data : $data;

        if (isset($dataObj->montant) || isset($dataObj->amount)) {
            $invoice->setAmount((float)($dataObj->montant ?? $dataObj->amount));
        }

        if (!empty($dataObj->periode) || !empty($dataObj->period)) {
            $invoice->setPeriod(trim($dataObj->periode ?? $dataObj->period));
        }

        if (!empty($dataObj->statut) || !empty($dataObj->status)) {
            $status = trim($dataObj->statut ?? $dataObj->status);
            $invoice->setStatus($status);
            if ($status === 'Payé' && !$invoice->getPaidAt()) {
                $invoice->setPaidAt(new \DateTime());
            }
        }

        $this->em->persist($invoice);
        $this->em->flush();

        return $invoice;
    }

    public function togglePaid(CompanyInvoice $invoice): CompanyInvoice
    {
        if ($invoice->getStatus() === 'Payé') {
            $invoice->setStatus('En attente');
            $invoice->setPaidAt(null);
        } else {
            $invoice->setStatus('Payé');
            $invoice->setPaidAt(new \DateTime());
        }

        $this->em->persist($invoice);
        $this->em->flush();

        return $invoice;
    }

    public function delete(CompanyInvoice $invoice): CompanyInvoice
    {
        $this->em->remove($invoice);
        $this->em->flush();
        return $invoice;
    }

    public function getAllInvoices(): array
    {
        return $this->companyInvoiceRepository->findBy([], ['createdAt' => 'DESC']);
    }

    public function findInvoice(?string $uuid): ?CompanyInvoice
    {
        if (!$uuid) {
            return null;
        }
        $invoice = $this->companyInvoiceRepository->findOneBy(['uuid' => $uuid]);
        if (!$invoice && is_numeric($uuid)) {
            $invoice = $this->companyInvoiceRepository->find((int)$uuid);
        }
        if (!$invoice) {
            $invoice = $this->companyInvoiceRepository->findOneBy(['reference' => $uuid]);
        }
        return $invoice;
    }

    public function togglePaidByUuid(string $uuid): CompanyInvoice
    {
        $invoice = $this->findInvoice($uuid);
        if (!$invoice) {
            throw new ExceptionApi('Facture introuvable.', ['msg' => 'Facture introuvable'], Response::HTTP_NOT_FOUND);
        }
        return $this->togglePaid($invoice);
    }

    public function deleteByUuid(string $uuid): void
    {
        $invoice = $this->findInvoice($uuid);
        if (!$invoice) {
            throw new ExceptionApi('Facture introuvable.', ['msg' => 'Facture introuvable'], Response::HTTP_NOT_FOUND);
        }
        $this->delete($invoice);
    }
}
