<?php

namespace App\Manager\Business;

use App\Entity\Business\Credit;
use App\Entity\Business\Ticket;
use App\Entity\Business\Payment;
use App\Entity\Business\Agent;
use App\Repository\Business\CreditRepository;
use App\Repository\Business\PassengerRepository;
use App\Repository\CompanyRepository;
use App\Service\QrCodeService;
use Doctrine\ORM\EntityManagerInterface;

use App\Service\NotificationService;

class CreditManager
{
    private $em;
    private $creditRepository;
    private $passengerRepository;
    private $companyRepository;
    private $qrCodeService;
    private $notificationService;
    private $creditPolicyManager;

    public function __construct(
        EntityManagerInterface $em,
        CreditRepository $creditRepository,
        PassengerRepository $passengerRepository,
        CompanyRepository $companyRepository,
        QrCodeService $qrCodeService,
        NotificationService $notificationService,
        \App\Manager\Business\CreditPolicyManager $creditPolicyManager
    ) {
        $this->em = $em;
        $this->creditRepository = $creditRepository;
        $this->passengerRepository = $passengerRepository;
        $this->companyRepository = $companyRepository;
        $this->qrCodeService = $qrCodeService;
        $this->notificationService = $notificationService;
        $this->creditPolicyManager = $creditPolicyManager;
    }

    public function create(object $data): Credit
    {
        $credit = new Credit();
        return $this->save($credit, $data, true);
    }

    public function findCredit(string $identifier): ?Credit
    {
        $credit = $this->creditRepository->findOneBy(['uuid' => $identifier]);
        if (!$credit && is_numeric($identifier)) {
            $credit = $this->creditRepository->find((int) $identifier);
            if ($credit && !$credit->getUuid()) {
                $credit->setUuid(\Ramsey\Uuid\Uuid::uuid4()->toString());
                $this->em->flush();
            }
        }
        return $credit;
    }

    public function update(string $uuid, object $data): Credit
    {
        $credit = $this->findCredit($uuid);
        if (!$credit) {
            throw new \Exception("Demande de crédit non trouvée");
        }

        if (in_array(strtoupper($credit->getStatus()), ['APPROVED', 'VALIDE', 'REFUSE', 'REJECTED'])) {
            throw new \Exception("Impossible de modifier une demande déjà traitée");
        }

        return $this->save($credit, $data, false);
    }

    private function save(Credit $credit, object $data, bool $isNew = false): Credit
    {
        if (isset($data->passenger) && $data->passenger instanceof \App\Entity\Business\Passenger) {
            $credit->setPassenger($data->passenger);
        } elseif (isset($data->passengerUuid)) {
            $passenger = $this->passengerRepository->findOneBy(['uuid' => $data->passengerUuid]);
            if ($passenger) {
                $credit->setPassenger($passenger);
            }
        }

        $compNameRaw = $data->departureCompany ?? $data->companyName ?? $data->compagnie ?? $data->company_name ?? null;

        if (isset($data->company) && $data->company instanceof \App\Entity\Business\Company) {
            $credit->setCompany($data->company);
        } elseif (isset($data->companyUuid)) {
            $company = $this->companyRepository->findOneBy(['uuid' => $data->companyUuid]);
            if ($company) {
                $credit->setCompany($company);
            }
        }

        if (!empty($compNameRaw)) {
            $compNameStr = trim((string) $compNameRaw);
            $company = $this->companyRepository->findOneBy(['name' => $compNameStr]);
            if (!$company) {
                $allCompanies = $this->companyRepository->findAll();
                foreach ($allCompanies as $c) {
                    if (strcasecmp($c->getName(), $compNameStr) === 0 || str_contains(strtolower($c->getName()), strtolower($compNameStr)) || str_contains(strtolower($compNameStr), strtolower($c->getName()))) {
                        $company = $c;
                        break;
                    }
                }
            }
            if (!$company) {
                $company = new \App\Entity\Business\Company();
                $company->setName($compNameStr);
                $company->setUuid(\Ramsey\Uuid\Uuid::uuid4()->toString());
                $company->setIsActive(true);
                $company->setCreatedAt(new \DateTime());
                $this->em->persist($company);
                $this->em->flush();
            }
            if ($company) {
                $credit->setCompany($company);
            }
        }

        if (!$credit->getCompany() && empty($compNameRaw)) {
            $firstCompany = $this->companyRepository->findOneBy([]);
            if ($firstCompany) {
                $credit->setCompany($firstCompany);
            }
        }

        if (isset($data->departureCity)) {
            $credit->setDepartureCity($data->departureCity);
        }
        if (isset($data->arrivalCity)) {
            $credit->setArrivalCity($data->arrivalCity);
        }

        if (isset($data->travelDate)) {
            $credit->setTravelDate(new \DateTime($data->travelDate));
        }
        if (isset($data->returnDate) && $data->returnDate) {
            $credit->setReturnDate(new \DateTime($data->returnDate));
        }

        if (isset($data->isRoundTrip)) {
            $credit->setIsRoundTrip((bool) $data->isRoundTrip);
        } elseif (isset($data->isRound)) {
            $credit->setIsRoundTrip((bool) $data->isRound);
        } elseif (isset($data->typeVoyage)) {
            $credit->setIsRoundTrip(strtoupper((string) $data->typeVoyage) === 'ALLER_RETOUR' || strtoupper((string) $data->typeVoyage) === 'ROUND_TRIP');
        } elseif (isset($data->returnDate) && $data->returnDate) {
            $credit->setIsRoundTrip(true);
        }

        if (isset($data->numberOfTickets)) {
            $credit->setPassengerCount((int) $data->numberOfTickets);
        }
        if (isset($data->unitPrice)) {
            $credit->setUnitPrice((int) $data->unitPrice);
        }

        $numberOfTickets = $credit->getPassengerCount() ?: 1;
        $unitPrice = $credit->getUnitPrice() ?: 0;

        $amountRequested = (isset($data->amountRequested) && (int) $data->amountRequested > 0)
            ? (int) $data->amountRequested
            : ($numberOfTickets * $unitPrice);

        $serviceFee = (isset($data->serviceFee) && (int) $data->serviceFee >= 0)
            ? (int) $data->serviceFee
            : (600 * $numberOfTickets);

        $totalAmount = (isset($data->totalAmount) && (int) $data->totalAmount > 0)
            ? (int) $data->totalAmount
            : $amountRequested;

        $credit->setAmountRequested($amountRequested);
        $credit->setServiceFee($serviceFee);
        $credit->setTotalAmount($totalAmount);

        if ($isNew) {
            $credit->setStatus('PENDING_VALIDATION');
            $credit->setRepaymentStatus('UNPAID');
        }

        if (!$credit->getUuid()) {
            $credit->setUuid(\Ramsey\Uuid\Uuid::uuid4()->toString());
        }
        if (!$credit->getCode()) {
            $codePrefix = 'PV-' . sprintf('%04d', rand(1000, 9999));
            $credit->setCode($codePrefix . '-' . sprintf('%02d', rand(10, 99)) . sprintf('%02d', rand(10, 99)) . '-01');
        }

        if (!$credit->getCreatedAt()) {
            $credit->setCreatedAt(new \DateTime());
        }
        $credit->setUpdatedAt(new \DateTime());

        $this->em->persist($credit);
        $this->em->flush();

        return $credit;
    }

