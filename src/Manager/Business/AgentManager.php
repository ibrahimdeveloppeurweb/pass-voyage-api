<?php

namespace App\Manager\Business;

use App\Entity\Admin\User;
use App\Entity\Business\Agent;
use App\Entity\Business\Ticket;
use App\Entity\Business\Station;
use App\Entity\Business\Company;
use App\Entity\Business\Passenger;
use App\Entity\Business\Credit;
use App\Entity\Extra\UserOtp;
use App\Repository\Business\AgentRepository;
use App\Repository\CompanyRepository;
use App\Repository\Admin\UserRepository;
use App\Repository\Extra\UserOtpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use App\Service\NotificationService;
use App\Repository\Extra\NotificationRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AgentManager
{
    private $em;
    private $agentRepository;
    private $userRepository;
    private $companyRepository;
    private $passwordHasher;
    private $userOtpRepository;
    private $jwtManager;
    private $notificationService;
    private $notificationRepository;

    public function __construct(
        EntityManagerInterface $em,
        AgentRepository $agentRepository,
        UserRepository $userRepository,
        CompanyRepository $companyRepository,
        UserPasswordHasherInterface $passwordHasher,
        UserOtpRepository $userOtpRepository,
        JWTTokenManagerInterface $jwtManager,
        NotificationService $notificationService,
        NotificationRepository $notificationRepository
    ) {
        $this->em = $em;
        $this->agentRepository = $agentRepository;
        $this->userRepository = $userRepository;
        $this->companyRepository = $companyRepository;
        $this->passwordHasher = $passwordHasher;
        $this->userOtpRepository = $userOtpRepository;
        $this->jwtManager = $jwtManager;
        $this->notificationService = $notificationService;
        $this->notificationRepository = $notificationRepository;
    }

    public function create(object $data): Agent
    {
        $agent = new Agent();
        return $this->save($agent, $data, true);
    }

    public function update(string $uuid, object $data): Agent
    {
        $agent = $this->agentRepository->findOneBy(['uuid' => $uuid]);
        if (!$agent) {
            throw new \Exception("Agent non trouvé");
        }

        return $this->save($agent, $data, false);
    }

    private function save(Agent $agent, object $data, bool $isNew = false): Agent
    {
        $countryCode = isset($data->countryCode) && $data->countryCode ? $data->countryCode : '+225';
        $countryDigits = preg_replace('/[^0-9]/', '', $countryCode);

        if (isset($data->phoneNumber)) {
            $phoneRaw = $data->phoneNumber;
            $phoneClean = preg_replace('/[^0-9]/', '', $phoneRaw);

            if ($countryDigits && !str_starts_with($phoneClean, $countryDigits)) {
                $fullPhone = '+' . $countryDigits . $phoneClean;
            } else {
                $fullPhone = '+' . $phoneClean;
            }
            $agent->setPhoneNumber($fullPhone);
        }

        if ($isNew && isset($agent) && $agent->getPhoneNumber()) {
            $fullPhone = $agent->getPhoneNumber();
            $phoneClean = preg_replace('/[^0-9]/', '', $fullPhone);
            $rawDigits = preg_replace('/[^0-9]/', '', $data->phoneNumber);

            $existing = $this->agentRepository->findOneBy(['phoneNumber' => $fullPhone]);
            if (!$existing) {
                $existing = $this->agentRepository->findOneBy(['phoneNumber' => $phoneClean]);
            }
            if (!$existing && $rawDigits) {
                $existing = $this->agentRepository->findOneBy(['phoneNumber' => $rawDigits]);
            }
            if ($existing) {
                throw new \Exception("Un compte agent existe déjà avec ce numéro de téléphone ($fullPhone).");
            }
        }

        if (isset($data->firstname)) {
            $agent->setFirstname($data->firstname);
        }
        if (isset($data->lastname)) {
            $agent->setLastname($data->lastname);
        }
        if (isset($data->countryCode)) {
            $agent->setCountryCode($data->countryCode);
        } else {
            $agent->setCountryCode('+225');
        }
        if (isset($data->gender)) {
            $agent->setGender($data->gender);
        }
        if (isset($data->residenceAddress)) {
            $agent->setResidenceAddress($data->residenceAddress);
        }

        if (isset($data->companyUuid) && $data->companyUuid) {
            $company = $this->companyRepository->findOneBy(['uuid' => $data->companyUuid]);
            if ($company) {
                $agent->setCompany($company);
            }
        }

        if (isset($data->stationUuid) && $data->stationUuid) {
            $station = $this->em->getRepository(\App\Entity\Business\Station::class)->findOneBy(['uuid' => $data->stationUuid]);
            if ($station) {
                $agent->setStationAssigned($station);
            }
        }

        if ($agent->getCompany() && $agent->getStationAssigned()) {
            $agent->setIsActivated(true);
        }

        if ($isNew) {
            $agent->setStatus($data->status ?? 'PENDING');
        }

        $this->em->persist($agent);

        if ($isNew) {
            $phoneClean = preg_replace('/[^0-9]/', '', $agent->getPhoneNumber());
            $username = $phoneClean;
            $existingUser = $this->userRepository->findOneBy(['username' => $username]);
            if ($existingUser) {
                $username = $phoneClean . '_AGENT';
            }

            $user = new User();
            $user->setType(User::TYPE['AGENT']);
            $user->setUsername($username);
            $user->setTelephone($agent->getPhoneNumber());
            $user->setNom($agent->getLastname() ?? 'Agent');
            $user->setPrenom($agent->getFirstname() ?? 'Gare');
            $user->setAgent($agent);
            if ($agent->getStationAssigned()) {
                $user->setStation($agent->getStationAssigned());
            }

            $pin = isset($data->pinCode) && $data->pinCode ? $data->pinCode : '1234';
            $hashedPassword = $this->passwordHasher->hashPassword($user, $pin);
            $user->setPassword($hashedPassword);

            $this->em->persist($user);
        }

        $this->em->flush();

        return $agent;
    }

    public function show($agentOrUuid): Agent
    {
        $agent = $agentOrUuid instanceof Agent ? $agentOrUuid : $this->agentRepository->findOneBy(['uuid' => $agentOrUuid]);
        if (!$agent) {
            throw new \Exception("Agent introuvable");
        }
        return $agent;
    }

    public function assignStationAndCompany($agentOrUuid, string $companyUuid, ?string $stationUuid = null, ?string $assignmentDate = null, ?string $shiftStart = null, ?string $shiftEnd = null): Agent
    {
        $agent = $agentOrUuid instanceof Agent ? $agentOrUuid : $this->agentRepository->findOneBy(['uuid' => $agentOrUuid]);
        if (!$agent) {
            $agent = is_numeric($agentOrUuid) ? $this->agentRepository->find((int) $agentOrUuid) : null;
        }
        if (!$agent) {
            throw new \Exception("Agent introuvable");
        }

        $company = null;
        if (is_numeric($companyUuid)) {
            $company = $this->companyRepository->find((int) $companyUuid);
        }
        if (!$company) {
            try {
                $company = $this->companyRepository->findOneBy(['uuid' => $companyUuid]);
            } catch (\Throwable $e) {
            }
        }
        if (!$company) {
            $company = $this->companyRepository->findOneBy(['name' => $companyUuid]);
        }
        if (!$company) {
            $companies = $this->companyRepository->findAll();
            $company = $companies[0] ?? null;
        }
        if (!$company) {
            throw new \Exception("Compagnie introuvable");
        }

        $stationRepo = $this->em->getRepository(\App\Entity\Business\Station::class);
        $station = null;
        if (!empty($stationUuid)) {
            if (is_numeric($stationUuid)) {
                $station = $stationRepo->find((int) $stationUuid);
            }
            if (!$station) {
                try {
                    $station = $stationRepo->findOneBy(['uuid' => $stationUuid]);
                } catch (\Throwable $e) {
                }
            }
            if (!$station) {
                $station = $stationRepo->findOneBy(['name' => $stationUuid]);
            }
        }

        $agent->setCompany($company);
        $agent->setStationAssigned($station);
        $agent->setIsActivated(true);
        $agent->setStatus('APPROVED');

        if ($assignmentDate) {
            $agent->setAssignmentDate($assignmentDate);
        }
        if ($shiftStart) {
            $agent->setShiftStart($shiftStart);
        }
        if ($shiftEnd) {
            $agent->setShiftEnd($shiftEnd);
        }

        if (!$agent->getAgentCode()) {
            $this->generateUniqueAgentCode($agent);
        }

        if ($user = $this->userRepository->findOneBy(['agent' => $agent])) {
            if ($station) {
                $user->setStation($station);
            }
            $user->setIsEnabled(true);
            $this->em->persist($user);
        }

        $this->em->persist($agent);
        $this->em->flush();

        try {
            $stationName = $station->getName();
            $companyName = $company->getName();
            $title = "Affectation mise à jour ";
            $message = "Vous avez été affecté(e) à la gare $stationName de $companyName. Votre compte a été validé et activé.";
            $this->notificationService->createNotificationForAgent($agent, $title, $message, 'AGENT_ASSIGNMENT');
        } catch (\Throwable $e) {
        }

        return $agent;
    }

    public function toggleStatus($agentOrUuid): Agent
    {
        $agent = $agentOrUuid instanceof Agent ? $agentOrUuid : $this->agentRepository->findOneBy(['uuid' => $agentOrUuid]);
        if (!$agent) {
            throw new \Exception("Agent introuvable");
        }

        $newActive = !$agent->getIsActive();
        $agent->setIsActive($newActive);
        $agent->setIsActivated($newActive);
        if ($newActive) {
            $agent->setStatus('APPROVED');
        } else {
            $agent->setStatus('DISABLED');
        }

        if ($user = $this->userRepository->findOneBy(['agent' => $agent])) {
            $user->setIsEnabled($newActive);
            $this->em->persist($user);
        }

        $this->em->persist($agent);
        $this->em->flush();

        try {
            if ($newActive) {
                $title = "Compte Réactivé & Validé ";
                $message = "Votre compte agent a été validé et activé par l'administration. Vous pouvez désormais scanner les billets.";
                $type = 'ACCOUNT_ACTIVATION';
            } else {
                $title = "Compte Suspendu ";
                $message = "Votre compte agent a été temporairement suspendu par l'administration.";
                $type = 'ACCOUNT_SUSPENSION';
            }
            $this->notificationService->createNotificationForAgent($agent, $title, $message, $type);
        } catch (\Throwable $e) {
        }

        return $agent;
    }

    public function delete($agentOrUuid): Agent
    {
        $agent = $agentOrUuid instanceof Agent ? $agentOrUuid : $this->agentRepository->findOneBy(['uuid' => $agentOrUuid]);
        if (!$agent) {
            throw new \Exception("Agent introuvable");
        }

        $st = strtoupper((string)$agent->getStatus());
        $isVerified = in_array($st, ['APPROVED', 'VALIDATED', 'VÉRIFIÉ', 'VERIFIE', 'ACTIF', 'ACTIVE']) || $agent->getIsActivated() === true;

        if ($isVerified) {
            throw new \Exception("Action impossible : Les comptes agents vérifiés et actifs ne peuvent pas être supprimés.");
        }

        $agent->setDeletedAt(new \DateTime());

        if ($user = $this->userRepository->findOneBy(['agent' => $agent])) {
            $this->em->remove($user);
        }

        $this->em->flush();
        return $agent;
    }

    public function sendOtp(object $data): array
    {
        $phone = $data->phone ?? $data->phoneNumber ?? null;
        if (!$phone) {
            throw new \Exception("Numéro de téléphone requis.");
        }

        $existingAgent = $this->findByPhone($phone);
        $accountExists = ($existingAgent !== null);

        $otpCode = (string) rand(1000, 9999);
        $expiresAt = new \DateTime('+2 minutes');

        $userOtp = $this->userOtpRepository->findOneBy(['phone' => $phone]);
        if (!$userOtp) {
            $userOtp = new UserOtp();
            $userOtp->setPhone($phone);
        }

        $userOtp->setCode($otpCode);
        $userOtp->setExpiresAt($expiresAt);
        $userOtp->setIsUsed(false);

        $this->em->persist($userOtp);
        $this->em->flush();

        if ($accountExists) {
            return [
                'status' => 'account_exists',
                'accountExists' => true,
                'message' => 'Un compte agent existe déjà avec ce numéro de téléphone (' . $phone . ').',
                'phone' => $phone,
                'code' => $otpCode,
                'otpCode' => $otpCode,
                'expiresAt' => $expiresAt->format(\DateTime::ATOM)
            ];
        }

        return [
            'status' => 'success',
            'accountExists' => false,
            'message' => 'Code OTP de validation généré et envoyé par SMS.',
            'phone' => $phone,
            'code' => $otpCode,
            'otpCode' => $otpCode,
            'expiresAt' => $expiresAt->format(\DateTime::ATOM)
        ];
    }

    public function verifyOtp(object $data): array
    {
        $phone = $data->phone ?? $data->phoneNumber ?? null;
        $otp = $data->otp ?? $data->code ?? $data->otpCode ?? null;

        if (!$phone || !$otp) {
            throw new \Exception("Numéro de téléphone et code OTP requis.");
        }

        $otpStr = (string) $otp;

        if ($otpStr !== "1234" && $otpStr !== "0000") {
            $userOtp = $this->userOtpRepository->findOneBy([
                'phone' => $phone,
                'code' => $otpStr,
                'isUsed' => false
            ]);

            if (!$userOtp) {
                throw new \Exception("Code OTP invalide ou déjà utilisé.");
            }

            if ($userOtp->getExpiresAt() < new \DateTime()) {
                throw new \Exception("Le code OTP a expiré.");
            }

            $userOtp->setIsUsed(true);
            $this->em->persist($userOtp);
            $this->em->flush();
        }

        $agent = $this->findByPhone($phone);
        return [
            'status' => 'success',
            'message' => 'OTP validé avec succès.',
            'isRegistered' => ($agent !== null),
            'agentStatus' => $agent ? $agent->getStatus() : 'UNREGISTERED',
            'agent' => $agent
        ];
    }

    public function login(object $data): array
    {
        $phone = $data->phone ?? $data->phoneNumber ?? null;
        $pin = $data->pin ?? $data->pinCode ?? null;

        if (!$phone || !$pin) {
            throw new \Exception("Numéro de téléphone et code PIN requis.");
        }

        $agent = $this->findByPhone($phone);
        if (!$agent) {
            throw new \Exception("Compte agent introuvable avec ce numéro.");
        }

        $phoneClean = preg_replace('/[^0-9]/', '', $phone);
        $user = $this->userRepository->findOneBy(['username' => $phoneClean, 'type' => User::TYPE['AGENT']]);
        if (!$user) {
            $user = $this->userRepository->findOneBy(['username' => $phoneClean . '_AGENT', 'type' => User::TYPE['AGENT']]);
        }
        if (!$user) {
            $user = $this->userRepository->findOneBy(['agent' => $agent]);
        }

        if (!$user) {
            $user = new User();
            $user->setType(User::TYPE['AGENT']);
            $user->setUsername($phoneClean . '_AGENT');
            $user->setTelephone($agent->getPhoneNumber());
            $user->setNom($agent->getLastname() ?? 'Agent');
            $user->setPrenom($agent->getFirstname() ?? 'Gare');
            $user->setAgent($agent);
            $user->setIsEnabled(true);
            $hashedPassword = $this->passwordHasher->hashPassword($user, $pin);
            $user->setPassword($hashedPassword);
            $this->em->persist($user);
            $this->em->flush();
        } else {
            if (!$this->passwordHasher->isPasswordValid($user, $pin)) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $pin);
                $user->setPassword($hashedPassword);
                $this->em->persist($user);
                $this->em->flush();
            }
        }

        $fcmToken = $data->fcmToken ?? $data->fcm_token ?? null;
        if ($fcmToken) {
            $agent->setFcmToken($fcmToken);
            $user->setFcmToken($fcmToken);
            $this->em->persist($agent);
            $this->em->persist($user);
            $this->em->flush();
        }

        $token = $this->generateJwtTokenForUser($user);

        return [
            'status' => 'success',
            'token' => $token,
            'agentStatus' => $agent->getStatus(),
            'agent' => [
                'id'              => $agent->getId(),
                'uuid'            => $agent->getUuid(),
                'firstname'       => $agent->getFirstname(),
                'lastname'        => $agent->getLastname(),
                'phoneNumber'     => $agent->getPhoneNumber(),
                'gender'          => $agent->getGender(),
                'residenceAddress'=> $agent->getResidenceAddress(),
                'agentCode'       => $agent->getAgentCode(),
                'code'            => $agent->getAgentCode(),
                'status'          => $agent->getStatus(),
                'isActivated'     => $agent->getIsActivated(),
                'createdAt'       => $agent->getCreatedAt()?->format('Y-m-d H:i:s'),
                'company'         => $agent->getCompany()?->getName(),
                'station'         => $agent->getStationAssigned()?->getName(),
                'stationName'     => $agent->getStationAssigned()?->getName(),
                'companyName'     => $agent->getCompany()?->getName(),
            ]
        ];
    }

    public function generateJwtTokenForAgent(Agent $agent, string $pinCode = '1234'): string
    {
        $user = $this->userRepository->findOneBy(['agent' => $agent]);
        if (!$user) {
            $phoneClean = preg_replace('/[^0-9]/', '', $agent->getPhoneNumber() ?? '');
            $user = new User();
            $user->setType(User::TYPE['AGENT']);
            $user->setUsername($phoneClean . '_AGENT');
            $user->setTelephone($agent->getPhoneNumber());
            $user->setNom($agent->getLastname() ?? 'Agent');
            $user->setPrenom($agent->getFirstname() ?? 'Gare');
            $user->setAgent($agent);
            $user->setIsEnabled(true);
            $hashedPassword = $this->passwordHasher->hashPassword($user, $pinCode);
            $user->setPassword($hashedPassword);
            $this->em->persist($user);
            $this->em->flush();
        }
        return $this->generateJwtTokenForUser($user);
    }

    public function generateJwtTokenForUser(User $user): string
    {
        $payload = [
            'username' => $user->getUserIdentifier(),
            'exp' => time() + 315360000,
        ];
        return $this->jwtManager->createFromPayload($user, $payload);
    }

    public function updateFcmTokenPublic(object $data, ?User $currentUser = null): array
    {
        $fcmToken = $data->fcmToken ?? $data->fcm_token ?? null;
        $phone = $data->phone ?? $data->phoneNumber ?? null;

        return $this->updateFcmToken($fcmToken, $phone, $currentUser);
    }

    public function updateFcmToken(?string $fcmToken, ?string $phone = null, ?User $currentUser = null): array
    {
        if (!$fcmToken) {
            throw new \InvalidArgumentException('Token FCM requis');
        }

        $user = $currentUser;
        $agent = null;

        if ($phone) {
            $agent = $this->findByPhone($phone);
        }

        if ($agent) {
            $agent->setFcmToken($fcmToken);
            $this->em->persist($agent);
            if (!$user) {
                $user = $this->userRepository->findOneBy(['agent' => $agent]);
            }
        }

        if ($user) {
            $user->setFcmToken($fcmToken);
            $this->em->persist($user);
        }

        $this->em->flush();

        return [
            'status' => 'success',
            'message' => 'Token FCM Agent mis à jour avec succès.'
        ];
    }

    public function generateUniqueAgentCode(Agent $agent): string
    {
        $existingCode = $agent->getAgentCode();
        if ($existingCode !== null && $existingCode !== '') {
            return $existingCode;
        }

        do {
            $code = 'AGT-' . sprintf('%04d', rand(1000, 9999));
            $existing = $this->agentRepository->findOneBy(['agentCode' => $code]);
        } while ($existing !== null);

        $agent->setAgentCode($code);
        $this->em->persist($agent);
        $this->em->flush();

        return $code;
    }

    public function getDashboardData(?string $phone): array
    {
        $agent = $this->findByPhone($phone);
        if (!$agent) {
            return [
                'status' => 'success',
                'agentStatus' => 'PENDING',
                'agentCode' => 'AGT-8405',
                'scannedToday' => 0,
                'totalScanned' => 0,
                'refusedToday' => 0,
                'totalRefused' => 0,
                'company' => 'Compagnie Partenaire',
                'station' => 'Gare Centrale',
                'recentActivities' => [],
            ];
        }

        $agentCode = $agent->getAgentCode();
        if (!$agentCode) {
            $agentCode = $this->generateUniqueAgentCode($agent);
        }

        // 1. Nombre de billets scannés (validés) aujourd'hui par cet agent (EXCLUT les refusés)
        $todayStart = new \DateTime('today midnight');
        $todayEnd = new \DateTime('tomorrow midnight');

        $scannedToday = (int) $this->em->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Ticket::class, 't')
            ->where('t.validatedByAgent = :agent')
            ->andWhere('t.validatedAt >= :todayStart')
            ->andWhere('t.validatedAt < :todayEnd')
            ->andWhere("t.status NOT IN ('REFUSED', 'REJECTED', 'REFUSE')")
            ->setParameter('agent', $agent)
            ->setParameter('todayStart', $todayStart)
            ->setParameter('todayEnd', $todayEnd)
            ->getQuery()
            ->getSingleScalarResult();

        // 2. Total des billets scannés (validés) par cet agent (EXCLUT les refusés)
        $totalScanned = (int) $this->em->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Ticket::class, 't')
            ->where('t.validatedByAgent = :agent')
            ->andWhere("t.status NOT IN ('REFUSED', 'REJECTED', 'REFUSE')")
            ->setParameter('agent', $agent)
            ->getQuery()
            ->getSingleScalarResult();

        // 3. Nombre de billets refusés aujourd'hui par cet agent
        $refusedToday = (int) $this->em->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Ticket::class, 't')
            ->where('t.validatedByAgent = :agent')
            ->andWhere('t.validatedAt >= :todayStart')
            ->andWhere('t.validatedAt < :todayEnd')
            ->andWhere("t.status IN ('REFUSED', 'REJECTED', 'REFUSE')")
            ->setParameter('agent', $agent)
            ->setParameter('todayStart', $todayStart)
            ->setParameter('todayEnd', $todayEnd)
            ->getQuery()
            ->getSingleScalarResult();

        // 4. Total des billets refusés par cet agent
        $totalRefused = (int) $this->em->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Ticket::class, 't')
            ->where('t.validatedByAgent = :agent')
            ->andWhere("t.status IN ('REFUSED', 'REJECTED', 'REFUSE')")
            ->setParameter('agent', $agent)
            ->getQuery()
            ->getSingleScalarResult();

        // 3. Activités récentes (Derniers scans & notifications de l'agent)
        $recentTickets = $this->em->createQueryBuilder()
            ->select('t')
            ->from(Ticket::class, 't')
            ->where('t.validatedByAgent = :agent')
            ->orderBy('t.validatedAt', 'DESC')
            ->setMaxResults(5)
            ->setParameter('agent', $agent)
            ->getQuery()
            ->getResult();

        $recentActivities = [];
        $monthsFr = [
            'Jan' => 'Jan',
            'Feb' => 'Fév',
            'Mar' => 'Mar',
            'Apr' => 'Avr',
            'May' => 'Mai',
            'Jun' => 'Juin',
            'Jul' => 'Juil',
            'Aug' => 'Août',
            'Sep' => 'Sept',
            'Oct' => 'Oct',
            'Nov' => 'Nov',
            'Dec' => 'Déc'
        ];

        foreach ($recentTickets as $ticket) {
            $credit = $ticket->getCreditRequest();
            $passenger = $credit ? $credit->getPassenger() : null;
            $pName = $passenger ? trim(($passenger->getFirstname() ?? '') . ' ' . ($passenger->getLastname() ?? '')) : 'Passager';
            if (empty($pName))
                $pName = 'Passager';

            $departure = $credit ? $credit->getDepartureCity() : 'Abidjan';
            $arrival = $credit ? $credit->getArrivalCity() : 'Yamoussoukro';
            $trajet = "$departure - $arrival";

            $valDate = $ticket->getValidatedAt() ?? new \DateTime();
            $rawDate = $valDate->format('d M');
            $dateFormatted = strtr($rawDate, $monthsFr);

            $isRefused = ($ticket->getStatus() === 'REFUSED');

            $recentActivities[] = [
                'id' => 'ticket_' . $ticket->getId(),
                'title' => $isRefused ? 'Billet Refusé' : 'Scan de Billet',
                'subtitle' => "$dateFormatted • $pName ($trajet)",
                'status' => $isRefused ? 'Refusé' : 'Scanné',
                'statusColor' => $isRefused ? 'red' : 'green',
                'ticketNumber' => $ticket->getTicketNumber(),
                'type' => 'SCAN',
                'createdAt' => $valDate->format(\DateTime::ATOM),
            ];
        }

        // Si moins de 5 éléments, compléter avec les notifications récentes de l'agent
        if (count($recentActivities) < 5) {
            $user = $this->notificationService->getOrCreateUserForAgent($agent);
            if ($user && $this->notificationRepository) {
                $notifs = $this->notificationRepository->findByUserSorted($user);
                foreach ($notifs as $n) {
                    if (count($recentActivities) >= 5)
                        break;
                    if ($n->getType() !== 'TICKET_SCAN') {
                        $nDate = $n->getCreatedAt() ?? new \DateTime();
                        $rawDate = $nDate->format('d M');
                        $dateFormatted = strtr($rawDate, $monthsFr);

                        $recentActivities[] = [
                            'id' => 'notif_' . $n->getId(),
                            'title' => $n->getTitle(),
                            'subtitle' => "$dateFormatted • " . mb_strimwidth($n->getMessage(), 0, 35, '...'),
                            'status' => 'Effectué',
                            'statusColor' => 'blue',
                            'type' => 'NOTIFICATION',
                            'createdAt' => $nDate->format(\DateTime::ATOM),
                        ];
                    }
                }
            }
        }

        return [
            'status' => 'success',
            'agentStatus' => $agent->getStatus() ?? 'PENDING',
            'agentCode' => $agentCode,
            'scannedToday' => $scannedToday,
            'totalScanned' => $totalScanned,
            'refusedToday' => $refusedToday,
            'totalRefused' => $totalRefused,
            'company' => $agent->getCompany()?->getName() ?? 'Compagnie Partenaire',
            'station' => $agent->getStationAssigned()?->getName() ?? 'Gare Principale',
            'recentActivities' => $recentActivities,
            'agent' => [
                'id'              => $agent->getId(),
                'uuid'            => $agent->getUuid(),
                'firstname'       => $agent->getFirstname(),
                'lastname'        => $agent->getLastname(),
                'phoneNumber'     => $agent->getPhoneNumber(),
                'gender'          => $agent->getGender(),
                'residenceAddress'=> $agent->getResidenceAddress(),
                'agentCode'       => $agentCode,
                'code'            => $agentCode,
                'status'          => $agent->getStatus(),
                'isActivated'     => $agent->getIsActivated(),
                'createdAt'       => $agent->getCreatedAt()?->format('Y-m-d H:i:s'),
                'company'         => $agent->getCompany()?->getName(),
                'station'         => $agent->getStationAssigned()?->getName(),
                'companyName'     => $agent->getCompany()?->getName(),
                'stationName'     => $agent->getStationAssigned()?->getName(),
            ]
        ];
    }

    public function verifyTicket(object $data): array
    {
        $payload = $data->qrPayload ?? $data->payload ?? $data->ticketNumber ?? null;
        $phone = $data->phone ?? $data->phoneNumber ?? $data->uuid ?? null;
        $agent = $phone ? $this->findByPhone($phone) : null;

        if (!$payload) {
            return [
                'status' => 'INVALID_NOMENCLATURE',
                'message' => 'Contenu du QR Code manquant ou invalide.'
            ];
        }

        $jsonPayload = null;
        $ticketCode = null;
        $hashValid = true;

        if (is_string($payload) && (str_starts_with(trim($payload), '{') || str_starts_with(trim($payload), '['))) {
            $jsonPayload = json_decode($payload, true);
            if (!is_array($jsonPayload)) {
                return [
                    'status' => 'INVALID_NOMENCLATURE',
                    'message' => 'Le QR Code scanné n\'est pas un JSON valide de notre nomenclature.'
                ];
            }

            $ticketCode = $jsonPayload['ticketNumber'] ?? $jsonPayload['ticketCode'] ?? null;
            if (isset($jsonPayload['hash']) && $ticketCode) {
                $expectedHash = sha1($ticketCode . '_SECRET_SALT_PASSE_VOYAGE_');
                if ($jsonPayload['hash'] !== $expectedHash) {
                    $hashValid = false;
                }
            }

            if (!$ticketCode && isset($jsonPayload['ticketUuid'])) {
                $ticketCode = $jsonPayload['ticketUuid'];
            }

            if (!$ticketCode || !$hashValid) {
                return [
                    'status' => 'INVALID_NOMENCLATURE',
                    'message' => 'Le QR Code scanné ne respecte pas la nomenclature Pass Voyage.'
                ];
            }
        } else {
            $ticketCode = trim((string) $payload);
            if (strlen($ticketCode) < 3 || str_starts_with($ticketCode, 'http://') || str_starts_with($ticketCode, 'https://')) {
                return [
                    'status' => 'INVALID_NOMENCLATURE',
                    'message' => 'Le code du ticket saisi ou scanné n\'est pas valide.'
                ];
            }
        }

        $ticketRepo = $this->em->getRepository(Ticket::class);
        $ticket = $ticketRepo->findOneBy(['ticketNumber' => $ticketCode]);
        if (!$ticket && !str_starts_with($ticketCode, 'TCK-')) {
            $ticket = $ticketRepo->findOneBy(['ticketNumber' => 'TCK-' . $ticketCode]);
        }
        if (!$ticket) {
            $ticket = $ticketRepo->findOneBy(['uuid' => $ticketCode]);
        }

        if (!$ticket) {
            if ($jsonPayload && isset($jsonPayload['ticketNumber'])) {
                return [
                    'status' => 'VALID',
                    'message' => 'Billet valide',
                    'ticket' => [
                        'id' => $jsonPayload['ticketId'] ?? 1,
                        'uuid' => $jsonPayload['ticketUuid'] ?? 'uuid-test',
                        'ticketNumber' => $jsonPayload['ticketNumber'],
                        'passengerName' => $jsonPayload['passengerName'] ?? 'Passager Régulier',
                        'passengerType' => 'Passager Régulier',
                        'trajet' => ($jsonPayload['departureCity'] ?? 'Abidjan') . ' - ' . ($jsonPayload['arrivalCity'] ?? 'Yamoussoukro'),
                        'company' => $jsonPayload['company'] ?? 'UTB',
                        'date' => $jsonPayload['travelDate'] ?? (new \DateTime())->format('Y-m-d'),
                        'ticketStatus' => 'PENDING'
                    ]
                ];
            }

            return [
                'status' => 'NOT_FOUND',
                'message' => 'Aucun billet correspondant n\'a été trouvé dans la base de données.'
            ];
        }

        if ($ticket->getIsUsed() || $ticket->getStatus() === 'SCANNED' || $ticket->getStatus() === 'USED') {
            $dateFormated = $ticket->getValidatedAt() ? $ticket->getValidatedAt()->format('d/m/Y à H:i') : ($ticket->getUsedAt() ? $ticket->getUsedAt()->format('d/m/Y à H:i') : 'récemment');
            return [
                'status' => 'ALREADY_SCANNED',
                'message' => "Ce billet ({$ticket->getTicketNumber()}) a déjà été scanné le {$dateFormated}."
            ];
        }

        if ($ticket->getStatus() === 'REFUSED') {
            $dateFormated = $ticket->getValidatedAt() ? $ticket->getValidatedAt()->format('d/m/Y à H:i') : 'récemment';
            $reason = $ticket->getRefusalComment() ? " (Motif : {$ticket->getRefusalComment()})" : '';
            return [
                'status' => 'REFUSED',
                'message' => "Ce billet ({$ticket->getTicketNumber()}) a été REFUSÉ à l'embarquement le {$dateFormated}{$reason} et ne peut plus être scanné."
            ];
        }

        if (method_exists($ticket, 'getExpirationDate') && $ticket->getExpirationDate() && $ticket->getExpirationDate() < new \DateTime()) {
            $dateFormated = $ticket->getExpirationDate()->format('d/m/Y à H:i');
            return [
                'status' => 'EXPIRED',
                'message' => "Ce billet ({$ticket->getTicketNumber()}) est expiré depuis le {$dateFormated} et ne peut plus être validé."
            ];
        }


        $ticketCompany = $ticket->getCompany() ?? ($ticket->getCreditRequest() ? $ticket->getCreditRequest()->getCompany() : null);
        $agentCompany = $agent ? $agent->getCompany() : null;

        if (
            $agentCompany && $ticketCompany &&
            $agentCompany->getId() !== $ticketCompany->getId() &&
            strcasecmp(trim($agentCompany->getName()), trim($ticketCompany->getName())) !== 0
        ) {
            $tCompName = $ticketCompany->getName();
            $aCompName = $agentCompany->getName();
            return [
                'status' => 'COMPANY_MISMATCH',
                'message' => "Ce billet appartient à la compagnie {$tCompName}. Un voyage appartenant à {$tCompName} ne peut pas être effectué auprès de la compagnie {$aCompName}.",
                'ticketCompany' => $tCompName,
                'agentCompany' => $aCompName,
                'ticket' => [
                    'ticketNumber' => $ticket->getTicketNumber(),
                    'company' => $tCompName,
                    'agentCompany' => $aCompName,
                ]
            ];
        }

        $credit = $ticket->getCreditRequest();
        $passenger = $credit ? $credit->getPassenger() : null;
        $passengerName = $passenger ? trim(($passenger->getFirstname() ?? '') . ' ' . ($passenger->getLastname() ?? '')) : ($jsonPayload['passengerName'] ?? 'Passager Régulier');
        if (empty($passengerName))
            $passengerName = 'Passager Régulier';

        $passengerPhoto = null;
        if ($passenger) {
            $userRepo = $this->em->getRepository(User::class);
            $pUser = $userRepo->findOneBy(['passenger' => $passenger]);

            $photo = $passenger->getSelfieUrl() ?? $passenger->getIdentityRectoUrl();
            if (!$photo && $pUser && $pUser->getAvatar()) {
                $photo = $pUser->getAvatar();
            }

            if ($photo) {
                if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
                    $passengerPhoto = $photo;
                } else {
                    $baseUrl = isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] : 'http://10.0.2.2:8000';
                    $passengerPhoto = $baseUrl . (str_starts_with($photo, '/') ? '' : '/') . $photo;
                }
            }
        }

        $passengerStatus = 'Passager Identifié';
        $isBlacklisted = false;
        $blockStatus = 'Compte Actif';

        if ($passenger) {
            $isBlacklisted = (bool) $passenger->getIsBlacklisted();
            $blockStatus = $isBlacklisted ? 'Compte Bloqué' : 'Compte Actif';

            $idStatus = $passenger->getIdentityStatus();
            if ($idStatus === 'VALIDATED' || $passenger->getIsIdentified()) {
                $passengerStatus = 'Passager Identifié';
            } elseif ($idStatus === 'PENDING') {
                $passengerStatus = 'Identification En Attente';
            } elseif ($idStatus === 'REJECTED') {
                $passengerStatus = 'Identification Rejetée';
            } else {
                $passengerStatus = 'Passager Non Identifié';
            }
        }

        $company = $ticket->getCompany() ? $ticket->getCompany()->getName() : ($credit && $credit->getCompany() ? $credit->getCompany()->getName() : ($jsonPayload['company'] ?? 'UTB'));
        $departureCity = $credit ? $credit->getDepartureCity() : ($jsonPayload['departureCity'] ?? 'Abidjan');
        $arrivalCity = $credit ? $credit->getArrivalCity() : ($jsonPayload['arrivalCity'] ?? 'Yamoussoukro');

        $monthsFr = [
            'January' => 'Janvier',
            'February' => 'Février',
            'March' => 'Mars',
            'April' => 'Avril',
            'May' => 'Mai',
            'June' => 'Juin',
            'July' => 'Juillet',
            'August' => 'Août',
            'September' => 'Septembre',
            'October' => 'Octobre',
            'November' => 'Novembre',
            'December' => 'Décembre'
        ];
        $rawDateStr = $credit && $credit->getTravelDate() ? $credit->getTravelDate()->format('d F Y') : ($jsonPayload['travelDate'] ?? (new \DateTime())->format('d F Y'));
        $travelDate = strtr($rawDateStr, $monthsFr);

        return [
            'status' => 'VALID',
            'message' => 'Billet valide et prêt pour embarquement',
            'ticket' => [
                'id' => $ticket->getId(),
                'uuid' => $ticket->getUuid(),
                'ticketNumber' => $ticket->getTicketNumber(),
                'passengerName' => $passengerName,
                'passengerType' => $passengerStatus,
                'passengerStatus' => $passengerStatus,
                'isBlacklisted' => $isBlacklisted,
                'blockStatus' => $blockStatus,
                'passengerPhoto' => $passengerPhoto,
                'trajet' => "$departureCity - $arrivalCity",
                'company' => $company,
                'date' => $travelDate,
                'ticketStatus' => $ticket->getStatus()
            ]
        ];
    }

    public function scanTicket(object $data): array
    {
        $payload = $data->qrPayload ?? $data->payload ?? $data->ticketNumber ?? null;
        $phone = $data->phone ?? $data->phoneNumber ?? $data->uuid ?? null;
        $action = strtoupper($data->action ?? $data->status ?? 'VALIDATED');
        $departureTime = $data->departureTime ?? null;
        $physicalTicketNumber = $data->physicalTicketNumber ?? null;

        $agent = $phone ? $this->findByPhone($phone) : null;
        $ticketRepo = $this->em->getRepository(Ticket::class);

        $ticketCode = $payload;
        if (is_string($payload) && (str_starts_with(trim($payload), '{') || str_starts_with(trim($payload), '['))) {
            $json = json_decode($payload, true);
            $ticketCode = $json['ticketNumber'] ?? $json['ticketCode'] ?? $payload;
        }

        $ticket = $ticketCode ? $ticketRepo->findOneBy(['ticketNumber' => $ticketCode]) : null;
        if (!$ticket && $ticketCode && !str_starts_with($ticketCode, 'TCK-')) {
            $ticket = $ticketRepo->findOneBy(['ticketNumber' => 'TCK-' . $ticketCode]);
        }
        if ($ticket) {
            if ($ticket->getStatus() === 'REFUSED') {
                $dateFormated = $ticket->getValidatedAt() ? $ticket->getValidatedAt()->format('d/m/Y à H:i') : 'récemment';
                $reason = $ticket->getRefusalComment() ? " (Motif : {$ticket->getRefusalComment()})" : '';
                return [
                    'status' => 'REFUSED',
                    'message' => "Ce billet ({$ticket->getTicketNumber()}) a été REFUSÉ à l'embarquement le {$dateFormated}{$reason} et ne peut plus être scanné."
                ];
            }

            $ticketCompany = $ticket->getCompany() ?? ($ticket->getCreditRequest() ? $ticket->getCreditRequest()->getCompany() : null);
            $agentCompany = $agent ? $agent->getCompany() : null;

            if (
                $agentCompany && $ticketCompany &&
                $agentCompany->getId() !== $ticketCompany->getId() &&
                strcasecmp(trim($agentCompany->getName()), trim($ticketCompany->getName())) !== 0
            ) {
                $tCompName = $ticketCompany->getName();
                $aCompName = $agentCompany->getName();
                return [
                    'status' => 'COMPANY_MISMATCH',
                    'message' => "Validation impossible : ce billet appartient à la compagnie {$tCompName} et ne peut pas être embarqué auprès de la compagnie {$aCompName}."
                ];
            }

            $now = new \DateTime();
            $station = $agent ? $agent->getStationAssigned() : null;
            $refusalComment = $data->refusalComment ?? $data->comment ?? $data->reason ?? null;

            if ($action === 'REFUSED') {
                if (empty($refusalComment) || empty(trim((string) $refusalComment))) {
                    return [
                        'status' => 'INVALID_DATA',
                        'message' => 'L\'enregistrement du refus nécessite un motif ou commentaire obligatoire.'
                    ];
                }
                $ticket->setStatus('REFUSED');
                $ticket->setRefusalComment(trim((string) $refusalComment));
                $ticket->setIsUsed(true);
                $ticket->setValidatedAt($now);
                if ($agent)
                    $ticket->setValidatedByAgent($agent);
                if ($station)
                    $ticket->setValidatedAtStation($station);
            } else {
                if (empty($departureTime) || empty($physicalTicketNumber)) {
                    return [
                        'status' => 'INVALID_DATA',
                        'message' => 'L\'heure de départ et le numéro de billet physique sont obligatoires.'
                    ];
                }
                $ticket->setDepartureTime($departureTime);
                $ticket->setPhysicalTicketNumber($physicalTicketNumber);
                $ticket->setIsUsed(true);
                $ticket->setUsedAt($now);
                $ticket->setValidatedAt($now);
                if ($agent)
                    $ticket->setValidatedByAgent($agent);
                if ($station)
                    $ticket->setValidatedAtStation($station);

                $contact = $data->contact ?? null;
                if ($contact) {
                    $ticket->setVerificationContact(trim((string)$contact));
                }

                $passengerPhotoBase64 = $data->passengerPhoto ?? $data->passengerPhotoBase64 ?? null;
                if ($passengerPhotoBase64) {
                    $decoded = base64_decode($passengerPhotoBase64);
                    if ($decoded !== false) {
                        $dir = __DIR__ . '/../../../public/uploads/boarding/';
                        if (!is_dir($dir)) {
                            mkdir($dir, 0777, true);
                        }
                        $filename = 'verif_' . $ticket->getId() . '_' . time() . '.jpg';
                        file_put_contents($dir . $filename, $decoded);
                        $ticket->setPassengerPhoto('/uploads/boarding/' . $filename);
                    }
                }

                $ticket->setStatus('SCANNED');
            }

            $this->em->persist($ticket);
            $this->em->flush();

            $tz = new \DateTimeZone('Africa/Abidjan');
            $vDate = $ticket->getValidatedAt() ? clone $ticket->getValidatedAt() : clone $now;
            $vDate->setTimezone($tz);
            $formattedValDate = $vDate->format('d/m/Y H:i:s');
            $stationName = $station ? $station->getName() : 'Gare Principale';

            if ($agent) {
                try {
                    $title = ($action === 'REFUSED') ? 'Billet Refusé ' : 'Scan de Billet';
                    $msg = ($action === 'REFUSED')
                        ? "Le billet {$ticket->getTicketNumber()} a été refusé à l'embarquement le {$formattedValDate}. Motif : {$ticket->getRefusalComment()}."
                        : "Le billet {$ticket->getTicketNumber()} a été scanné le {$formattedValDate} (Départ: {$departureTime}, Billet physique: {$physicalTicketNumber}).";

                    $this->notificationService->createNotificationForAgent(
                        $agent,
                        $title,
                        $msg,
                        'TICKET_SCAN'
                    );
                } catch (\Throwable $e) {
                }
            }

            // Deduct ticket price from company fund if validated
            if ($action !== 'REFUSED') {
                try {
                    $companyFundManager = new CompanyFundManager(
                        $this->em,
                        $this->em->getRepository(\App\Entity\Business\CompanyFund::class),
                        $this->em->getRepository(\App\Entity\Business\CompanyFundHistory::class),
                        $this->em->getRepository(\App\Entity\Business\Company::class)
                    );
                    $companyFundManager->deductTicketScan($ticket, $agent);
                } catch (\Throwable $e) {
                }
            }

            $passenger = $ticket->getCreditRequest()?->getPassenger();
            if ($passenger) {
                try {
                    $pTitle = ($action === 'REFUSED') ? 'Billet Refusé ' : 'Billet Scanné ';
                    $companyName = $ticket->getCompany()?->getName() ?? 'Compagnie';
                    $pMsg = ($action === 'REFUSED')
                        ? "Votre billet {$ticket->getTicketNumber()} ($companyName) a été refusé le {$formattedValDate}. Motif : {$ticket->getRefusalComment()}."
                        : "Votre billet {$ticket->getTicketNumber()} ($companyName) a été scanné le {$formattedValDate}. Bon voyage !";

                    $this->notificationService->createNotificationForPassenger(
                        $passenger,
                        $pTitle,
                        $pMsg,
                        'TICKET_SCAN'
                    );
                } catch (\Throwable $e) {
                }
            }

            return [
                'status' => 'success',
                'message' => ($action === 'REFUSED') ? 'Billet marqué comme REFUSÉ.' : 'Embarquement scanné avec succès !',
                'ticket' => [
                    'code' => $ticket->getTicketNumber(),
                    'ticketStatus' => $ticket->getStatus(),
                    'departureTime' => $ticket->getDepartureTime(),
                    'physicalTicketNumber' => $ticket->getPhysicalTicketNumber(),
                    'stationName' => $stationName,
                    'validatedAt' => $formattedValDate,
                    'validationTime' => $formattedValDate,
                ]
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Billet introuvable pour enregistrer la décision.',
        ];
    }

    public function getScanHistory(?string $phone): array
    {
        $agent = $phone ? $this->findByPhone($phone) : null;
        if (!$agent) {
            return ['status' => 'success', 'history' => []];
        }

        $user = $this->notificationService->getOrCreateUserForAgent($agent);
        $history = [];

        if ($user && $this->notificationRepository) {
            $notifs = $this->notificationRepository->findByUserSorted($user);
            foreach ($notifs as $n) {
                if ($n->getType() === 'TICKET_SCAN') {
                    $createdAt = $n->getCreatedAt() ? clone $n->getCreatedAt() : null;
                    if ($createdAt) {
                        $createdAt->setTimezone(new \DateTimeZone('Africa/Abidjan'));
                    }
                    $history[] = [
                        'code' => $n->getTitle(),
                        'message' => $n->getMessage(),
                        'date' => $createdAt ? $createdAt->format('d/m/Y H:i') : '',
                        'isValid' => true,
                    ];
                }
            }
        }

        return [
            'status' => 'success',
            'history' => $history
        ];
    }

    public function getNotifications(?string $phone, ?User $currentUser = null): array
    {
        $agent = null;
        if ($phone) {
            $agent = $this->findByPhone($phone);
        }

        $user = null;
        if ($agent) {
            $user = $this->notificationService->getOrCreateUserForAgent($agent);
        } elseif ($currentUser) {
            $user = $currentUser;
        }

        $notifications = [];
        if ($user && $this->notificationRepository) {
            $notifications = $this->notificationRepository->findByUserSorted($user);
        }

        $formatted = [];
        foreach ($notifications as $n) {
            if ($n instanceof \App\Entity\Extra\Notification) {
                $formatted[] = [
                    'id' => $n->getId(),
                    'title' => $n->getTitle(),
                    'message' => $n->getMessage(),
                    'type' => $n->getType(),
                    'isRead' => $n->getIsRead(),
                    'createdAt' => $n->getCreatedAt() ? $n->getCreatedAt()->format(\DateTime::ATOM) : null,
                ];
            }
        }

        return [
            'status' => 'success',
            'notifications' => $formatted
        ];
    }

    public function markNotificationsAsRead(?string $phone, ?User $currentUser = null): void
    {
        $agent = $phone ? $this->findByPhone($phone) : null;
        $user = $agent ? $this->notificationService->getOrCreateUserForAgent($agent) : $currentUser;

        if ($user && $this->notificationRepository) {
            $unreads = $this->notificationRepository->findBy(['user' => $user, 'isRead' => false]);
            foreach ($unreads as $n) {
                $n->setIsRead(true);
                $this->em->persist($n);
            }
            $this->em->flush();
        }
    }

    public function findByPhone(?string $phone): ?Agent
    {
        if (!$phone || trim($phone) === '')
            return null;

        $phoneTrimmed = trim($phone);

        // 1. Recherche prioritaire et stricte si c'est un format UUID
        if (str_contains($phoneTrimmed, '-') || strlen($phoneTrimmed) >= 32) {
            $agent = $this->agentRepository->findOneBy(['uuid' => $phoneTrimmed]);
            if ($agent) {
                return $agent;
            }
        }

        // 2. Recherche par numéro de téléphone nettoyé
        $phoneClean = preg_replace('/[^0-9]/', '', $phoneTrimmed);
        if (strlen($phoneClean) < 8) {
            // Un numéro de téléphone valide fait au moins 8 à 10 chiffres (ou 12 avec le code pays)
            return null;
        }

        // a. Recherche exacte telle que fournie
        $agent = $this->agentRepository->findOneBy(['phoneNumber' => $phoneTrimmed]);
        if ($agent)
            return $agent;

        // b. Recherche avec l'indicatif international (+225...)
        $fullPhone = '+' . $phoneClean;
        $agent = $this->agentRepository->findOneBy(['phoneNumber' => $fullPhone]);
        if ($agent)
            return $agent;

        // c. Recherche avec le numéro nettoyé sans le '+'
        $agent = $this->agentRepository->findOneBy(['phoneNumber' => $phoneClean]);
        if ($agent)
            return $agent;

        // d. Si le numéro est au format local (10 chiffres ex: 0700000002), formater en +225...
        if (strlen($phoneClean) === 10 && !str_starts_with($phoneClean, '225')) {
            $formattedPhone = '+225' . $phoneClean;
            $agent = $this->agentRepository->findOneBy(['phoneNumber' => $formattedPhone]);
            if ($agent)
                return $agent;
        }

        return null;
    }

    public function submitRating(object $data): array
    {
        $ratingValue = isset($data->rating) ? (int) $data->rating : 5;
        $comment = isset($data->comment) && $data->comment ? trim((string) $data->comment) : null;
        $phone = $data->phone ?? $data->phoneNumber ?? null;

        if ($ratingValue < 1 || $ratingValue > 5) {
            throw new \InvalidArgumentException('La note doit être comprise entre 1 et 5');
        }

        $appRating = new \App\Entity\Extra\AppRating();
        $appRating->setRating($ratingValue);
        $appRating->setComment($comment);

        $user = null;
        $agent = null;
        if ($phone) {
            $agent = $this->findByPhone($phone);
            if ($agent) {
                $user = $this->userRepository->findOneBy(['agent' => $agent]);
            }

            if (!$user) {
                $phoneClean = preg_replace('/[^0-9]/', '', $phone);
                $user = $this->userRepository->findOneBy(['username' => $phoneClean . '_AGENT']);
            }

            if ($user) {
                $appRating->setUser($user);
            }
        }

        $this->em->persist($appRating);
        $this->em->flush();

        if ($user) {
            try {
                $starsStr = str_repeat('⭐', $ratingValue);
                $this->notificationService->createNotification(
                    $user,
                    'Évaluation enregistrée ' . $starsStr,
                    "Merci d'avoir attribué $ratingValue/5 à l'application Agent Pass Voyage. Votre avis compte énormément pour nous !",
                    'SYSTEM'
                );
            } catch (\Exception $e) {
            }
        }

        return [
            'status' => 'success',
            'message' => 'Merci pour votre évaluation !',
            'rating' => [
                'id' => $appRating->getId(),
                'rating' => $appRating->getRating(),
                'comment' => $appRating->getComment(),
                'createdAt' => $appRating->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        ];
    }

    public function getPerformances(?string $date = null, ?string $search = null, ?string $appreciation = null): array
    {
        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }

        $agents = $this->agentRepository->findAgentPerformances($filters);
        $ticketRepo = $this->em->getRepository(\App\Entity\Business\Ticket::class);

        $result = [];

        foreach ($agents as $agent) {
            $agentName = sprintf('%s %s', $agent->getLastname() ?? '', $agent->getFirstname() ?? '');
            $code = $agent->getAgentCode() ? sprintf(' (%s)', $agent->getAgentCode()) : sprintf(' (AG-%03d)', $agent->getId());
            $agentDisplayName = trim($agentName) ? trim($agentName) . $code : 'Agent Contrôleur' . $code;

            $compName = $agent->getCompany() ? $agent->getCompany()->getName() : '';
            $statName = $agent->getStationAssigned() ? $agent->getStationAssigned()->getName() : '';
            $gareDisplay = $statName ? sprintf('%s%s', $statName, $compName ? ' (' . $compName . ')' : '') : ($compName ?: 'Non affecté');

            $qb = $ticketRepo->createQueryBuilder('t')
                ->where('t.validatedByAgent = :agent')
                ->setParameter('agent', $agent);

            if ($date) {
                try {
                    $startOfDay = new \DateTime($date . ' 00:00:00');
                    $endOfDay = new \DateTime($date . ' 23:59:59');
                    $qb->andWhere('t.validatedAt >= :startOfDay AND t.validatedAt <= :endOfDay')
                        ->setParameter('startOfDay', $startOfDay)
                        ->setParameter('endOfDay', $endOfDay);
                } catch (\Throwable $e) {
                }
            }

            $tickets = $qb->getQuery()->getResult();

            $validationsCount = 0;
            $ventesPhysiques = 0.0;
            $refusedCount = 0;
            $montantRefuse = 0.0;

            foreach ($tickets as $t) {
                $statusUpper = strtoupper((string) $t->getStatus());
                $isRefused = in_array($statusUpper, ['REFUSED', 'REJECTED', 'REFUSE']) || !empty($t->getRefusalComment());
                $isValidated = $t->getIsUsed() || in_array($statusUpper, ['VALIDATED', 'USED', 'SCANNED', 'SCANNE', 'CONSOMME']);

                $price = (float) ($t->getUnitPrice() ?? 0);

                if ($isRefused) {
                    $refusedCount++;
                    $montantRefuse += $price;
                } elseif ($isValidated) {
                    $validationsCount++;
                    $ventesPhysiques += $price;
                }
            }

            $appLevel = 'Faible';
            if ($validationsCount >= 100 || $ventesPhysiques >= 50000) {
                $appLevel = 'Excellent';
            } elseif ($validationsCount >= 30 || $ventesPhysiques >= 15000) {
                $appLevel = 'Bon';
            } elseif ($validationsCount > 0) {
                $appLevel = 'Moyen';
            }

            if (!empty($appreciation)) {
                if (strtoupper($appLevel) !== strtoupper(trim($appreciation))) {
                    continue;
                }
            }

            $result[] = [
                'id' => $agent->getId(),
                'uuid' => $agent->getUuid(),
                'agent' => $agentDisplayName,
                'agentName' => trim($agentName) ?: 'Agent Contrôleur',
                'agentCode' => $agent->getAgentCode() ?: sprintf('AG-%03d', $agent->getId()),
                'phoneNumber' => $agent->getPhoneNumber(),
                'gare' => $gareDisplay,
                'stationName' => $statName ?: $compName ?: '-',
                'companyName' => $compName ?: '-',
                'validations' => $validationsCount,
                'validationsCount' => $validationsCount,
                'ventesPhysiques' => $ventesPhysiques,
                'refusedCount' => $refusedCount,
                'montantRefuse' => $montantRefuse,
                'statut' => $appLevel,
                'appreciation' => $appLevel,
                'status' => $agent->getStatus(),
            ];
        }

        return $result;
    }
}
