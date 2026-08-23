<?php

namespace App\Manager\Business;

use App\Entity\Admin\User;
use App\Entity\Business\Passenger;
use App\Entity\Business\PassengerContact;
use App\Repository\Business\PassengerRepository;
use App\Repository\Admin\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use App\Service\NotificationService;
use App\Repository\Extra\NotificationRepository;

class PassengerManager
{
    private $em;
    private $passengerRepository;
    private $userRepository;
    private $passwordHasher;
    private $notificationService;
    private $notificationRepository;

    public function __construct(
        EntityManagerInterface $em,
        PassengerRepository $passengerRepository,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        NotificationService $notificationService,
        NotificationRepository $notificationRepository
    ) {
        $this->em = $em;
        $this->passengerRepository = $passengerRepository;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->notificationService = $notificationService;
        $this->notificationRepository = $notificationRepository;
    }

    public function create(object $data): Passenger
    {
        $passenger = new Passenger();
        return $this->save($passenger, $data, true);
    }

    public function registerPublic(object $data): Passenger
    {
        return $this->create($data);
    }

    public function update(string $uuid, object $data): Passenger
    {
        $passenger = $this->passengerRepository->findOneBy(['uuid' => $uuid]);
        if (!$passenger && is_numeric($uuid)) {
            $passenger = $this->passengerRepository->find((int) $uuid);
        }
        if (!$passenger) {
            throw new \Exception("Passager non trouvé");
        }

        return $this->save($passenger, $data, false);
    }