    public function approve(Credit $credit): Credit
    {
        if ($credit->getStatus() === 'APPROVED') {
            return $credit;
        }

        $credit->setStatus('APPROVED');

        $passenger = $credit->getPassenger();

        // Calculer et sauvegarder la date limite de remboursement DÈS l'approbation
        $startDate = clone ($credit->getCreatedAt() ?? clone $credit->getTravelDate() ?? new \DateTime());
        $policy = $this->creditPolicyManager->getOrCreatePolicy();
        $delaiAccorde = $policy->getNewUserDelay();
        if ($passenger) {
            $profileType = $passenger->getProfileType();
            if ($profileType === 'VIP') {
                $delaiAccorde = $policy->getVipDelay();
            } elseif ($profileType === 'STANDARD') {
                $delaiAccorde = $policy->getStandardDelay();
            }
        }
        $dueDate = clone $startDate;
        $dueDate->modify("+{$delaiAccorde} days");
        $credit->setRepaymentDueDate($dueDate);
        
        if ($passenger) {
            // Lors de l'approbation, recalculer la dette globale uniquement sur les demandes de crédit VALIDÉES
            $totalDebt = 0;
            $allUserCredits = $this->creditRepository->findBy(['passenger' => $passenger]);
            foreach ($allUserCredits as $cr) {
                $st = strtoupper(trim((string) $cr->getStatus()));
                if (in_array($st, ['APPROVED', 'VALIDE']) || $cr->getId() === $credit->getId()) {
                    $toRepay = method_exists($cr, 'getAmountToRepay') ? $cr->getAmountToRepay() : ($cr->getAmountRequested() ?: $cr->getTotalAmount());
                    $totalDebt += max(0, $toRepay - (int) $cr->getRepaidAmount());
                }
            }
            $passenger->setTotalDebt($totalDebt);
            $passenger->setAvailableCredit($totalDebt);

            // Deduct the service fee from the wallet now that the request is approved
            $serviceFeeUsed = (int) $credit->getServiceFee();
            $currentWallet = (int) $passenger->getServiceFeeWallet();
            $newWallet = max(0, $currentWallet - $serviceFeeUsed);
            $passenger->setServiceFeeWallet($newWallet);

            $this->em->persist($passenger);
        }

        // Générer les billets et leurs QR codes via QrCodeService
        $this->qrCodeService->generateBatchTickets($credit);

        $this->em->persist($credit);
        $this->em->flush();

        // Envoi de la notification Push au passager + sauvegarde dans l'historique des notifications
        if ($passenger && $this->notificationService) {
            $trajet = sprintf("%s - %s", $credit->getDepartureCity() ?? 'Abidjan', $credit->getArrivalCity() ?? 'Yamoussoukro');
            $nbBillets = $credit->getPassengerCount() ?? 1;
            $title = "Crédit Voyage Approuvé ! ";
            $message = sprintf(
                "Votre demande de crédit pour %d billet(s) (%s) d'un montant de %s FCFA a été approuvée avec succès. Vos billets et QR codes sont disponibles dans 'Mes Pass'.",
                $nbBillets,
                $trajet,
                number_format((float) $credit->getAmountToRepay(), 0, ',', ' ')
            );
            try {
                $this->notificationService->createNotificationForPassenger($passenger, $title, $message, 'CREDIT_APPROVED');
            } catch (\Throwable $e) {
                // Log ou ignorer l'erreur d'envoi de notification
            }
        }

        return $credit;
    }

