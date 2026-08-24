<?php

namespace App\Manager\Business;

use App\Entity\Business\Company;
use App\Entity\Business\CompanyFund;
use App\Entity\Business\CompanyFundHistory;
use App\Entity\Business\Ticket;
use App\Entity\Business\Agent;
use App\Exception\ExceptionApi;
use App\Repository\Business\CompanyFundRepository;
use App\Repository\Business\CompanyFundHistoryRepository;
use App\Repository\Business\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class CompanyFundManager
{
    private $em;
    private $companyFundRepository;
    private $companyFundHistoryRepository;
    private $companyRepository;

    public function __construct(
        EntityManagerInterface $em,
        CompanyFundRepository $companyFundRepository,
        CompanyFundHistoryRepository $companyFundHistoryRepository,
        CompanyRepository $companyRepository
    ) {
        $this->em = $em;
        $this->companyFundRepository = $companyFundRepository;
        $this->companyFundHistoryRepository = $companyFundHistoryRepository;
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

        // Check if a fund already exists for this company
        $existingFund = $this->companyFundRepository->findOneBy(['company' => $company]);
        if ($existingFund) {
            $previousBalance = $existingFund->getRemainingAmount();
            $existingFund->setTotalAmount($existingFund->getTotalAmount() + $montant);
            $newBalance = $existingFund->getRemainingAmount();

            $history = new CompanyFundHistory();
            $history->setCompanyFund($existingFund);
            $history->setCompany($company);
            $history->setType('RECHARGE');
            $history->setAmount($montant);
            $history->setPreviousBalance($previousBalance);
            $history->setNewBalance($newBalance);
            $history->setReference('RECH-' . date('YmdHis'));
            $history->setDescription($dataObj->reason ?? $dataObj->comment ?? 'Allocation / Rechargement de fonds de roulement');
            $history->setPerformedBy($dataObj->performedBy ?? 'Administrateur');

            $this->em->persist($existingFund);
            $this->em->persist($history);
            $this->em->flush();
            return $existingFund;
        }

        $fund = new CompanyFund();
        $fund->setCompany($company);
        $fund->setTotalAmount($montant);
        $fund->setConsumedAmount((float)($dataObj->consumedAmount ?? $dataObj->consomme ?? 0));

        $previousBalance = 0.0;
        $newBalance = $fund->getRemainingAmount();

        $history = new CompanyFundHistory();
        $history->setCompanyFund($fund);
        $history->setCompany($company);
        $history->setType('RECHARGE');
        $history->setAmount($montant);
        $history->setPreviousBalance($previousBalance);
        $history->setNewBalance($newBalance);
        $history->setReference('INIT-' . date('YmdHis'));
        $history->setDescription($dataObj->reason ?? 'Initialisation du fonds de roulement de la compagnie');
        $history->setPerformedBy($dataObj->performedBy ?? 'Administrateur');

        $this->em->persist($fund);
        $this->em->persist($history);
        $this->em->flush();

        return $fund;
    }

    public function rechargeFund(string $uuid, float $amount, ?string $reason = null, ?string $performedBy = null): CompanyFund
    {
        /** @var CompanyFund $fund */
        $fund = $this->companyFundRepository->findOneBy(['uuid' => $uuid]);
        if (!$fund && is_numeric($uuid)) {
            $fund = $this->companyFundRepository->find((int)$uuid);
        }

        if (!$fund) {
            throw new ExceptionApi('Fonds introuvable.', ['msg' => 'Fonds introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($amount <= 0) {
            throw new ExceptionApi('Le montant de rechargement doit être supérieur à 0 XOF.', ['msg' => 'Montant invalide'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $previousBalance = $fund->getRemainingAmount();
        $fund->setTotalAmount($fund->getTotalAmount() + $amount);
        $newBalance = $fund->getRemainingAmount();

        $history = new CompanyFundHistory();
        $history->setCompanyFund($fund);
        $history->setCompany($fund->getCompany());
        $history->setType('RECHARGE');
        $history->setAmount($amount);
        $history->setPreviousBalance($previousBalance);
        $history->setNewBalance($newBalance);
        $history->setReference('RECH-' . date('YmdHis') . '-' . rand(10, 99));
        $history->setDescription($reason ?: 'Rechargement du fonds de roulement');
        $history->setPerformedBy($performedBy ?: 'Administrateur');

        $this->em->persist($fund);
        $this->em->persist($history);
        $this->em->flush();

        return $fund;
    }

    public function deductTicketScan(Ticket $ticket, ?Agent $agent = null): ?CompanyFundHistory
    {
        $company = null;
        if ($agent && $agent->getCompany()) {
            $company = $agent->getCompany();
        }
        if (!$company && method_exists($ticket, 'getCompany') && $ticket->getCompany()) {
            $company = $ticket->getCompany();
        }

        if (!$company) {
            return null;
        }

        $fund = $this->companyFundRepository->findOneBy(['company' => $company]);
        if (!$fund) {
            // Auto create base fund if missing
            $fund = new CompanyFund();
            $fund->setCompany($company);
            $fund->setTotalAmount(5000000.0);
            $fund->setConsumedAmount(0.0);
            $this->em->persist($fund);
        }

        $ticketPrice = (float)($ticket->getUnitPrice() ?? $ticket->getAmount() ?? 0.0);
        if ($ticketPrice <= 0) {
            $ticketPrice = 5000.0; // fallback standard unit price
        }

        $previousBalance = $fund->getRemainingAmount();
        $fund->setConsumedAmount($fund->getConsumedAmount() + $ticketPrice);
        $newBalance = $fund->getRemainingAmount();

        $agentName = $agent ? sprintf('%s %s', $agent->getLastname() ?? '', $agent->getFirstname() ?? '') : 'Agent Terrain';

        $history = new CompanyFundHistory();
        $history->setCompanyFund($fund);
        $history->setCompany($company);
        $history->setType('DEBIT_BILLET');
        $history->setAmount($ticketPrice);
        $history->setPreviousBalance($previousBalance);
        $history->setNewBalance($newBalance);
        $history->setReference('TK-' . ($ticket->getTicketNumber() ?: $ticket->getId()));
        $history->setDescription(sprintf('Débit du billet #%s scanné par %s', $ticket->getTicketNumber() ?: $ticket->getId(), trim($agentName)));
        $history->setPerformedBy(trim($agentName));
        $history->setTicket($ticket);
        $history->setCreatedAt($ticket->getValidatedAt() ?: new \DateTime());

        $this->em->persist($fund);
        $this->em->persist($history);
        $this->em->flush();

        return $history;
    }

    public function getHistory(string $uuid, array $filters = []): array
    {
        /** @var CompanyFund $fund */
        $fund = $this->companyFundRepository->findOneBy(['uuid' => $uuid]);
        if (!$fund && is_numeric($uuid)) {
            $fund = $this->companyFundRepository->find((int)$uuid);
        }

        if (!$fund) {
            throw new ExceptionApi('Fonds introuvable.', ['msg' => 'Fonds introuvable'], Response::HTTP_NOT_FOUND);
        }

        return $this->companyFundHistoryRepository->findByCompanyFund($fund, $filters);
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
        throw new ExceptionApi(
            'La suppression d\'un fonds de roulement est interdite. Les fonds de roulement ne peuvent pas être supprimés.',
            ['msg' => 'Suppression interdite'],
            Response::HTTP_FORBIDDEN
        );
    }
}