    private function save(Passenger $passenger, object $data, bool $isNew = false): Passenger
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
            $passenger->setPhoneNumber($fullPhone);
        }

        if ($isNew && isset($passenger) && $passenger->getPhoneNumber()) {
            $fullPhone = $passenger->getPhoneNumber();
            $phoneClean = preg_replace('/[^0-9]/', '', $fullPhone);
            $targetDigits = strlen($phoneClean) >= 9 ? substr($phoneClean, -9) : $phoneClean;

            $allPassengers = $this->passengerRepository->findAll();
            foreach ($allPassengers as $p) {
                $pClean = preg_replace('/[^0-9]/', '', $p->getPhoneNumber() ?? '');
                $pDigits = strlen($pClean) >= 9 ? substr($pClean, -9) : $pClean;
                if ($pDigits && $pDigits === $targetDigits) {
                    throw new \Exception("Un compte passager existe déjà avec ce numéro de téléphone ($fullPhone).");
                }
            }
        }

        if (isset($data->firstname)) {
            $passenger->setFirstname($data->firstname);
        }
        if (isset($data->lastname)) {
            $passenger->setLastname($data->lastname);
        }
        if (isset($data->countryCode)) {
            $passenger->setCountryCode($data->countryCode);
        } else {
            $passenger->setCountryCode('+225');
        }
        if (isset($data->email)) {
            $passenger->setEmail($data->email);
        }
        if (isset($data->gender)) {
            $passenger->setGender($data->gender);
        }
        if (isset($data->residenceAddress)) {
            $passenger->setResidenceAddress($data->residenceAddress);
        }
        if (isset($data->pinCode)) {
            $passenger->setPinCode($data->pinCode);
        }
        if (isset($data->identityCardNumber)) {
            $passenger->setIdentityCardNumber($data->identityCardNumber);
        }
        if (isset($data->identityType)) {
            $passenger->setIdentityType($data->identityType);
        }
        if (isset($data->identityRectoUrl) || isset($data->rectoImage)) {
            $recto = $data->identityRectoUrl ?? $data->rectoImage;
            $savedRecto = $this->saveBase64Image($recto, 'recto');
            if ($savedRecto)
                $passenger->setIdentityRectoUrl($savedRecto);
        }
        if (isset($data->identityVersoUrl) || isset($data->versoImage)) {
            $verso = $data->identityVersoUrl ?? $data->versoImage;
            $savedVerso = $this->saveBase64Image($verso, 'verso');
            if ($savedVerso)
                $passenger->setIdentityVersoUrl($savedVerso);
        }
        if (isset($data->selfieUrl) || isset($data->selfieImage)) {
            $selfie = $data->selfieUrl ?? $data->selfieImage;
            $savedSelfie = $this->saveBase64Image($selfie, 'selfie');
            if ($savedSelfie)
                $passenger->setSelfieUrl($savedSelfie);
        }

        if (isset($data->fcmToken) || isset($data->fcm_token)) {
            $fcmToken = $data->fcmToken ?? $data->fcm_token;
            if ($fcmToken) {
                $passenger->setFcmToken($fcmToken);
            }
        }

        if (isset($data->identityStatus)) {
            $oldStatus = $passenger->getIdentityStatus();
            $newStatus = strtoupper((string) $data->identityStatus);
            $passenger->setIdentityStatus($newStatus);

            if ($newStatus === 'VERIFIED' || $newStatus === 'VALIDATED') {
                $passenger->setIsIdentified(true);
            } elseif ($newStatus === 'REJECTED') {
                $passenger->setIsIdentified(false);
            }

            if ($oldStatus !== $newStatus) {
                try {
                    if ($newStatus === 'VERIFIED' || $newStatus === 'VALIDATED') {
                        $this->notificationService->createNotificationForPassenger(
                            $passenger,
                            'Identification validée ✅',
                            'Félicitations ! Votre identité et vos pièces ont été vérifiées et approuvées par l\'administrateur.',
                            'KYC_VERIFIED'
                        );
                    } elseif ($newStatus === 'REJECTED') {
                        $this->notificationService->createNotificationForPassenger(
                            $passenger,
                            'Identification refusée ❌',
                            'Votre dossier d\'identification a été rejeté par l\'administrateur. Veuillez soumettre à nouveau vos pièces.',
                            'KYC_REJECTED'
                        );
                    }
                } catch (\Exception $e) {
                }
            }
        } else {
            $hasDocuments = !empty($data->identityRectoUrl) || !empty($data->rectoImage)
                || !empty($data->identityVersoUrl) || !empty($data->versoImage)
                || !empty($data->selfieUrl) || !empty($data->selfieImage);

            if ($hasDocuments && $passenger->getIdentityStatus() !== 'VERIFIED') {
                $passenger->setIdentityStatus('PENDING');
            } elseif (!$passenger->getIdentityStatus()) {
                $passenger->setIdentityStatus('NOT_SUBMITTED');
            }
        }

        $this->em->persist($passenger);

        // Create linked User
        if ($isNew) {
            $phoneClean = preg_replace('/[^0-9]/', '', $passenger->getPhoneNumber());
            $username = $phoneClean;
            $existingUser = $this->userRepository->findOneBy(['username' => $username]);
            if ($existingUser) {
                $username = $phoneClean . '_PASSENGER';
            }

            $user = new User();
            $user->setType(User::TYPE['PASSENGER']);
            $user->setUsername($username);
            $user->setTelephone($passenger->getPhoneNumber());
            $user->setNom($passenger->getLastname());
            $user->setPrenom($passenger->getFirstname());
            $user->setEmail($passenger->getEmail());
            $user->setPassenger($passenger);
            $user->setIsEnabled(true);
            if ($passenger->getFcmToken()) {
                $user->setFcmToken($passenger->getFcmToken());
            }

            $pin = isset($data->pinCode) && $data->pinCode ? $data->pinCode : '1234';
            $hashedPassword = $this->passwordHasher->hashPassword($user, $pin);
            $user->setPassword($hashedPassword);

            $this->em->persist($user);
            $this->em->flush();

            // Créer la notification de bienvenue
            try {
                $this->notificationService->createNotification(
                    $user,
                    'Bienvenue sur Pass Voyage',
                    'Votre compte passager a été créé avec succès. Bienvenue parmi nous !',
                    'WELCOME'
                );
            } catch (\Exception $e) {
                // Ignore notification failure if any
            }
        } else {
            $this->em->flush();
        }

        return $passenger;
    }

    private function saveBase64Image(?string $dataString, string $prefix): ?string
    {
        if (!$dataString || trim($dataString) === '') {
            return null;
        }

        if (str_starts_with($dataString, '/data/user/') || str_starts_with($dataString, 'file://') || str_contains($dataString, '/com.example.')) {
            return null;
        }

        if (str_starts_with($dataString, '/uploads/') || str_starts_with($dataString, 'http://') || str_starts_with($dataString, 'https://')) {
            return $dataString;
        }

        $rawBase64 = $dataString;
        if (str_contains($dataString, 'base64,')) {
            $parts = explode('base64,', $dataString);
            $rawBase64 = $parts[1];
        }

        // Clean spaces, newlines, and carriage returns from base64 string
        $rawBase64 = str_replace([' ', "\r", "\n", "\t"], ['+', '', '', ''], trim($rawBase64));

        $decoded = base64_decode($rawBase64);
        if ($decoded !== false && strlen($decoded) > 50) {
            $uploadDir = __DIR__ . '/../../../public/uploads/passengers';
            if (!file_exists($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $filename = $prefix . '_' . uniqid() . '.jpg';
            $filePath = $uploadDir . '/' . $filename;
            file_put_contents($filePath, $decoded);
            return '/uploads/passengers/' . $filename;
        }

        if (strlen($dataString) > 255) {
            return null;
        }

        return $dataString;
    }

    public function submitIdentityDocuments(Passenger $passenger, object $data): Passenger
    {
        if (isset($data->identityType)) {
            $passenger->setIdentityType($data->identityType);
        }
        if (isset($data->identityCardNumber)) {
            $passenger->setIdentityCardNumber($data->identityCardNumber);
        }

        $recto = $data->rectoImage ?? $data->identityRectoUrl ?? null;
        if ($recto) {
            $savedRecto = $this->saveBase64Image($recto, 'recto');
            if ($savedRecto) {
                $passenger->setIdentityRectoUrl($savedRecto);
            }
        }

        $verso = $data->versoImage ?? $data->identityVersoUrl ?? null;
        if ($verso) {
            $savedVerso = $this->saveBase64Image($verso, 'verso');
            if ($savedVerso) {
                $passenger->setIdentityVersoUrl($savedVerso);
            }
        }

        $selfie = $data->selfieImage ?? $data->selfieUrl ?? null;
        if ($selfie) {
            $savedSelfie = $this->saveBase64Image($selfie, 'selfie');
            if ($savedSelfie) {
                $passenger->setSelfieUrl($savedSelfie);
            }
        }

        $passenger->setIdentityStatus('PENDING');

        try {
            $this->notificationService->createNotificationForPassenger(
                $passenger,
                'Documents transmis',
                'Vos documents d\'identification ont été transmis avec succès et sont en cours d\'examen par l\'administrateur.',
                'KYC_SUBMITTED'
            );
        } catch (\Exception $e) {
        }

        $this->em->persist($passenger);
        $this->em->flush();

        return $passenger;
    }

    public function verifyKyc(Passenger $passenger, string $status, ?string $reason = null): Passenger
    {
        $statusUpper = strtoupper($status);

        if ($statusUpper === 'VERIFIED' || $statusUpper === 'VALIDATED') {
            $passenger->setIdentityStatus('VERIFIED');
            $passenger->setIsIdentified(true);
            $passenger->setProfileType('STANDARD');
            if (!$passenger->getMaxCreditLimit()) {
                $passenger->setMaxCreditLimit(200000);
            }

            try {
                $this->notificationService->createNotificationForPassenger(
                    $passenger,
                    'Identification validée ✅',
                    'Félicitations ! Votre identité et vos pièces ont été vérifiées et approuvées par l\'administrateur.',
                    'KYC_VERIFIED'
                );
            } catch (\Exception $e) {
            }
        } elseif ($statusUpper === 'REJECTED' || $statusUpper === 'REFUSED') {
            $passenger->setIdentityStatus('REJECTED');
            $passenger->setIsIdentified(false);

            try {
                $msg = 'Votre dossier d\'identification a été rejeté par l\'administrateur.';
                if ($reason)
                    $msg .= ' Raison : ' . $reason;
                $this->notificationService->createNotificationForPassenger(
                    $passenger,
                    'Identification refusée ❌',
                    $msg,
                    'KYC_REJECTED'
                );
            } catch (\Exception $e) {
            }
        } else {
            $passenger->setIdentityStatus($statusUpper);
        }

        $this->em->persist($passenger);
        $this->em->flush();

        return $passenger;
    }

    /**
     * Valide ou rejette le compte d'un passager
     */
    public function validateAccount(Passenger $passenger, string $status = 'VERIFIED', ?string $reason = null): Passenger
    {
        return $this->verifyKyc($passenger, $status, $reason);
    }

    /**
     * Valide ou rejette le compte d'un passager par son UUID
     */
    public function validatePassengerByUuid(string $uuid, string $status = 'VERIFIED', ?string $reason = null): Passenger
    {
        $passenger = $this->passengerRepository->findOneBy(['uuid' => $uuid]);
        if (!$passenger) {
            throw new \Exception("Passager non trouvé avec l'identifiant UUID : $uuid");
        }

        return $this->verifyKyc($passenger, $status, $reason);
    }

    public function delete(Passenger $passenger): Passenger
    {
        $passenger->setDeletedAt(new \DateTime());

        if ($user = $this->userRepository->findOneBy(['passenger' => $passenger])) {
            $this->em->remove($user);
        }

        $this->em->flush();
        return $passenger;
    }

    public function findByPhone(?string $phone): ?Passenger
    {
        if (!$phone) {
            return null;
        }

        $phoneClean = preg_replace('/[^0-9]/', '', $phone);
        if (!$phoneClean) {
            return null;
        }

        $targetDigits = strlen($phoneClean) >= 9 ? substr($phoneClean, -9) : $phoneClean;

        $passenger = $this->passengerRepository->findOneBy(['phoneNumber' => '+' . $phoneClean]);
        if (!$passenger) {
            $passenger = $this->passengerRepository->findOneBy(['phoneNumber' => $phoneClean]);
        }

        if (!$passenger) {
            $allPassengers = $this->passengerRepository->findAll();
            foreach ($allPassengers as $p) {
                $pClean = preg_replace('/[^0-9]/', '', $p->getPhoneNumber() ?? '');
                $pDigits = strlen($pClean) >= 9 ? substr($pClean, -9) : $pClean;
                if ($pDigits && $pDigits === $targetDigits) {
                    return $p;
                }
            }
        }

        return $passenger;
    }

    public function findUserByPhone(?string $phone): ?User
    {
        if (!$phone) {
            return null;
        }

        $phoneClean = preg_replace('/[^0-9]/', '', $phone);
        if (!$phoneClean) {
            return null;
        }

        $targetDigits = strlen($phoneClean) >= 9 ? substr($phoneClean, -9) : $phoneClean;

        $allUsers = $this->userRepository->findAll();
        foreach ($allUsers as $u) {
            $uClean = preg_replace('/[^0-9]/', '', $u->getUsername() ?? $u->getTelephone() ?? '');
            $uDigits = strlen($uClean) >= 9 ? substr($uClean, -9) : $uClean;
            if ($uDigits && $uDigits === $targetDigits) {
                return $u;
            }
        }

        return null;
    }

    private function formatDateFr(?\DateTimeInterface $date): string
    {
        if (!$date) {
            return 'Récemment';
        }

        $monthsFr = [
            1 => 'Janv',
            2 => 'Fév',
            3 => 'Mars',
            4 => 'Avr',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juil',
            8 => 'Août',
            9 => 'Sept',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Déc'
        ];

        $monthNum = (int) $date->format('n');
        $monthStr = $monthsFr[$monthNum] ?? $date->format('M');

        return $date->format('d') . ' ' . $monthStr . ' • ' . $date->format('H:i');
    }

    public function getDashboardData(?string $phone): array
    {
        if (!$phone) {
            throw new \InvalidArgumentException('Numéro de téléphone requis');
        }

        $passenger = $this->findByPhone($phone);
        if (!$passenger) {
            return [
                'status' => 'success',
                'availableCredit' => 0,
                'formattedCredit' => '0',
                'recentActivities' => []
            ];
        }

        // Recalculer la dette réelle à rembourser (somme des restes dus sur demandes validées)
        $creditRepo = $this->em->getRepository(\App\Entity\Business\Credit::class);
        $allUserCredits = $creditRepo->findBy(['passenger' => $passenger]);
        $totalDebtFromValidatedCredits = 0;
        foreach ($allUserCredits as $cr) {
            $st = strtoupper(trim((string) $cr->getStatus()));
            if (in_array($st, ['APPROVED', 'VALIDE'])) {
                $toRepay = method_exists($cr, 'getAmountToRepay') ? $cr->getAmountToRepay() : ($cr->getAmountRequested() ?: $cr->getTotalAmount());
                $rem = max(0, $toRepay - (int) $cr->getRepaidAmount());
                $totalDebtFromValidatedCredits += $rem;
            }
        }

        $totalDebt = $totalDebtFromValidatedCredits;
        $maxLimit = (int) ($passenger->getMaxCreditLimit() ?? 200000);
        $availableCreditLimit = max(0, $maxLimit - $totalDebt);

        $passenger->setTotalDebt($totalDebt);
        $passenger->setAvailableCredit($availableCreditLimit);
        $this->em->persist($passenger);
        $this->em->flush();

        $user = $this->userRepository->findOneBy(['passenger' => $passenger]);

        $recentActivities = [];
        $credits = $creditRepo->findBy(['passenger' => $passenger], ['createdAt' => 'DESC'], 10);
        foreach ($credits as $cr) {
            $statusStr = match ($cr->getStatus()) {
                'APPROVED' => 'Approuvée',
                'PENDING_VALIDATION', 'PENDING' => 'En attente de validation',
                'REJECTED' => 'Rejetée',
                default => $cr->getStatus()
            };
            $title = 'Demande de Crédit';
            if ($cr->getStatus() === 'APPROVED') {
                $title = 'Octroi Crédit Voyage';
            } elseif (in_array(strtoupper((string) $cr->getStatus()), ['PENDING_VALIDATION', 'PENDING', 'EN_ATTENTE', 'IN_PROGRESS', 'SUBMITTED'])) {
                $title = 'Demande de crédit soumise';
            }

            $recentActivities[] = [
                'id' => 'cr_' . $cr->getId(),
                'title' => $title,
                'date' => $this->formatDateFr($cr->getCreatedAt()),
                'message' => 'Statut : ' . $statusStr,
                'iconType' => 'CREDIT',
                'isPositive' => $cr->getStatus() === 'APPROVED',
                'amount' => number_format($cr->getAmountToRepay(), 0, ',', '.') . ' XOF',
                'timestamp' => $cr->getCreatedAt() ? $cr->getCreatedAt()->getTimestamp() : 0,
            ];

            foreach ($cr->getTickets() as $ticket) {
                if ($ticket->getIsUsed() || $ticket->getStatus() === 'SCANNED') {
                    $comp = $ticket->getCompany() ? $ticket->getCompany()->getName() : 'Car';
                    $usedDate = $this->formatDateFr($ticket->getUsedAt() ?? $ticket->getCreatedAt());
                    $recentActivities[] = [
                        'id' => 'tk_' . $ticket->getId(),
                        'title' => 'Billet scanné par l\'agent',
                        'date' => $usedDate,
                        'message' => 'Ticket #' . $ticket->getTicketNumber() . ' (' . $comp . ')',
                        'iconType' => 'BUS',
                        'isPositive' => false,
                        'amount' => '- ' . number_format($ticket->getUnitPrice(), 0, ',', '.') . ' XOF',
                        'timestamp' => $ticket->getUsedAt() ? $ticket->getUsedAt()->getTimestamp() : 0,
                    ];
                }
            }
        }

        if ($user && $this->notificationRepository) {
            $notifications = $this->notificationRepository->findByUserSorted($user);
            foreach ($notifications as $notif) {
                $type = strtoupper((string) $notif->getType());
                if ($type === 'CREDIT_SUBMITTED') {
                    continue;
                }

                if (
                    str_contains($type, 'CREDIT') ||
                    str_contains($type, 'TICKET') ||
                    str_contains($type, 'AGENT') ||
                    str_contains($type, 'SCAN') ||
                    str_contains($type, 'REIMBURSE')
                ) {
                    $isPositive = !str_contains($type, 'SCAN') && !str_contains($type, 'REIMBURSE');
                    $iconType = str_contains($type, 'BUS') || str_contains($type, 'SCAN') ? 'BUS' : (str_contains($type, 'REIMBURSE') ? 'PAYMENT' : 'CREDIT');

                    $recentActivities[] = [
                        'id' => 'notif_' . $notif->getId(),
                        'title' => $notif->getTitle(),
                        'date' => $this->formatDateFr($notif->getCreatedAt()),
                        'message' => $notif->getMessage(),
                        'iconType' => $iconType,
                        'isPositive' => $isPositive,
                        'amount' => '',
                        'timestamp' => $notif->getCreatedAt() ? $notif->getCreatedAt()->getTimestamp() : 0,
                    ];
                }
            }
        }

        $paymentRepo = $this->em->getRepository(\App\Entity\Business\Payment::class);
        $payments = $paymentRepo->findBy(['passenger' => $passenger], ['paymentDate' => 'DESC'], 10);
        foreach ($payments as $pm) {
            $txId = (string) $pm->getTransactionId();
            $pmMethod = (string) $pm->getPaymentMethod();

            $isFee = str_starts_with(strtoupper($txId), 'TX-FEE-')
                || str_contains(strtoupper($txId), 'FEE')
                || str_contains(strtoupper($pmMethod), 'SERVICE')
                || str_contains(strtoupper($pmMethod), 'FRAIS');

            $title = $isFee ? 'Paiement Frais de Service' : 'Remboursement Crédit';
            $msg = $isFee
                ? ('Paiement des frais de service via ' . ($pm->getPaymentMethod() ?? 'Mobile Money'))
                : ('Règlement via ' . ($pm->getPaymentMethod() ?? 'Mobile Money'));

            $recentActivities[] = [
                'id' => 'pm_' . $pm->getId(),
                'title' => $title,
                'date' => $this->formatDateFr($pm->getPaymentDate()),
                'message' => $msg,
                'iconType' => 'PAYMENT',
                'isPositive' => true,
                'amount' => '- ' . number_format($pm->getAmount(), 0, ',', '.') . ' XOF',
                'timestamp' => $pm->getPaymentDate() ? $pm->getPaymentDate()->getTimestamp() : 0,
            ];
        }

        usort($recentActivities, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        $recentActivities = array_slice($recentActivities, 0, 6);

        return [
            'status' => 'success',
            'totalDebt' => $totalDebt,
            'availableCredit' => $totalDebt,
            'creditLimit' => $availableCreditLimit,
            'maxCreditLimit' => $maxLimit,
            'formattedCredit' => number_format($totalDebt, 0, ',', '.'),
            'formattedCreditLimit' => number_format($availableCreditLimit, 0, ',', '.'),
            'identityStatus' => $passenger->getIdentityStatus(),
            'isIdentified' => $passenger->getIsIdentified() ?? ($passenger->getIdentityStatus() === 'VERIFIED'),
            'isBlacklisted' => $passenger->getIsBlacklisted() ?? false,
            'isBlocked' => $passenger->getIsBlacklisted() ?? false,
            'passenger' => $passenger,
            'recentActivities' => $recentActivities,
        ];
    }

    public function getNotifications(?string $phone): array
    {
        if (!$phone) {
            throw new \InvalidArgumentException('Numéro de téléphone requis');
        }

        $passenger = $this->findByPhone($phone);
        if (!$passenger) {
            return ['notifications' => []];
        }

        $user = $this->notificationService->getOrCreateUserForPassenger($passenger);
        $notifications = $this->notificationRepository ? $this->notificationRepository->findByUserSorted($user) : [];

        if (empty($notifications)) {
            try {
                $this->notificationService->createNotification(
                    $user,
                    'Bienvenue sur Pass Voyage ',
                    'Votre compte passager a été configuré avec succès. Retrouvez ici toutes vos activités.',
                    'WELCOME'
                );

                if ($passenger->getIdentityStatus() === 'PENDING') {
                    $this->notificationService->createNotification(
                        $user,
                        'Pièces transmises ',
                        'Vos documents d\'identification ont été transmis avec succès et sont en cours d\'examen par l\'administrateur.',
                        'KYC_SUBMITTED'
                    );
                } elseif ($passenger->getIdentityStatus() === 'VERIFIED') {
                    $this->notificationService->createNotification(
                        $user,
                        'Identification validée ✅',
                        'Félicitations ! Votre identité et vos pièces ont été vérifiées et approuvées par l\'administrateur.',
                        'KYC_VERIFIED'
                    );
                } elseif ($passenger->getIdentityStatus() === 'REJECTED') {
                    $this->notificationService->createNotification(
                        $user,
                        'Identification refusée ❌',
                        'Votre dossier d\'identification a été rejeté par l\'administrateur. Veuillez soumettre à nouveau vos pièces.',
                        'KYC_REJECTED'
                    );
                }

                if ($this->notificationRepository) {
                    $notifications = $this->notificationRepository->findByUserSorted($user);
                }
            } catch (\Exception $e) {
            }
        }

        return [
            'status' => 'success',
            'notifications' => $notifications
        ];
    }

    public function markNotificationsAsRead(?string $phone): void
    {
        if (!$phone) {
            throw new \InvalidArgumentException('Numéro de téléphone requis');
        }

        $user = $this->findUserByPhone($phone);
        if ($user && $this->notificationRepository) {
            $notifications = $this->notificationRepository->findBy(['user' => $user, 'isRead' => false]);
            foreach ($notifications as $n) {
                $n->setIsRead(true);
                $this->em->persist($n);
            }
            $this->em->flush();
        }
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
        $passenger = null;
        if ($phone) {
            $passenger = $this->findByPhone($phone);
            if ($passenger) {
                $appRating->setPassenger($passenger);
                $user = $this->userRepository->findOneBy(['passenger' => $passenger]);
            }

            if (!$user) {
                $user = $this->findUserByPhone($phone);
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
                    "Merci d'avoir attribué $ratingValue/5 à Pass Voyage. Votre avis compte énormément pour nous !",
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
                'createdAt' => $appRating->getCreatedAt()->format(\DateTime::ATOM),
            ]
        ];
    }

    public function updateFcmToken(string $fcmToken, ?string $phone = null, ?User $currentUser = null): void
    {
        if (!$fcmToken) {
            throw new \InvalidArgumentException('Token FCM requis');
        }

        $user = $currentUser;
        $passenger = null;

        if ($phone) {
            $passenger = $this->findByPhone($phone);
        }

        if ($passenger) {
            $passenger->setFcmToken($fcmToken);
            $this->em->persist($passenger);
            if (!$user) {
                $user = $this->userRepository->findOneBy(['passenger' => $passenger]);
            }
        }

        if ($user) {
            $user->setFcmToken($fcmToken);
            $this->em->persist($user);
        }

        $this->em->flush();
    }

    public function getDemandeCreditConfig(): array
    {
        // 1. Compagnies actives
        $companyRepo = $this->em->getRepository(\App\Entity\Business\Company::class);
        $allCompanies = $companyRepo->findAll();

        if (empty($allCompanies)) {
            $defaultNames = ['UTB', 'MT', 'AVS', 'CTE', 'SBTA'];
            foreach ($defaultNames as $dName) {
                $c = new \App\Entity\Business\Company();
                $c->setName($dName);
                $c->setStatus('Partenaire Actif');
                $c->setIsActive(true);
                $this->em->persist($c);
            }
            $this->em->flush();
            $allCompanies = $companyRepo->findAll();
        }

        $activeCompanies = array_filter($allCompanies, function (\App\Entity\Business\Company $c) {
            return $c->getIsActive() !== false && $c->getStatus() !== 'Inactif';
        });

        if (empty($activeCompanies)) {
            $activeCompanies = $allCompanies;
        }

        $companiesData = array_values(array_map(function (\App\Entity\Business\Company $c) {
            return [
                'id' => $c->getId(),
                'uuid' => $c->getUuid(),
                'name' => $c->getName(),
                'nom' => $c->getName(),
                'status' => $c->getStatus(),
                'logo' => $c->getLogo(),
            ];
        }, $activeCompanies));

        $normalizeCity = function (?string $name): string {
            if (!$name)
                return '';
            $clean = trim($name);
            if ($clean === '' || $clean === '-')
                return '';
            $cleanLower = mb_strtolower($clean, 'UTF-8');
            $cleanTitle = mb_convert_case($cleanLower, MB_CASE_TITLE, 'UTF-8');
            $map = [
                'San Pedro' => 'San-Pédro',
                'San-Pedro' => 'San-Pédro',
                'Bouake' => 'Bouaké',
                'Odienne' => 'Odienné',
                'Yamoussokro' => 'Yamoussoukro',
            ];
            return $map[$cleanTitle] ?? $cleanTitle;
        };

        // 2. Trajets actifs
        $routeRepo = $this->em->getRepository(\App\Entity\Business\Route::class);
        $activeRoutes = $routeRepo->findBy(['isActive' => true], ['id' => 'DESC']);
        if (empty($activeRoutes)) {
            $activeRoutes = $routeRepo->findAll();
        }

        $routesData = array_map(function (\App\Entity\Business\Route $r) use ($normalizeCity) {
            $depName = $normalizeCity($r->getDepartureCity() ? $r->getDepartureCity()->getName() : ($r->getDepartureStation() ? $r->getDepartureStation()->getName() : ''));
            $arrName = $normalizeCity($r->getArrivalCity() ? $r->getArrivalCity()->getName() : ($r->getArrivalStation() ? $r->getArrivalStation()->getName() : ''));

            return [
                'id' => $r->getId(),
                'uuid' => $r->getUuid(),
                'villeDepart' => $depName,
                'departureCity' => $depName,
                'villeArrivee' => $arrName,
                'arrivalCity' => $arrName,
                'distance' => $r->getDistance(),
            ];
        }, $activeRoutes);

        // 3. Villes
        $cityNamesMap = [];
        foreach ($routesData as $r) {
            if (!empty($r['villeDepart'])) {
                $cityNamesMap[$r['villeDepart']] = true;
            }
            if (!empty($r['villeArrivee'])) {
                $cityNamesMap[$r['villeArrivee']] = true;
            }
        }

        $cityRepo = $this->em->getRepository(\App\Entity\Business\City::class);
        $dbCities = $cityRepo->findAll();
        foreach ($dbCities as $c) {
            $norm = $normalizeCity($c->getName());
            if ($norm !== '') {
                $cityNamesMap[$norm] = true;
            }
        }

        $defaultCities = [];
        foreach ($defaultCities as $dCity) {
            $cityNamesMap[$dCity] = true;
        }

        $citiesList = array_keys($cityNamesMap);
        sort($citiesList);

        // 4. Tarifs actifs
        $tariffRepo = $this->em->getRepository(\App\Entity\Business\Tariff::class);
        $activeTariffs = $tariffRepo->findBy(['isActive' => true], ['id' => 'DESC']);
        if (empty($activeTariffs)) {
            $activeTariffs = $tariffRepo->findAll();
        }

        $tariffsData = array_map(function (\App\Entity\Business\Tariff $t) use ($normalizeCity) {
            $route = $t->getRoute();
            $depName = $normalizeCity($route ? ($route->getDepartureCity() ? $route->getDepartureCity()->getName() : ($route->getDepartureStation() ? $route->getDepartureStation()->getName() : '')) : '');
            $arrName = $normalizeCity($route ? ($route->getArrivalCity() ? $route->getArrivalCity()->getName() : ($route->getArrivalStation() ? $route->getArrivalStation()->getName() : '')) : '');
            $companyName = $t->getCompany() ? $t->getCompany()->getName() : 'Toutes';

            return [
                'id' => $t->getId(),
                'uuid' => $t->getUuid(),
                'routeUuid' => $route ? $route->getUuid() : null,
                'companyUuid' => $t->getCompany() ? $t->getCompany()->getUuid() : null,
                'villeDepart' => $depName,
                'departureCity' => $depName,
                'villeArrivee' => $arrName,
                'arrivalCity' => $arrName,
                'compagnie' => $companyName,
                'companyName' => $companyName,
                'price' => $t->getPrice(),
                'prix' => $t->getPrice(),
            ];
        }, $activeTariffs);

        return [
            'companies' => $companiesData,
            'cities' => $citiesList,
            'routes' => $routesData,
            'tariffs' => $tariffsData,
        ];
    }

    public function processReimbursement(int $amount, string $paymentMethod = 'MOBILE_MONEY', ?string $phone = null, ?string $creditUuid = null, ?User $currentUser = null): array
    {
        if ($amount < 100) {
            throw new \InvalidArgumentException('Le montant minimum de remboursement est de 100 F CFA.');
        }

        $passenger = null;
        if ($currentUser && method_exists($currentUser, 'getPassenger') && $currentUser->getPassenger()) {
            $passenger = $currentUser->getPassenger();
        }

        if (!$passenger && $phone) {
            $passenger = $this->findByPhone($phone);
        }

        if (!$passenger) {
            throw new \Exception('Passager introuvable');
        }

        $creditRepo = $this->em->getRepository(\App\Entity\Business\Credit::class);

        // Récupérer toutes les demandes de crédit validées et calculer le reste dû total
        $userCredits = $creditRepo->findBy(['passenger' => $passenger], ['createdAt' => 'ASC']);
        $pendingCredits = [];
        $totalDebtFromValidatedCredits = 0;
        foreach ($userCredits as $cr) {
            $st = strtoupper(trim((string) $cr->getStatus()));
            if (in_array($st, ['APPROVED', 'VALIDE'])) {
                $toRepay = method_exists($cr, 'getAmountToRepay') ? $cr->getAmountToRepay() : ($cr->getAmountRequested() ?: $cr->getTotalAmount());
                $rem = max(0, $toRepay - (int) $cr->getRepaidAmount());
                $totalDebtFromValidatedCredits += $rem;
                if ($cr->getRepaymentStatus() !== 'FULLY_REIMBURSED' && $rem > 0) {
                    $pendingCredits[] = $cr;
                }
            }
        }

        if ($totalDebtFromValidatedCredits <= 0) {
            return [
                'status' => 'error',
                'message' => "Vous n'avez aucun solde de crédit en cours à rembourser."
            ];
        }

        if ($amount > $totalDebtFromValidatedCredits) {
            return [
                'status' => 'error',
                'message' => sprintf(
                    "Le montant du remboursement (%s FCFA) ne peut pas dépasser votre solde dû (%s FCFA).",
                    number_format($amount, 0, ',', ' '),
                    number_format($totalDebtFromValidatedCredits, 0, ',', ' ')
                )
            ];
        }

        $payment = new \App\Entity\Business\Payment();
        $payment->setPassenger($passenger);
        $payment->setAmount($amount);
        $payment->setPaymentMethod($paymentMethod);
        $payment->setTransactionId('TX-REIMB-' . date('YmdHis') . '-' . rand(1000, 9999));
        $payment->setPaymentDate(new \DateTime());
        $payment->setStatus('SUCCESS');

        // Distribuer le montant du paiement sur les demandes de crédit
        $remainingPayment = $amount;
        $primaryCreditRequest = null;

        foreach ($pendingCredits as $cr) {
            if ($remainingPayment <= 0) {
                break;
            }

            if (!$primaryCreditRequest) {
                $primaryCreditRequest = $cr;
            }

            $amountToRepay = method_exists($cr, 'getAmountToRepay') ? $cr->getAmountToRepay() : ($cr->getAmountRequested() ?: $cr->getTotalAmount());
            $alreadyRepaid = (int) $cr->getRepaidAmount();
            $needed = max(0, $amountToRepay - $alreadyRepaid);

            if ($needed <= 0) {
                $cr->setRepaidAmount($amountToRepay);
                $cr->setRepaymentStatus('FULLY_REIMBURSED');
                $this->em->persist($cr);
                continue;
            }

            if ($remainingPayment >= $needed) {
                $cr->setRepaidAmount($amountToRepay);
                $cr->setRepaymentStatus('FULLY_REIMBURSED');
                $remainingPayment -= $needed;
            } else {
                $cr->setRepaidAmount($alreadyRepaid + $remainingPayment);
                $cr->setRepaymentStatus('PARTIALLY_REIMBURSED');
                $remainingPayment = 0;
            }

            $this->em->persist($cr);
        }

        if ($primaryCreditRequest) {
            $payment->setCreditRequest($primaryCreditRequest);
        }

        $this->em->persist($payment);

        // Recalculer le total remboursé depuis la somme des paiements effectifs
        $paymentRepo = $this->em->getRepository(\App\Entity\Business\Payment::class);
        $allPayments = $paymentRepo->findBy(['passenger' => $passenger]);
        $newTotalReimbursed = 0;
        foreach ($allPayments as $p) {
            $newTotalReimbursed += (int) $p->getAmount();
        }
        $passenger->setTotalReimbursed($newTotalReimbursed);

        // Recalculer la dette globale uniquement sur les demandes de crédit VALIDÉES (APPROVED / VALIDE)
        $totalDebtFromValidatedCredits = 0;
        $allUserCredits = $creditRepo->findBy(['passenger' => $passenger]);
        foreach ($allUserCredits as $cr) {
            $st = strtoupper(trim((string) $cr->getStatus()));
            if (in_array($st, ['APPROVED', 'VALIDE'])) {
                $toRepay = method_exists($cr, 'getAmountToRepay') ? $cr->getAmountToRepay() : ($cr->getAmountRequested() ?: $cr->getTotalAmount());
                $rem = max(0, $toRepay - (int) $cr->getRepaidAmount());
                $totalDebtFromValidatedCredits += $rem;
            }
        }

        $newTotalDebt = $totalDebtFromValidatedCredits;
        $maxLimit = (int) ($passenger->getMaxCreditLimit() ?? 200000);
        $newAvailableCredit = max(0, $maxLimit - $newTotalDebt);

        $passenger->setTotalDebt($newTotalDebt);
        $passenger->setAvailableCredit($newAvailableCredit);

        $this->em->persist($passenger);
        $this->em->flush();

        $user = $this->userRepository->findOneBy(['passenger' => $passenger]);
        if ($user && $this->notificationService) {
            try {
                $this->notificationService->createNotification(
                    $user,
                    'Remboursement de crédit effectué ',
                    sprintf(
                        'Votre paiement de %s FCFA par %s a bien été pris en compte. Solde crédit restant : %s FCFA.',
                        number_format($amount, 0, ',', '.'),
                        $paymentMethod,
                        number_format($newTotalDebt, 0, ',', '.')
                    ),
                    'CREDIT_REIMBURSEMENT'
                );
            } catch (\Throwable $e) {
            }
        }

        return [
            'status' => 'success',
            'message' => sprintf('Remboursement de %s FCFA effectué avec succès !', number_format($amount, 0, ',', '.')),
            'availableCredit' => $newTotalDebt,
            'formattedCredit' => number_format($newTotalDebt, 0, ',', '.'),
            'totalDebt' => $newTotalDebt,
            'totalReimbursed' => $newTotalReimbursed,
            'payment' => [
                'id' => $payment->getId(),
                'amount' => $payment->getAmount(),
                'paymentMethod' => $payment->getPaymentMethod(),
                'transactionId' => $payment->getTransactionId(),
                'paymentDate' => $payment->getPaymentDate() ? $payment->getPaymentDate()->format('Y-m-d H:i:s') : null,
            ]
        ];
    }

    public function getReimbursements(?string $phone = null, ?User $currentUser = null): array
    {
        $passenger = null;
        if ($currentUser && method_exists($currentUser, 'getPassenger') && $currentUser->getPassenger()) {
            $passenger = $currentUser->getPassenger();
        }

        if (!$passenger && $phone) {
            $passenger = $this->findByPhone($phone);
        }

        if (!$passenger) {
            return [
                'status' => 'success',
                'totalReimbursed' => 0,
                'totalDebt' => 0,
                'availableCredit' => 0,
                'reimbursements' => []
            ];
        }

        $paymentRepo = $this->em->getRepository(\App\Entity\Business\Payment::class);
        $payments = $paymentRepo->findBy(['passenger' => $passenger], ['paymentDate' => 'DESC']);
        $reimbursementsList = [];
        $calculatedTotalReimbursed = 0;

        foreach ($payments as $pm) {
            $txId = (string) $pm->getTransactionId();
            // Les frais de service (TX-FEE-) ne doivent pas etre comptabilises comme remboursement du credit
            if (str_starts_with($txId, 'TX-FEE-') || str_contains($txId, 'FEE')) {
                continue;
            }

            $amt = (int) $pm->getAmount();
            $calculatedTotalReimbursed += $amt;
            $reimbursementsList[] = [
                'id' => $pm->getId(),
                'amount' => $amt,
                'paymentMethod' => $pm->getPaymentMethod() ?? 'Mobile Money',
                'date' => $this->formatDateFr($pm->getPaymentDate()),
                'status' => $pm->getStatus() ?? 'SUCCESS',
                'transactionId' => $pm->getTransactionId(),
                'timestamp' => $pm->getPaymentDate() ? $pm->getPaymentDate()->getTimestamp() : 0,
            ];
        }

        // Recalculer et synchroniser la dette réelle à rembourser
        $totalDebtFromValidatedCredits = 0;
        $creditRepo = $this->em->getRepository(\App\Entity\Business\Credit::class);
        $allUserCredits = $creditRepo->findBy(['passenger' => $passenger]);
        foreach ($allUserCredits as $cr) {
            $st = strtoupper(trim((string) $cr->getStatus()));
            if (in_array($st, ['APPROVED', 'VALIDE'])) {
                $toRepay = method_exists($cr, 'getAmountToRepay') ? $cr->getAmountToRepay() : ($cr->getAmountRequested() ?: $cr->getTotalAmount());
                $rem = max(0, $toRepay - (int) $cr->getRepaidAmount());
                $totalDebtFromValidatedCredits += $rem;
            }
        }

        $passenger->setTotalReimbursed($calculatedTotalReimbursed);
        $passenger->setTotalDebt($totalDebtFromValidatedCredits);
        $this->em->persist($passenger);
        $this->em->flush();

        return [
            'status' => 'success',
            'totalReimbursed' => $calculatedTotalReimbursed,
            'formattedTotalReimbursed' => number_format($calculatedTotalReimbursed, 0, ',', '.'),
            'totalDebt' => $totalDebtFromValidatedCredits,
            'formattedTotalDebt' => number_format($totalDebtFromValidatedCredits, 0, ',', '.'),
            'availableCredit' => $totalDebtFromValidatedCredits,
            'formattedAvailableCredit' => number_format($totalDebtFromValidatedCredits, 0, ',', '.'),
            'reimbursements' => $reimbursementsList,
        ];
    }

    public function syncPassengerContacts(?string $phone, array $contactsList, ?User $currentUser = null): array
    {
        $passenger = null;
        if ($currentUser && method_exists($currentUser, 'getPassenger') && $currentUser->getPassenger()) {
            $passenger = $currentUser->getPassenger();
        }

        if (!$passenger && $phone) {
            $passenger = $this->findByPhone($phone);
        }

        if (!$passenger) {
            return [
                'status' => 'error',
                'message' => 'Passager non trouvé.',
                'syncedCount' => 0
            ];
        }

        $contactRepo = $this->em->getRepository(PassengerContact::class);
        $savedCount = 0;
        $batchSize = 50;

        foreach ($contactsList as $c) {
            if (is_object($c)) {
                $c = (array) $c;
            }
            $name = trim((string) ($c['name'] ?? $c['displayName'] ?? $c['contactName'] ?? ''));
            $num = trim((string) ($c['phone'] ?? $c['phoneNumber'] ?? $c['number'] ?? ''));

            if (empty($num)) {
                continue;
            }

            $existing = $contactRepo->findOneBy([
                'passenger' => $passenger,
                'phoneNumber' => $num
            ]);

            if (!$existing) {
                $contact = new PassengerContact();
                $contact->setContactName(!empty($name) ? $name : $num);
                $contact->setPhoneNumber($num);
                $passenger->addContact($contact);

                $this->em->persist($contact);
                $savedCount++;

                if (($savedCount % $batchSize) === 0) {
                    $this->em->flush();
                }
            }
        }

        $this->em->flush();

        return [
            'status' => 'success',
            'message' => sprintf('%d contact(s) du répertoire synchronisé(s) avec succès.', $savedCount),
            'syncedCount' => $savedCount
        ];
    }

    public function toggleBlacklist(Passenger $passenger, ?bool $status = null, ?string $reason = null): Passenger
    {
        $newStatus = ($status !== null) ? (bool) $status : !$passenger->getIsBlacklisted();
        $passenger->setIsBlacklisted($newStatus);
        $passenger->setUpdatedAt(new \DateTime());
        $this->em->persist($passenger);
        $this->em->flush();

        return $passenger;
    }

    public function sendReminder($passengerId): array
    {
        if (!$passengerId) {
            throw new \InvalidArgumentException('Passager introuvable');
        }

        $passenger = is_numeric($passengerId)
            ? $this->passengerRepository->find((int) $passengerId)
            : $this->passengerRepository->findOneBy(['uuid' => $passengerId]);

        if (!$passenger) {
            throw new \InvalidArgumentException('Passager débiteur introuvable');
        }

        $debt = number_format((int) $passenger->getTotalDebt(), 0, ',', '.');
        $name = trim(($passenger->getFirstname() ?? '') . ' ' . ($passenger->getLastname() ?? ''));
        if (!$name) {
            $name = $passenger->getPhoneNumber() ?? 'Passager';
        }

        $setting = $this->em->getRepository(\App\Entity\Extra\GeneralSetting::class)->findOneBy([]);
        $delaiDays = $setting ? (int) $setting->getDelaiOptionStandard() : 14;
        $days = $passenger->getDaysOverdue($delaiDays);

        $title = 'Rappel de paiement de crédit voyage';
        $message = sprintf(
            "Cher(e) %s, sauf erreur de notre part, vous avez un solde d'impayé de %s FCFA avec %d jour(s) de retard. Merci de procéder au remboursement pour éviter la suspension de votre compte.",
            $name,
            $debt,
            $days
        );

        $this->notificationService->createNotificationForPassenger($passenger, $title, $message, 'PAYMENT_REMINDER');

        return [
            'status' => 'success',
            'message' => sprintf('Relance de paiement envoyée avec succès à %s !', $name)
        ];
    }

    public function sendAllReminders(): array
    {
        $setting = $this->em->getRepository(\App\Entity\Extra\GeneralSetting::class)->findOneBy([]);
        $delaiDays = $setting ? (int) $setting->getDelaiOptionStandard() : 14;

        $debtors = $this->passengerRepository->findByFilters(['financialStatus' => 'IMPAYE', 'onlyOverdue' => 1], $delaiDays);
        $count = 0;

        foreach ($debtors as $passenger) {
            if ((int) $passenger->getTotalDebt() <= 0 || $passenger->getDaysOverdue($delaiDays) <= 0) {
                continue;
            }

            $debt = number_format((int) $passenger->getTotalDebt(), 0, ',', '.');
            $name = trim(($passenger->getFirstname() ?? '') . ' ' . ($passenger->getLastname() ?? ''));
            if (!$name) {
                $name = $passenger->getPhoneNumber() ?? 'Passager';
            }
            $days = $passenger->getDaysOverdue($delaiDays);

            $title = 'Rappel de paiement de crédit voyage';
            $message = sprintf(
                "Cher(e) %s, sauf erreur de notre part, vous avez un solde d'impayé de %s FCFA avec %d jour(s) de retard. Merci de procéder au remboursement pour éviter la suspension de votre compte.",
                $name,
                $debt,
                $days
            );

            try {
                $this->notificationService->createNotificationForPassenger($passenger, $title, $message, 'PAYMENT_REMINDER');
                $count++;
            } catch (\Exception $e) {
            }
        }

        return [
            'status' => 'success',
            'count' => $count,
            'message' => sprintf('Relances de paiement envoyées avec succès à %d passager(s) débiteur(s) !', $count)
        ];
    }

    public function getFormattedPassengerList(array $queryParams = []): array
    {
        $setting = $this->em->getRepository(\App\Entity\Extra\GeneralSetting::class)->findOneBy([]);
        if (!$setting) {
            $all = $this->em->getRepository(\App\Entity\Extra\GeneralSetting::class)->findAll();
            $setting = count($all) > 0 ? $all[0] : null;
        }
        $delaiDays = $setting ? (int) $setting->getDelaiOptionStandard() : 14;

        if (!empty($queryParams)) {
            $passengers = $this->passengerRepository->findByFilters($queryParams, $delaiDays);
        } else {
            $passengers = $this->passengerRepository->findBy([], ['id' => 'DESC']);
        }

        $result = [];
        foreach ($passengers as $p) {
            $daysOverdue = $p->getDaysOverdue($delaiDays);
            $result[] = [
                'id' => $p->getId(),
                'uuid' => $p->getUuid(),
                'firstname' => $p->getFirstname(),
                'lastname' => $p->getLastname(),
                'phoneNumber' => $p->getPhoneNumber(),
                'countryCode' => $p->getCountryCode(),
                'gender' => $p->getGender(),
                'residenceAddress' => $p->getResidenceAddress(),
                'email' => $p->getEmail(),
                'identityCardNumber' => $p->getIdentityCardNumber(),
                'identityType' => $p->getIdentityType(),
                'identityRectoUrl' => $p->getIdentityRectoUrl(),
                'identityVersoUrl' => $p->getIdentityVersoUrl(),
                'selfieUrl' => $p->getSelfieUrl(),
                'identityStatus' => $p->getIdentityStatus(),
                'creditScore' => $p->getCreditScore(),
                'profileType' => $p->getProfileType(),
                'maxCreditLimit' => $p->getMaxCreditLimit(),
                'availableCredit' => $p->getAvailableCredit(),
                'totalDebt' => $p->getTotalDebt(),
                'totalReimbursed' => $p->getTotalReimbursed(),
                'isIdentified' => $p->getIsIdentified(),
                'isBlacklisted' => $p->getIsBlacklisted(),
                'code' => $p->getCode(),
                'createdAt' => $p->getCreatedAt()?->format('Y-m-d H:i:s'),
                'daysOverdue' => $daysOverdue,
                'isOverdue' => ($daysOverdue > 0),
                'delaiOptionDays' => $delaiDays,
            ];
        }

        return $result;
    }
}