    public function reject(Credit $credit, ?string $reason = null): Credit
    {
        $credit->setStatus('REJECTED');
        if ($reason && method_exists($credit, 'setRejectionReason')) {
            $credit->setRejectionReason($reason);
        }

        // Le wallet n'est PAS touché en cas de refus (il conserve le solde initial de la garantie)

        $this->em->persist($credit);
        $this->em->flush();

        // Notification push + historique pour le passager lors du rejet
        $passenger = $credit->getPassenger();
        if ($passenger && $this->notificationService) {
            $trajet = sprintf("%s - %s", $credit->getDepartureCity() ?? 'Abidjan', $credit->getArrivalCity() ?? 'Yamoussoukro');
            $title = "Demande de Crédit Refusée ";
            $motifText = !empty($reason) ? sprintf(" Motif : %s.", $reason) : "";
            $message = sprintf(
                "Votre demande de crédit voyage pour le trajet %s a été refusée.%s",
                $trajet,
                $motifText
            );
            try {
                $this->notificationService->createNotificationForPassenger($passenger, $title, $message, 'CREDIT_REJECTED');
            } catch (\Throwable $e) {
                // Log ou ignorer l'erreur d'envoi
            }
        }

        return $credit;
    }

    public function scanTicket(string $qrCodeContent, ?Agent $agent = null): Ticket
    {
        $ticketRepository = $this->em->getRepository(Ticket::class);
        $ticket = $ticketRepository->findOneBy(['qrCodeContent' => $qrCodeContent]);

        if (!$ticket) {
            throw new \Exception("Billet introuvable avec ce code QR");
        }

        if ($ticket->getIsUsed()) {
            throw new \Exception("Ce billet a déjà été utilisé le " . $ticket->getUsedAt()->format('d/m/Y H:i'));
        }

        $ticket->setIsUsed(true);
        $ticket->setStatus('SCANNED');
        $ticket->setUsedAt(new \DateTime());

        $this->em->persist($ticket);
        $this->em->flush();

        return $ticket;
    }

