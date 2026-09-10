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

    public function getEspaceFinancesData(?Company $company): array
    {
        if (!$company) {
            throw new ExceptionApi('Aucune compagnie associée à votre compte.', ['msg' => 'Aucune compagnie trouvée.'], Response::HTTP_BAD_REQUEST);
        }

        $fund = $this->em->getRepository(\App\Entity\Business\CompanyFund::class)->findOneBy(['company' => $company]);
        
        $data = [
            'fondsTotal' => $fund ? $fund->getTotalAmount() : 0,
            'soldeRestant' => $fund ? $fund->getRemainingAmount() : 0,
            'consommation' => $fund ? $fund->getConsumedAmount() : 0,
            'tauxUtilisation' => $fund ? $fund->getPercentage() : 0,
            'transactions' => []
        ];

        if ($fund) {
            $history = $this->em->getRepository(\App\Entity\Business\CompanyFundHistory::class)->findBy(
                ['companyFund' => $fund], 
                ['createdAt' => 'DESC'], 
                50
            );
            
            foreach ($history as $h) {
                $dt = $h->getCreatedAt() ? clone $h->getCreatedAt() : new \DateTime();
                $dt = $dt->setTimezone(new \DateTimeZone('Africa/Abidjan'));
                
                $data['transactions'][] = [
                    'date' => $dt->format('d/m/Y H:i'),
                    'typeBadge' => $h->getType() === 'RECHARGE' ? 'Crédit Alloué' : 'Débit Billet',
                    'typeClass' => $h->getType() === 'RECHARGE' ? 'bg-success text-white' : 'bg-danger text-white',
                    'icon' => $h->getType() === 'RECHARGE' ? 'icon-arrow-down-left' : 'icon-arrow-up-right',
                    'ref' => $h->getReference(),
                    'desc' => $h->getDescription(),
                    'amount' => ($h->getType() === 'RECHARGE' ? '+' : '-') . number_format($h->getAmount(), 0, '', ' ') . ' XOF',
                    'amountClass' => $h->getType() === 'RECHARGE' ? 'text-success' : 'text-danger',
                    'balanceAfter' => number_format($h->getNewBalance(), 0, '', ' ') . ' XOF',
                    'author' => $h->getPerformedBy()
                ];
            }
        }

        return $data;
    }

    public function getEspaceDashboardData(?Company $company, string $period = 'all'): array
    {
        if (!$company) {
            throw new ExceptionApi('Aucune compagnie associée à votre compte.', ['msg' => 'Aucune compagnie trouvée.'], Response::HTTP_BAD_REQUEST);
        }

        $fund = $this->em->getRepository(\App\Entity\Business\CompanyFund::class)->findOneBy(['company' => $company]);
        $ticketRepo = $this->em->getRepository(\App\Entity\Business\Ticket::class);
        $stationRepo = $this->em->getRepository(\App\Entity\Business\Station::class);
        $agentRepo = $this->em->getRepository(\App\Entity\Business\Agent::class);

        $dateStart = null;
        $dateEnd = null;
        if ($period !== 'all') {
            $dateStart = new \DateTime('today');
            $dateEnd = (clone $dateStart)->modify('+1 day');
            if ($period === 'week') {
                $dateStart->modify('-7 days');
            } elseif ($period === 'month') {
                $dateStart->modify('-1 month');
            } elseif ($period === 'year') {
                $dateStart->modify('-1 year');
            }
        }

        $qbTickets = $this->em->createQueryBuilder()
            ->select('count(t.id)')
            ->from(\App\Entity\Business\Ticket::class, 't')
            ->where('t.company = :comp')->setParameter('comp', $company)
            ->andWhere("UPPER(t.status) NOT IN ('REFUSED', 'REJECTED', 'EXPIRED', 'CANCELLED', 'ANNULE', 'ANNULÉ', 'REFUSE', 'REFUSÉ')")
            ->andWhere('(t.isUsed = true OR UPPER(t.status) IN (\'SCANNED\', \'SCANNE\', \'USED\', \'CONSOMME\', \'CONSOMMÉ\'))');

        $qbSales = $this->em->createQueryBuilder()
            ->select('SUM(t.unitPrice)')
            ->from(\App\Entity\Business\Ticket::class, 't')
            ->where('t.company = :comp')->setParameter('comp', $company)
            ->andWhere("UPPER(t.status) NOT IN ('REFUSED', 'REJECTED', 'EXPIRED', 'CANCELLED', 'ANNULE', 'ANNULÉ', 'REFUSE', 'REFUSÉ')")
            ->andWhere('(t.isUsed = true OR UPPER(t.status) IN (\'SCANNED\', \'SCANNE\', \'USED\', \'CONSOMME\', \'CONSOMMÉ\'))');

        if ($dateStart && $dateEnd) {
            $qbTickets->andWhere('t.validatedAt >= :start')->setParameter('start', $dateStart);
            $qbTickets->andWhere('t.validatedAt < :end')->setParameter('end', $dateEnd);

            $qbSales->andWhere('t.validatedAt >= :start')->setParameter('start', $dateStart);
            $qbSales->andWhere('t.validatedAt < :end')->setParameter('end', $dateEnd);
        }

        $totalTickets = $qbTickets->getQuery()->getSingleScalarResult();
        $totalSales = $qbSales->getQuery()->getSingleScalarResult();

        $totalStations = $this->em->createQueryBuilder()
            ->select('count(s.id)')
            ->from(\App\Entity\Business\Station::class, 's')
            ->where('s.company = :comp')->setParameter('comp', $company)
            ->getQuery()->getSingleScalarResult();

        $totalAgents = $this->em->createQueryBuilder()
            ->select('count(a.id)')
            ->from(\App\Entity\Business\Agent::class, 'a')
            ->where('a.company = :comp')->setParameter('comp', $company)
            ->getQuery()->getSingleScalarResult();

        $topStationQb = $this->em->createQueryBuilder()
            ->select('IDENTITY(t.validatedAtStation) as stationId', 'COUNT(t.id) as scanCount')
            ->from(\App\Entity\Business\Ticket::class, 't')
            ->where('t.company = :comp')->setParameter('comp', $company)
            ->andWhere('t.validatedAtStation IS NOT NULL')
            ->andWhere("UPPER(t.status) NOT IN ('REFUSED', 'REJECTED', 'EXPIRED', 'CANCELLED', 'ANNULE', 'ANNULÉ', 'REFUSE', 'REFUSÉ')")
            ->andWhere('(t.isUsed = true OR UPPER(t.status) IN (\'SCANNED\', \'SCANNE\', \'USED\', \'CONSOMME\', \'CONSOMMÉ\'))')
            ->groupBy('t.validatedAtStation')
            ->orderBy('scanCount', 'DESC')
            ->setMaxResults(1);

        if ($dateStart && $dateEnd) {
            $topStationQb->andWhere('t.validatedAt >= :start')->setParameter('start', $dateStart);
            $topStationQb->andWhere('t.validatedAt < :end')->setParameter('end', $dateEnd);
        }

        $topStationQuery = $topStationQb->getQuery()->getResult();

        $periodTotalScans = $totalTickets;

        $topStationData = null;
        if (!empty($topStationQuery) && $periodTotalScans > 0) {
            $stId = $topStationQuery[0]['stationId'];
            $stScanCount = (int)$topStationQuery[0]['scanCount'];
            $st = $this->em->getRepository(\App\Entity\Business\Station::class)->find($stId);
            
            if ($st) {
                $percentage = (int)(($stScanCount / $periodTotalScans) * 100);
                $topStationData = [
                    'name' => $st->getName(),
                    'percentage' => $percentage
                ];
            }
        }

        $data = [
            'totalSales' => $period === 'all' ? ($fund ? $fund->getConsumedAmount() : 0) : (float)$totalSales, 
            'totalTickets' => (int)$totalTickets,
            'activeStations' => (int)$totalStations,
            'activeAgents' => (int)$totalAgents,
            'recentActivities' => [],
            'topStationPerformance' => $topStationData
        ];

        if ($fund) {
            $history = $this->em->getRepository(\App\Entity\Business\CompanyFundHistory::class)->findBy(
                ['companyFund' => $fund], 
                ['createdAt' => 'DESC'], 
                5
            );
            
            $ticketRepo = $this->em->getRepository(\App\Entity\Business\Ticket::class);

            foreach ($history as $h) {
                $isTicket = $h->getType() !== 'RECHARGE';
                $title = $isTicket ? 'Billet Scanné' : 'Nouveau Solde';
                $stationName = 'Gare / Centrale';
                
                if ($isTicket) {
                    $ref = $h->getReference() ?? '';
                    $ticketNum = str_replace(['TK-', 'TK '], '', $ref);
                    $ticketNum = trim($ticketNum);

                    $ticket = $ticketRepo->findOneBy(['ticketNumber' => $ticketNum]);
                    
                    if (!$ticket) {
                        // Fallback: search anywhere in ticketNumber
                        $ticket = $ticketRepo->createQueryBuilder('t')
                            ->where('t.ticketNumber LIKE :num')
                            ->setParameter('num', '%' . $ticketNum . '%')
                            ->setMaxResults(1)
                            ->getQuery()->getOneOrNullResult();
                    }

                    if ($ticket && $ticket->getValidatedAtStation()) {
                        $stationName = $ticket->getValidatedAtStation()->getName();
                    }
                }
                
                $dt = clone $h->getCreatedAt();
                $dt = $dt->setTimezone(new \DateTimeZone('Africa/Abidjan'));
                
                $data['recentActivities'][] = [
                    'title' => $title,
                    'desc' => $isTicket ? $h->getReference() : 'Crédit/Rechargement Associé',
                    'station' => $stationName, 
                    'agent' => $h->getPerformedBy() ?? 'Système',
                    'time' => $dt->format('d/m/Y H:i'),
                    'amount' => number_format($h->getAmount(), 0, '', ' ') . ' XOF',
                    'status' => 'success'
                ];
            }
        }

        return $data;
    }

    public function getEspaceActivitesGaresData(?Company $company, ?string $startDate = null, ?string $endDate = null, ?string $search = null, ?string $status = null): array
    {
        if (!$company) {
            throw new ExceptionApi('Aucune compagnie associée à votre compte.', ['msg' => 'Aucune compagnie trouvée.'], Response::HTTP_BAD_REQUEST);
        }

        $qb = $this->em->createQueryBuilder()
            ->select('s', 'c')
            ->from(\App\Entity\Business\Station::class, 's')
            ->leftJoin('s.city', 'c')
            ->where('s.company = :comp')->setParameter('comp', $company);

        if ($search) {
            $qb->andWhere('s.name LIKE :search OR c.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($status && in_array(strtolower($status), ['active', 'inactive'])) {
            $qb->andWhere('s.isActive = :isActive')
               ->setParameter('isActive', strtolower($status) === 'active');
        }

        $stations = $qb->getQuery()->getResult();

        $data = ['stations' => []];

        if (empty($stations)) {
            return $data;
        }

        $dtStart = null;
        if ($startDate) {
            $dtStart = \DateTime::createFromFormat('Y-m-d', $startDate);
            if ($dtStart) $dtStart->setTime(0, 0, 0);
        }

        $dtEnd = null;
        if ($endDate) {
            $dtEnd = \DateTime::createFromFormat('Y-m-d', $endDate);
            if ($dtEnd) $dtEnd->setTime(23, 59, 59);
        }

        // 1. Group active agents by station
        $agentQb = $this->em->createQueryBuilder()
            ->select('IDENTITY(a.stationAssigned) as stationId', 'COUNT(a.id) as agentCount')
            ->from(\App\Entity\Business\Agent::class, 'a')
            ->where('a.company = :comp')->setParameter('comp', $company)
            ->andWhere('a.isActive = true')
            ->andWhere('a.stationAssigned IS NOT NULL')
            ->groupBy('a.stationAssigned');
        
        $agentsData = $agentQb->getQuery()->getResult();
        $agentsMap = [];
        foreach ($agentsData as $row) {
            $agentsMap[$row['stationId']] = (int)$row['agentCount'];
        }

        // 2. Group tickets scans and revenue by station
        $ticketQb = $this->em->createQueryBuilder()
            ->select('IDENTITY(t.validatedAtStation) as stationId', 'COUNT(t.id) as totalScans', 'SUM(t.unitPrice) as totalRevenue')
            ->from(\App\Entity\Business\Ticket::class, 't')
            ->where('t.company = :comp')->setParameter('comp', $company)
            ->andWhere('t.validatedAtStation IS NOT NULL')
            ->andWhere("UPPER(t.status) NOT IN ('REFUSED', 'REJECTED', 'EXPIRED', 'CANCELLED', 'ANNULE', 'ANNULÉ', 'REFUSE', 'REFUSÉ')")
            ->andWhere('(t.isUsed = true OR UPPER(t.status) IN (\'SCANNED\', \'SCANNE\', \'USED\', \'CONSOMME\', \'CONSOMMÉ\'))');

        if ($dtStart) {
            $ticketQb->andWhere('t.validatedAt >= :start')->setParameter('start', $dtStart);
        }
        if ($dtEnd) {
            $ticketQb->andWhere('t.validatedAt <= :end')->setParameter('end', $dtEnd);
        }
        $ticketQb->groupBy('t.validatedAtStation');

        $ticketsData = $ticketQb->getQuery()->getResult();
        $ticketsMap = [];
        foreach ($ticketsData as $row) {
            $ticketsMap[$row['stationId']] = [
                'totalScans' => (int)$row['totalScans'],
                'totalRevenue' => (float)$row['totalRevenue']
            ];
        }

        foreach ($stations as $station) {
            $stationId = $station->getId();
            
            $activeAgents = $agentsMap[$stationId] ?? 0;
            $totalScans = $ticketsMap[$stationId]['totalScans'] ?? 0;
            $revenue = $ticketsMap[$stationId]['totalRevenue'] ?? 0;
            $city = $station->getCity() ? $station->getCity()->getName() : 'Non définie';

            $data['stations'][] = [
                'uuid' => $station->getUuid(),
                'name' => $station->getName(),
                'city' => $city,
                'activeAgents' => $activeAgents,
                'totalScans' => $totalScans,
                'revenue' => number_format($revenue, 0, '', ' ') . ' XOF',
                'status' => $station->getIsActive() ? 'Active' : 'Inactive'
            ];
        }

        return $data;
    }

    public function getStationStats(string $uuid, ?Company $company): array
    {
        if (!$company) {
            throw new ExceptionApi('Aucune compagnie associée à votre compte.', ['msg' => 'Aucune compagnie trouvée.'], Response::HTTP_BAD_REQUEST);
        }

        $station = $this->em->getRepository(\App\Entity\Business\Station::class)->findOneBy(['uuid' => $uuid, 'company' => $company]);
        if (!$station) {
            throw new ExceptionApi('Gare introuvable ou accès refusé.', ['msg' => 'Gare introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $weeklyVolumes = [];
        $today = new \DateTime('today');
        
        $maxVolume = 0;
        
        for ($i = 6; $i >= 0; $i--) {
            $dateStart = (clone $today)->modify("-$i days");
            $dateEnd = (clone $dateStart)->modify('+1 day');
            
            $count = $this->em->createQueryBuilder()
                ->select('count(t.id)')
                ->from(\App\Entity\Business\Ticket::class, 't')
                ->where('t.validatedAtStation = :station')->setParameter('station', $station)
                ->andWhere("UPPER(t.status) NOT IN ('REFUSED', 'REJECTED', 'EXPIRED', 'CANCELLED', 'ANNULE', 'ANNULÉ', 'REFUSE', 'REFUSÉ')")
                ->andWhere('(t.isUsed = true OR UPPER(t.status) IN (\'SCANNED\', \'SCANNE\', \'USED\', \'CONSOMME\', \'CONSOMMÉ\'))')
                ->andWhere('t.validatedAt >= :start')->setParameter('start', $dateStart)
                ->andWhere('t.validatedAt < :end')->setParameter('end', $dateEnd)
                ->getQuery()->getSingleScalarResult();
                
            $count = (int)$count;
            if ($count > $maxVolume) $maxVolume = $count;
            
            $days = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 7 => 'Dim'];
            $dayName = $days[(int)$dateStart->format('N')];
            
            $weeklyVolumes[] = [
                'day' => $dayName,
                'count' => $count
            ];
        }

        foreach ($weeklyVolumes as &$vol) {
            $vol['percentage'] = $maxVolume > 0 ? (int)(($vol['count'] / $maxVolume) * 100) : 0;
        }

        // 2. Performances des agents
        $agentQuery = $this->em->createQueryBuilder()
            ->select('IDENTITY(t.validatedByAgent) as agentId', 'COUNT(t.id) as scanCount')
            ->from(\App\Entity\Business\Ticket::class, 't')
            ->where('t.validatedAtStation = :station')->setParameter('station', $station)
            ->andWhere('t.validatedByAgent IS NOT NULL')
            ->andWhere("UPPER(t.status) NOT IN ('REFUSED', 'REJECTED', 'EXPIRED', 'CANCELLED', 'ANNULE', 'ANNULÉ', 'REFUSE', 'REFUSÉ')")
            ->andWhere('(t.isUsed = true OR UPPER(t.status) IN (\'SCANNED\', \'SCANNE\', \'USED\', \'CONSOMME\', \'CONSOMMÉ\'))')
            ->groupBy('t.validatedByAgent')
            ->orderBy('scanCount', 'DESC')
            ->setMaxResults(5)
            ->getQuery();
            
        $topAgentsRaw = $agentQuery->getResult();
        $topAgents = [];
        
        if (!empty($topAgentsRaw)) {
            $agentIds = array_column($topAgentsRaw, 'agentId');
            
            $agentsDetail = $this->em->createQueryBuilder()
                ->select('a.id, a.firstname, a.lastname, a.shiftStart, a.shiftEnd')
                ->from(\App\Entity\Business\Agent::class, 'a')
                ->where('a.id IN (:ids)')->setParameter('ids', $agentIds)
                ->getQuery()->getResult();
                
            $detailsMap = [];
            foreach ($agentsDetail as $detail) {
                $detailsMap[$detail['id']] = $detail;
            }
            
            foreach ($topAgentsRaw as $row) {
                $aId = $row['agentId'];
                if (!isset($detailsMap[$aId])) continue;
                $aDetail = $detailsMap[$aId];
                
                $initials = strtoupper(substr($aDetail['firstname'] ?? 'A', 0, 1) . substr($aDetail['lastname'] ?? ' ', 0, 1));
                $shift = 'Matin / Soir';
                if (!empty($aDetail['shiftStart']) && !empty($aDetail['shiftEnd'])) {
                    $shift = sprintf('%s - %s', $aDetail['shiftStart'], $aDetail['shiftEnd']);
                }
                $topAgents[] = [
                    'initials' => $initials,
                    'name' => sprintf('%s %s.', $aDetail['firstname'] ?? 'Agent', substr($aDetail['lastname'] ?? '', 0, 1)),
                    'shift' => $shift,
                    'count' => (int)$row['scanCount']
                ];
            }
        }

        return [
            'name' => $station->getName(),
            'weeklyVolumes' => $weeklyVolumes,
            'topAgents' => $topAgents
        ];
    }
    
    public function getEspaceBilletsScannesData(?Company $company, ?string $search = null, ?string $stationName = null, ?string $startDate = null, ?string $endDate = null, int $page = 1, int $limit = 10): array
    {
        if (!$company) {
            throw new ExceptionApi('Aucune compagnie associée à votre compte.', ['msg' => 'Aucune compagnie trouvée.'], Response::HTTP_BAD_REQUEST);
        }

        $qb = $this->em->createQueryBuilder()
            ->select('t', 'a', 'cr', 'p', 'st')
            ->from(\App\Entity\Business\Ticket::class, 't')
            ->leftJoin('t.validatedByAgent', 'a')
            ->leftJoin('t.validatedAtStation', 'st')
            ->leftJoin('t.creditRequest', 'cr')
            ->leftJoin('cr.passenger', 'p')
            ->where('t.validatedByAgent IS NOT NULL')
            ->andWhere('st.company = :company')
            ->andWhere("UPPER(t.status) NOT IN ('REFUSED', 'REJECTED', 'EXPIRED', 'CANCELLED', 'ANNULE', 'ANNULÉ', 'REFUSE', 'REFUSÉ')")
            ->andWhere('(t.isUsed = true OR UPPER(t.status) IN (\'SCANNED\', \'SCANNE\', \'USED\', \'CONSOMME\', \'CONSOMMÉ\'))')
            ->setParameter('company', $company)
            ->orderBy('t.validatedAt', 'DESC');

        if ($startDate) {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $startDate);
            if ($dateObj) {
                $qb->andWhere('t.validatedAt >= :startDate')
                   ->setParameter('startDate', $dateObj->setTime(0, 0, 0));
            }
        }
        
        if ($endDate) {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $endDate);
            if ($dateObj) {
                $qb->andWhere('t.validatedAt <= :endDate')
                   ->setParameter('endDate', $dateObj->setTime(23, 59, 59));
            }
        }

        if ($search && trim($search) !== '') {
            $term = '%' . mb_strtolower(trim($search)) . '%';
            $qb->andWhere('LOWER(t.ticketNumber) LIKE :term OR LOWER(a.firstname) LIKE :term OR LOWER(a.lastname) LIKE :term OR LOWER(CONCAT(a.firstname, \' \', a.lastname)) LIKE :term OR LOWER(p.firstname) LIKE :term OR LOWER(p.lastname) LIKE :term OR LOWER(CONCAT(p.firstname, \' \', p.lastname)) LIKE :term OR LOWER(st.name) LIKE :term')
               ->setParameter('term', $term);
        }

        if ($stationName && trim($stationName) !== '') {
            $qb->andWhere('LOWER(st.name) LIKE :station')
               ->setParameter('station', '%' . mb_strtolower(trim($stationName)) . '%');
        }

        // Count total results for pagination
        $countQb = clone $qb;
        $totalItems = (int) $countQb->select('COUNT(t.id)')->getQuery()->getSingleScalarResult();

        if ($limit > 0) {
            $qb->setMaxResults($limit);
            $qb->setFirstResult(($page - 1) * $limit);
        } else {
            $qb->setMaxResults(500); // hard cap if "All" is selected to avoid memory crash
        }
        
        $tickets = $qb->getQuery()->getResult();

        $data = [];
        foreach ($tickets as $t) {
            $passenger = null;
            $credit = $t->getCreditRequest();
            $agent = $t->getValidatedByAgent();
            $agentName = $agent ? ($agent->getFirstname() . ' ' . $agent->getLastname()) : '-';
            
            $passengerName = '-';
            if ($credit && $credit->getPassenger()) {
                $passengerName = $credit->getPassenger()->getFirstname() . ' ' . $credit->getPassenger()->getLastname();
            }

            $station = $t->getValidatedAtStation();
            $stationStr = $station ? $station->getName() : 'Gare Inconnue';

            $departureCity = $credit ? ($credit->getDepartureCity() ?? 'Abidjan') : 'Abidjan';
            $arrivalCity = $credit ? ($credit->getArrivalCity() ?? 'Yamoussoukro') : 'Yamoussoukro';
            $trajet = sprintf('%s - %s', $departureCity, $arrivalCity);

            $rawStatus = strtoupper((string) $t->getStatus());
            $uiStatus = 'Scanné';
            if (in_array($rawStatus, ['REFUSED', 'REJECTED', 'EXPIRED', 'CANCELLED'])) {
                $uiStatus = 'Refusé';
            }

            $dtStr = '-';
            if ($t->getValidatedAt()) {
                $dt = clone $t->getValidatedAt();
                $dt = $dt->setTimezone(new \DateTimeZone('Africa/Abidjan'));
                $m = (int) $dt->format('n');
                $months = [1 => 'Janv', 2 => 'Fév', 3 => 'Mars', 4 => 'Avr', 5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Août', 9 => 'Sept', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc'];
                $dtStr = $dt->format('d') . ' ' . $months[$m] . '. ' . $dt->format('H:i');
            }

            $data[] = [
                'num' => $t->getTicketNumber() ?? ('PASS-' . str_pad((string)$t->getId(), 4, '0', STR_PAD_LEFT)),
                'passenger' => trim($passengerName),
                'trajectory' => $trajet,
                'station' => $stationStr,
                'agent' => trim($agentName),
                'time' => $dtStr,
                'amount' => number_format($t->getUnitPrice(), 0, ',', ' ') . ' XOF',
                'status' => $uiStatus,
                'rawStatus' => $rawStatus
            ];
        }

        return [
            'data' => $data,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalItems,
                'totalPages' => $limit > 0 ? (int)ceil($totalItems / $limit) : 1
            ]
        ];
    }
}
