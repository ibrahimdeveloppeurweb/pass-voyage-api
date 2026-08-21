<?php

namespace App\Manager\Business;

use App\Repository\Business\TicketRepository;
use App\Service\QrCodeService;
use Doctrine\ORM\EntityManagerInterface;

class TicketManager
{
    private TicketRepository $ticketRepository;
    private EntityManagerInterface $em;
    private QrCodeService $qrCodeService;

    public function __construct(
        TicketRepository $ticketRepository,
        EntityManagerInterface $em,
        QrCodeService $qrCodeService
    ) {
        $this->ticketRepository = $ticketRepository;
        $this->em = $em;
        $this->qrCodeService = $qrCodeService;
    }

    public function getFormattedTicketList(?string $search = null, ?string $statusFilter = null, ?string $companyFilter = null): array
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

        $tickets = !empty($filters)
            ? $this->ticketRepository->findByFilters($filters)
            : $this->ticketRepository->findBy([], ['id' => 'DESC']);

        $result = [];

        foreach ($tickets as $t) {
            $credit = null;
            $passenger = null;

            try {
                $c = $t->getCreditRequest();
                if ($c) {
                    $c->getId();
                    $passenger = $c->getPassenger();
                    if ($passenger) {
                        $passenger->getId();
                    }
                    $credit = $c;
                }
            } catch (\Throwable $e) {
                $credit = null;
                $passenger = null;
            }

            $passengerName = $passenger 
                ? sprintf('%s %s', $passenger->getFirstName() ?? '', $passenger->getLastName() ?? '')
                : 'Passager Inconnu';
            if (trim($passengerName) === '') {
                $passengerName = $passenger ? ($passenger->getPhoneNumber() ?? 'Passager Inconnu') : 'Passager Inconnu';
            }

            $company = null;
            try {
                $company = $t->getCompany() ?? ($credit ? $credit->getCompany() : null);
                if ($company) {
                    $company->getId();
                }
            } catch (\Throwable $e) {
                $company = null;
            }

            $companyName = $company ? $company->getName() : ($credit ? ($credit->getDepartureCompany() ?? 'UTB') : 'UTB');

            $departureCity = $credit ? ($credit->getDepartureCity() ?? 'Abidjan') : 'Abidjan';
            $arrivalCity = $credit ? ($credit->getArrivalCity() ?? 'Yamoussoukro') : 'Yamoussoukro';
            $trajet = sprintf('%s - %s', $departureCity, $arrivalCity);

            $dateValidite = date('Y-m-d');
            if ($credit) {
                try {
                    if ($credit->getTravelDate()) {
                        $dateValidite = $credit->getTravelDate()->format('Y-m-d');
                    }
                } catch (\Throwable $e) {}
            }

            $qrStatus = 'Valide';
            $statusUpper = strtoupper((string)$t->getStatus());
            if (in_array($statusUpper, ['REFUSED', 'REJECTED', 'REFUSE'])) {
                $qrStatus = 'Refusé';
            } elseif ($t->getIsUsed() || in_array($statusUpper, ['USED', 'SCANNED', 'SCANNE', 'CONSOMME'])) {
                $qrStatus = 'Scanné';
            } elseif (in_array($statusUpper, ['EXPIRED', 'EXPIRE'])) {
                $qrStatus = 'Expiré';
            } elseif (in_array($statusUpper, ['CANCELLED', 'ANNULE'])) {
                $qrStatus = 'Annulé';
            }

            $num = $t->getTicketNumber() ?? sprintf('TK-%d', 990000 + $t->getId());

            if (!$t->getUuid()) {
                $t->setUuid(\Ramsey\Uuid\Uuid::uuid4()->toString());
            }

            $qrContent = $t->getQrCodeContent();
            if ($credit && (!$qrContent || !str_contains((string)$qrContent, 'ticketUuid'))) {
                try {
                    $qrPayload = $this->qrCodeService->generateQrPayload($credit, $num, $t->getTicketIndex() ?: 1, $t);
                    $qrContent = $this->qrCodeService->generateQrCodeDataUri($qrPayload);
                    $t->setQrCodeContent($qrContent);
                    $this->em->persist($t);
                    $this->em->flush();
                } catch (\Throwable $e) {}
            }

            $agentInfo = null;
            $agentDisplayName = null;
            try {
                $agent = $t->getValidatedByAgent();
                if ($agent) {
                    $agentName = sprintf('%s %s', $agent->getFirstname() ?? '', $agent->getLastname() ?? '');
                    $code = $agent->getAgentCode() ? sprintf(' (%s)', $agent->getAgentCode()) : '';
                    $agentDisplayName = trim($agentName) . $code;
                    $agentInfo = [
                        'id' => $agent->getId(),
                        'uuid' => $agent->getUuid(),
                        'name' => trim($agentName) ?: 'Agent Contrôleur',
                        'firstname' => $agent->getFirstname(),
                        'lastname' => $agent->getLastname(),
                        'agentCode' => $agent->getAgentCode(),
                        'phoneNumber' => $agent->getPhoneNumber(),
                        'displayName' => $agentDisplayName,
                    ];
                }
            } catch (\Throwable $e) {}

            $stationInfo = null;
            $stationDisplayName = null;
            try {
                $agent = $t->getValidatedByAgent();
                $station = $t->getValidatedAtStation();
                if (!$station && $agent && $agent->getStationAssigned()) {
                    $station = $agent->getStationAssigned();
                }
                if ($station) {
                    $stationDisplayName = $station->getName();
                    $city = method_exists($station, 'getCity') ? $station->getCity() : null;
                    $cityName = $city ? (method_exists($city, 'getName') ? $city->getName() : (string)$city) : null;
                    $stationInfo = [
                        'id' => $station->getId(),
                        'uuid' => $station->getUuid(),
                        'name' => $station->getName(),
                        'ville' => $cityName,
                    ];
                }
            } catch (\Throwable $e) {}

            $result[] = [
                'id' => $t->getId(),
                'num' => $num,
                'passager' => trim($passengerName),
                'compagnie' => $companyName,
                'trajet' => $trajet,
                'dateValidite' => $dateValidite,
                'qrStatus' => $qrStatus,
                'qrCodeContent' => $qrContent,
                'unitPrice' => $t->getUnitPrice(),
                'status' => $t->getStatus(),
                'isUsed' => $t->getIsUsed(),
                'refusalComment' => $t->getRefusalComment(),
                'validatedByAgent' => $agentInfo,
                'validatedByAgentName' => $agentDisplayName,
                'validatedAtStation' => $stationInfo,
                'validatedAtStationName' => $stationDisplayName,
                'validatedAt' => $t->getValidatedAt() ? $t->getValidatedAt()->format('d/m/Y H:i') : null,
            ];
        }

        return $result;
    }
}