    public function reimburse(Credit $credit, int $amount, string $paymentMethod = 'MOBILE_MONEY'): Payment
    {
        $passenger = $credit->getPassenger();
        if (!$passenger) {
            throw new \Exception("Passager introuvable pour ce remboursement");
        }

        $payment = new Payment();
        $payment->setCreditRequest($credit);
        $payment->setPassenger($passenger);
        $payment->setAmount($amount);
        $payment->setPaymentMethod($paymentMethod);
        $payment->setTransactionId('TX-REIMB-' . date('YmdHis') . '-' . rand(1000, 9999));
        $payment->setPaymentDate(new \DateTime());
        $payment->setStatus('SUCCESS');

        $this->em->persist($payment);

        // Mettre à jour les dettes et le crédit du passager
        $newTotalReimbursed = $passenger->getTotalReimbursed() + $amount;
        $newTotalDebt = max(0, $passenger->getTotalDebt() - $amount);
        $newAvailableCredit = min($passenger->getMaxCreditLimit(), $passenger->getAvailableCredit() + $amount);

        $passenger->setTotalReimbursed($newTotalReimbursed);
        $passenger->setTotalDebt($newTotalDebt);
        $passenger->setAvailableCredit($newAvailableCredit);

        $this->em->persist($passenger);

        // Mettre à jour le montant remboursé sur ce crédit précisément
        $creditAmountToRepay = method_exists($credit, 'getAmountToRepay') ? $credit->getAmountToRepay() : ($credit->getAmountRequested() ?: $credit->getTotalAmount());
        $newCreditRepaid = $credit->getRepaidAmount() + $amount;
        $credit->setRepaidAmount($newCreditRepaid);

        if ($newCreditRepaid >= $creditAmountToRepay) {
            // CE CRÉDIT SPÉCIFIQUE EST ENTIÈREMENT REMBOURSÉ
            $credit->setRepaymentStatus('FULLY_REIMBURSED');

            // Logique du Bon Payeur : Ce crédit est soldé, est-ce dans les délais ?
            $now = new \DateTime();

            $policy = $this->creditPolicyManager->getOrCreatePolicy();

            // S'il y a une date d'échéance enregistrée, on l'utilise
            if (method_exists($credit, 'getRepaymentDueDate') && $credit->getRepaymentDueDate()) {
                $dueDate = clone $credit->getRepaymentDueDate();
            } else {
                $startDate = clone ($credit->getCreatedAt() ?? clone $credit->getTravelDate() ?? new \DateTime());
                // On utilise le délai qui était applicable au moment du GRADE ACTUEL
                $delaiAccorde = $policy->getNewUserDelay();
                if ($passenger->getProfileType() === 'VIP') {
                    $delaiAccorde = $policy->getVipDelay();
                } elseif ($passenger->getProfileType() === 'STANDARD') {
                    $delaiAccorde = $policy->getStandardDelay();
                }

                $dueDate = clone $startDate;
                if ($dueDate instanceof \DateTime) {
                    $dueDate->modify("+{$delaiAccorde} days");
                }
            }

            if ($now <= $dueDate) {
                // REMBOURSÉ À L'HEURE : +1 point
                $c = $passenger->getConsecutiveGoodRepayments() ?? 0;
                $passenger->setConsecutiveGoodRepayments($c + 1);

                // Promotion selon le score cumulé
                $score = $passenger->getConsecutiveGoodRepayments();
                if ($score >= 10) {
                    $passenger->setProfileType('VIP');
                    $passenger->setMaxCreditLimit($policy->getVipLimit());
                } elseif ($score >= 3) {
                    $passenger->setProfileType('STANDARD');
                    $passenger->setMaxCreditLimit($policy->getStandardLimit());
                } else {
                    $passenger->setProfileType('NEW_USER');
                    $passenger->setMaxCreditLimit($policy->getNewUserLimit());
                }
            } else {
                // EN RETARD : rétrogradation progressive (Option A)
                $passenger->setConsecutiveGoodRepayments(0);
                $currentProfile = $passenger->getProfileType();
                if ($currentProfile === 'VIP') {
                    // VIP → STANDARD (un cran en dessous)
                    $passenger->setProfileType('STANDARD');
                    $passenger->setMaxCreditLimit($policy->getStandardLimit());
                } else {
                    // STANDARD ou NEW_USER → NEW_USER
                    $passenger->setProfileType('NEW_USER');
                    $passenger->setMaxCreditLimit($policy->getNewUserLimit());
                }
            }
            $this->em->persist($passenger);

        } else {
            $credit->setRepaymentStatus('PARTIALLY_REIMBURSED');
        }

        $this->em->persist($credit);
        $this->em->flush();

        return $payment;
    }

    public function delete(Credit $credit): Credit
    {
        if (in_array(strtoupper($credit->getStatus()), ['APPROVED', 'VALIDE'])) {
            throw new \Exception("Impossible de supprimer une demande de crédit validée");
        }

        $credit->setDeletedAt(new \DateTime());
        $this->em->flush();

        return $credit;
    }

    public function resolvePassenger(?object $user = null, ?string $phone = null, ?string $passengerUuid = null): ?\App\Entity\Business\Passenger
    {
        if ($user && method_exists($user, 'getPassenger') && $user->getPassenger()) {
            return $user->getPassenger();
        }

        if ($phone) {
            $phoneClean = preg_replace('/[^0-9]/', '', $phone);
            $targetDigits = strlen($phoneClean) >= 9 ? substr($phoneClean, -9) : $phoneClean;

            $allPassengers = $this->passengerRepository->findAll();
            foreach ($allPassengers as $p) {
                $pClean = preg_replace('/[^0-9]/', '', $p->getPhoneNumber() ?? '');
                $pDigits = strlen($pClean) >= 9 ? substr($pClean, -9) : $pClean;
                if ($pDigits && $pDigits === $targetDigits) {
                    return $p;
                }
            }
        }

        if ($passengerUuid) {
            return $this->passengerRepository->findOneBy(['uuid' => $passengerUuid]);
        }

        return null;
    }

    public function getPendingCreditRequestData(?object $user, ?string $phone): array
    {
        $passenger = $this->resolvePassenger($user, $phone);
        if (!$passenger) {
            return ['hasPendingRequest' => false, 'passengerValid' => false];
        }

        $statusUpper = strtoupper((string) ($passenger->getIdentityStatus() ?? ''));
        $isIdentified = in_array($statusUpper, ['VERIFIED', 'VALIDATED', 'APPROVED']) || ($passenger->getIsIdentified() === true);
        $isBlocked = ($passenger->getIsBlacklisted() === true);
        $passengerValid = $isIdentified && !$isBlocked;

        $allCredits = $this->creditRepository->findBy(['passenger' => $passenger], ['createdAt' => 'DESC']);
        $pendingRequest = null;
        foreach ($allCredits as $c) {
            $st = strtoupper((string) $c->getStatus());
            if (in_array($st, ['PENDING_VALIDATION', 'PENDING', 'EN_ATTENTE', 'IN_PROGRESS', 'SUBMITTED']) || (!in_array($st, ['APPROVED', 'VALIDE', 'REFUSE', 'REJECTED', 'CANCELLED', 'PAID', 'REPAID', 'EXPIRED']))) {
                $pendingRequest = $c;
                break;
            }
        }

        if (!$pendingRequest) {
            return [
                'hasPendingRequest' => false,
                'passengerValid' => $passengerValid,
                'isBlocked' => $isBlocked,
                'isBlacklisted' => $isBlocked,
                'identityStatus' => $passenger->getIdentityStatus(),
            ];
        }

        return [
            'hasPendingRequest' => true,
            'passengerValid' => $passengerValid,
            'isBlocked' => $isBlocked,
            'isBlacklisted' => $isBlocked,
            'identityStatus' => $passenger->getIdentityStatus(),
            'pendingRequest' => [
                'id' => $pendingRequest->getId(),
                'uuid' => $pendingRequest->getUuid(),
                'departureCity' => $pendingRequest->getDepartureCity(),
                'arrivalCity' => $pendingRequest->getArrivalCity(),
                'travelDate' => $pendingRequest->getTravelDate() ? $pendingRequest->getTravelDate()->format('d/m/Y') : null,
                'returnDate' => $pendingRequest->getReturnDate() ? $pendingRequest->getReturnDate()->format('d/m/Y') : null,
                'isRoundTrip' => $pendingRequest->getIsRoundTrip() ?? false,
                'typeVoyage' => ($pendingRequest->getIsRoundTrip() ? 'ALLER_RETOUR' : 'ALLER_SIMPLE'),
                'numberOfTickets' => $pendingRequest->getPassengerCount(),
                'unitPrice' => $pendingRequest->getUnitPrice(),
                'amountRequested' => $pendingRequest->getAmountRequested(),
                'serviceFee' => $pendingRequest->getServiceFee(),
                'totalAmount' => $pendingRequest->getTotalAmount(),
                'companyName' => $pendingRequest->getCompany() ? $pendingRequest->getCompany()->getName() : ($pendingRequest->getDepartureCompany() ?? 'Partenaire'),
                'status' => $pendingRequest->getStatus(),
                'createdAt' => $pendingRequest->getCreatedAt() ? $pendingRequest->getCreatedAt()->format('d M Y à H:i') : null,
            ]
        ];
    }

    public function submitPassengerCreditRequest(object $data, ?object $user): array
    {
        $phone = $data->phone ?? $data->phoneNumber ?? null;
        $passengerUuid = $data->passengerUuid ?? null;

        $passenger = $this->resolvePassenger($user, $phone, $passengerUuid);
        if (!$passenger) {
            return [
                'success' => false,
                'code' => 404,
                'payload' => ['message' => 'Passager introuvable']
            ];
        }

        if ($passenger->getIsBlacklisted() === true) {
            return [
                'success' => false,
                'code' => 403,
                'payload' => [
                    'status' => 'error',
                    'code' => 'ACCOUNT_BLOCKED',
                    'message' => 'Votre compte est actuellement suspendu ou bloqué. Vous ne pouvez pas effectuer de demande de crédit voyage.'
                ]
            ];
        }

        $statusUpper = strtoupper((string) ($passenger->getIdentityStatus() ?? ''));
        $isIdentified = in_array($statusUpper, ['VERIFIED', 'VALIDATED', 'APPROVED']) || ($passenger->getIsIdentified() === true);
        if (!$isIdentified) {
            if ($passenger->getIdentityStatus() === 'PENDING') {
                return [
                    'success' => false,
                    'code' => 400,
                    'payload' => [
                        'status' => 'error',
                        'code' => 'KYC_PENDING',
                        'message' => "Vos pièces d'identité ont déjà été soumises et sont en cours d'examen par l'administrateur. Vous pourrez faire une demande dès la validation de votre dossier."
                    ]
                ];
            }

            return [
                'success' => false,
                'code' => 400,
                'payload' => [
                    'status' => 'error',
                    'code' => 'KYC_NOT_VERIFIED',
                    'message' => "Votre compte n'est pas encore validé. Veuillez soumettre vos pièces d'identité pour bénéficier d'un crédit voyage."
                ]
            ];
        }

        $allUserCredits = $this->creditRepository->findBy(['passenger' => $passenger]);
        foreach ($allUserCredits as $cr) {
            $st = strtoupper((string) $cr->getStatus());
            if (in_array($st, ['PENDING_VALIDATION', 'PENDING', 'EN_ATTENTE', 'IN_PROGRESS', 'SUBMITTED']) || (!in_array($st, ['APPROVED', 'VALIDE', 'REFUSE', 'REJECTED', 'CANCELLED', 'PAID', 'REPAID', 'EXPIRED']))) {
                return [
                    'success' => false,
                    'code' => 400,
                    'payload' => [
                        'status' => 'error',
                        'code' => 'PENDING_REQUEST_EXISTS',
                        'message' => "Vous avez déjà une demande de crédit voyage en attente de validation par l'administrateur."
                    ]
                ];
            }
        }

        $data->passenger = $passenger;
        $data->passengerUuid = $passenger->getUuid();

        $reqCompName = $data->departureCompany ?? $data->companyName ?? $data->compagnie ?? null;
        if (!empty($reqCompName)) {
            $compNameStr = trim((string) $reqCompName);
            $comp = $this->companyRepository->findOneBy(['name' => $compNameStr]);
            if (!$comp) {
                $comp = $this->companyRepository->findOneBy(['uuid' => $compNameStr]);
            }
            if (!$comp) {
                $allCompanies = $this->companyRepository->findAll();
                foreach ($allCompanies as $c) {
                    if (strcasecmp($c->getName(), $compNameStr) === 0 || str_contains(strtolower($c->getName()), strtolower($compNameStr)) || str_contains(strtolower($compNameStr), strtolower($c->getName()))) {
                        $comp = $c;
                        break;
                    }
                }
            }
            if (!$comp) {
                $comp = new \App\Entity\Business\Company();
                $comp->setName($compNameStr);
                $comp->setUuid(\Ramsey\Uuid\Uuid::uuid4()->toString());
                $comp->setIsActive(true);
                $comp->setCreatedAt(new \DateTime());
                $this->em->persist($comp);
                $this->em->flush();
            }
            if ($comp) {
                $data->companyUuid = $comp->getUuid();
                $data->company = $comp;
            }
        }

        try {
            $creditRequest = $this->create($data);

            // Enregistrer l'opération de paiement des frais de service dans la table payment (Ajusté avec le Wallet)
            $feeAmount = (int) $creditRequest->getServiceFee();
            if ($feeAmount <= 0) {
                $feeAmount = (isset($data->serviceFee) && (int) $data->serviceFee > 0) ? (int) $data->serviceFee : (600 * ($creditRequest->getPassengerCount() ?: 1));
            }

            if ($feeAmount > 0) {
                $walletAmount = (int) $passenger->getServiceFeeWallet();
                $feeToPay = max(0, $feeAmount - $walletAmount);

                // TOUT PAIEMENT EN CASH RECHARGE IMMÉDIATEMENT LE WALLET
                if ($feeToPay > 0) {
                    $walletAmount += $feeToPay;
                    $passenger->setServiceFeeWallet($walletAmount);
                    $this->em->persist($passenger);
                    $this->em->flush();

                    $paymentMethod = $data->paymentMethod ?? $data->payment_method ?? $data->method ?? 'Wave';

                    $feePayment = new Payment();
                    $feePayment->setPassenger($passenger);
                    $feePayment->setCreditRequest($creditRequest);
                    $feePayment->setAmount($feeToPay);
                    $feePayment->setPaymentMethod($paymentMethod);
                    $feePayment->setTransactionId('TX-FEE-' . (new \DateTime())->format('YmdHis') . '-' . rand(1000, 9999));
                    $feePayment->setPaymentDate(new \DateTime());
                    $feePayment->setStatus('SUCCESS');

                    $this->em->persist($feePayment);
                    $this->em->flush();
                }
            }

            if ($this->notificationService) {
                try {
                    if ($feeAmount > 0) {
                        $this->notificationService->createNotificationForPassenger(
                            $passenger,
                            'Frais de service réglés',
                            "Votre paiement de " . number_format($feeAmount, 0, ',', '.') . " FCFA a bien été pris en compte. (50% pour le service de réservation et 50% pour le service de crédit).",
                            'FEE_PAID'
                        );
                    }

                    $this->notificationService->createNotificationForPassenger(
                        $passenger,
                        'Demande de crédit soumise',
                        "Votre demande de crédit de " . number_format($creditRequest->getAmountToRepay(), 0, ',', '.') . "F pour " . $creditRequest->getDepartureCity() . " ➔ " . $creditRequest->getArrivalCity() . " a bien été enregistrée et est en cours d'examen.",
                        'CREDIT_SUBMITTED'
                    );
                } catch (\Throwable $e) {
                }
            }

            return [
                'success' => true,
                'code' => 201,
                'payload' => [
                    'status' => 'success',
                    'message' => 'Demande de crédit soumise avec succès. En attente de validation.',
                    'creditRequest' => [
                        'id' => $creditRequest->getId(),
                        'uuid' => $creditRequest->getUuid(),
                        'totalAmount' => $creditRequest->getTotalAmount(),
                        'amountRequested' => $creditRequest->getAmountRequested(),
                        'serviceFee' => $creditRequest->getServiceFee(),
                        'isRoundTrip' => $creditRequest->getIsRoundTrip() ?? false,
                        'typeVoyage' => ($creditRequest->getIsRoundTrip() ? 'ALLER_RETOUR' : 'ALLER_SIMPLE'),
                        'departureCity' => $creditRequest->getDepartureCity(),
                        'arrivalCity' => $creditRequest->getArrivalCity(),
                        'companyName' => $creditRequest->getCompany() ? $creditRequest->getCompany()->getName() : ($creditRequest->getDepartureCompany() ?? 'Partenaire'),
                        'travelDate' => $creditRequest->getTravelDate() ? $creditRequest->getTravelDate()->format('d/m/Y') : null,
                        'returnDate' => $creditRequest->getReturnDate() ? $creditRequest->getReturnDate()->format('d/m/Y') : null,
                        'numberOfTickets' => $creditRequest->getPassengerCount(),
                        'status' => $creditRequest->getStatus(),
                        'createdAt' => $creditRequest->getCreatedAt() ? $creditRequest->getCreatedAt()->format('d M Y à H:i') : null,
                    ]
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'code' => 400,
                'payload' => ['message' => 'Erreur lors de la soumission de la demande : ' . $e->getMessage()]
            ];
        }
    }

    public function getPassengerPasses(?object $user, ?string $phone): array
    {
        $passenger = $this->resolvePassenger($user, $phone);
        if (!$passenger) {
            return ['success' => false, 'passes' => []];
        }

        $credits = $this->creditRepository->findBy(['passenger' => $passenger], ['createdAt' => 'DESC']);
        $passes = [];

        foreach ($credits as $credit) {
            $st = strtoupper((string) $credit->getStatus());

            $ticketStatus = 'En validation';
            if (in_array($st, ['APPROVED', 'VALIDE', 'VALIDATED'])) {
                $ticketStatus = 'Valide';
            } elseif (in_array($st, ['USED', 'SCANNED', 'UTILISE'])) {
                $ticketStatus = 'Utilisé';
            } elseif (in_array($st, ['REFUSED', 'REJECTED', 'REFUSE', 'CANCELLED'])) {
                $ticketStatus = 'Refusé';
            }

            $refundStatus = 'Non remboursé';
            if (method_exists($credit, 'getRefundStatus') && $credit->getRefundStatus()) {
                $refundStatus = $credit->getRefundStatus();
            }

            $compagnie = $credit->getCompany() ? $credit->getCompany()->getName() : ($credit->getDepartureCompany() ?? 'UTB');
            $trajet = sprintf('%s - %s', $credit->getDepartureCity() ?? 'Abidjan', $credit->getArrivalCity() ?? 'Yamoussoukro');
            $formattedDate = $credit->getTravelDate() ? $this->formatFrenchDate($credit->getTravelDate()) : '15 Janvier 2026';
            $numberOfTickets = max(1, (int) ($credit->getPassengerCount() ?? 1));
            $unitPrice = (int) ($credit->getUnitPrice() ?? ($credit->getTotalAmount() ? intval($credit->getTotalAmount() / $numberOfTickets) : 5000));
            $prix = (int) ($credit->getTotalAmount() ?? ($unitPrice * $numberOfTickets));

            $ticketsArray = [];
            $existingTickets = $credit->getTickets();

            if (($existingTickets->isEmpty() || count($existingTickets) < $numberOfTickets) && $ticketStatus === 'Valide') {
                try {
                    $this->qrCodeService->generateTicketsForCreditRequest($credit);
                    $existingTickets = $credit->getTickets();
                } catch (\Throwable $e) {
                }
            }

            if (!$existingTickets->isEmpty()) {
                foreach ($existingTickets as $t) {
                    if (!$t->getUuid()) {
                        $t->setUuid(\Ramsey\Uuid\Uuid::uuid4()->toString());
                    }
                    $code = $t->getTicketNumber() ?? sprintf('PASS-V-%s-%d', strtoupper(substr(md5((string) $credit->getId()), 0, 6)), $t->getTicketIndex());
                    $qrPayload = $this->qrCodeService->generateQrPayload($credit, $code, $t->getTicketIndex() ?: 1, $t);
                    $qrDataUri = $this->qrCodeService->generateQrCodeDataUri($qrPayload);

                    if (!$t->getQrCodeContent() || !str_contains((string) $t->getQrCodeContent(), 'ticketUuid')) {
                        try {
                            $t->setQrCodeContent($qrDataUri);
                            $this->em->persist($t);
                            $this->em->flush();
                        } catch (\Throwable $e) {
                        }
                    }

                    $ticketsArray[] = [
                        'id' => $t->getId(),
                        'ticketIndex' => $t->getTicketIndex(),
                        'ticketNumber' => ($ticketStatus === 'Valide' || $ticketStatus === 'Utilisé') ? $code : '*** *** ***',
                        'status' => $t->getStatus(),
                        'isUsed' => $t->getIsUsed() ?? false,
                        'unitPrice' => $t->getUnitPrice() > 0 ? $t->getUnitPrice() : $unitPrice,
                        'qrCodeContent' => ($ticketStatus === 'Valide' || $ticketStatus === 'Utilisé') ? ($t->getQrCodeContent() ?: $qrDataUri) : null,
                        'expirationDate' => (method_exists($t, 'getExpirationDate') && $t->getExpirationDate())
                            ? $t->getExpirationDate()->format('d/m/Y')
                            : null,
                    ];
                }
            } else {
                for ($i = 1; $i <= $numberOfTickets; $i++) {
                    $code = ($ticketStatus === 'Valide' || $ticketStatus === 'Utilisé')
                        ? sprintf('PASS-V-%s-%d', strtoupper(substr(md5($credit->getId() . '_' . $i), 0, 6)), $i)
                        : '*** *** ***';
                    $qrPayload = $this->qrCodeService->generateQrPayload($credit, $code, $i);
                    $qrDataUri = $this->qrCodeService->generateQrCodeDataUri($qrPayload);
                    $ticketsArray[] = [
                        'ticketIndex' => $i,
                        'ticketNumber' => $code,
                        'status' => $st,
                        'isUsed' => ($ticketStatus === 'Utilisé'),
                        'unitPrice' => $unitPrice,
                        'qrCodeContent' => ($ticketStatus === 'Valide' || $ticketStatus === 'Utilisé') ? $qrDataUri : null,
                    ];
                }
            }

            $amountToRepay = method_exists($credit, 'getAmountToRepay') ? $credit->getAmountToRepay() : ($credit->getAmountRequested() ?: $prix);
            $repaidAmount = method_exists($credit, 'getRepaidAmount') ? $credit->getRepaidAmount() : 0;
            $remainingAmount = max(0, $amountToRepay - $repaidAmount);

            $passes[] = [
                'id' => $credit->getId(),
                'uuid' => $credit->getUuid(),
                'compagnie' => $compagnie,
                'trajet' => $trajet,
                'departureCity' => $credit->getDepartureCity(),
                'arrivalCity' => $credit->getArrivalCity(),
                'date' => $formattedDate,
                'isRoundTrip' => $credit->getIsRoundTrip() ?? false,
                'typeVoyage' => ($credit->getIsRoundTrip() ? 'ALLER_RETOUR' : 'ALLER_SIMPLE'),
                'returnDate' => $credit->getReturnDate() ? $credit->getReturnDate()->format('d/m/Y') : null,
                'ticketStatus' => $ticketStatus,
                'refundStatus' => $refundStatus,
                'repaymentStatus' => method_exists($credit, 'getRepaymentStatus') ? $credit->getRepaymentStatus() : 'NOT_PAID',
                'amountRequested' => $credit->getAmountRequested() ?? $amountToRepay,
                'serviceFee' => $credit->getServiceFee() ?? 0,
                'amountToRepay' => $amountToRepay,
                'repaidAmount' => $repaidAmount,
                'remainingAmount' => $remainingAmount,
                'prix' => $prix,
                'unitPrice' => $unitPrice,
                'numberOfTickets' => $numberOfTickets,
                'tickets' => $ticketsArray,
                'rejectionReason' => method_exists($credit, 'getRejectionReason') ? $credit->getRejectionReason() : null,
                'createdAt' => $credit->getCreatedAt() ? $credit->getCreatedAt()->format('Y-m-d H:i:s') : null,
            ];
        }

        return [
            'success' => true,
            'passes' => $passes
        ];
    }

    private function formatFrenchDate(?\DateTimeInterface $date): string
    {
        if (!$date)
            return '';
        $months = [
            1 => 'Janvier',
            2 => 'Février',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Août',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Décembre'
        ];
        $day = $date->format('d');
        $month = $months[(int) $date->format('n')];
        $year = $date->format('Y');
        return "$day $month $year";
    }

    public function getAllCreditRequests(?string $search = null, ?string $statusFilter = null, ?string $companyFilter = null): array
    {
        $filters = [];
        if ($search && trim($search) !== '') {
            $filters['search'] = trim($search);
        }
        if ($statusFilter && trim($statusFilter) !== '') {
            $filters['status'] = trim($statusFilter);
        }
        if ($companyFilter && trim($companyFilter) !== '') {
            $filters['company'] = trim($companyFilter);
        }

        $requests = !empty($filters)
            ? $this->creditRepository->findByFilters($filters)
            : $this->creditRepository->findBy([], ['createdAt' => 'DESC', 'id' => 'DESC']);

        $updated = false;
        foreach ($requests as $credit) {
            if (!$credit->getUuid()) {
                $credit->setUuid(\Ramsey\Uuid\Uuid::uuid4()->toString());
                $updated = true;
            }
        }
        if ($updated) {
            $this->em->flush();
        }

        return $requests;
    }

    public function getAllPayments(?string $search = null, ?string $paymentMethod = null, ?string $type = null): array
    {
        $paymentRepo = $this->em->getRepository(Payment::class);
        $filters = [];
        if ($search && trim($search) !== '') {
            $filters['search'] = trim($search);
        }
        if ($paymentMethod && trim($paymentMethod) !== '') {
            $filters['paymentMethod'] = trim($paymentMethod);
        }
        if ($type && trim($type) !== '') {
            $filters['type'] = trim($type);
        }

        if (!empty($filters) && method_exists($paymentRepo, 'findByFilters')) {
            return $paymentRepo->findByFilters($filters);
        }

        return $paymentRepo->findBy([], ['paymentDate' => 'DESC', 'id' => 'DESC']);
    }
}
