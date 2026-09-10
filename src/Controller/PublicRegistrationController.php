<?php

namespace App\Controller;

use App\Entity\Extra\UserOtp;
use App\Entity\Admin\User;
use App\Manager\Business\PassengerManager;
use App\Repository\Admin\UserRepository;
use App\Repository\Extra\UserOtpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @Route(path="/api/public/passenger")
 */
#[Route(path: '/api/public/passenger')]
class PublicRegistrationController extends AbstractController
{
    private $passengerManager;
    private $em;
    private $userOtpRepository;
    private $userRepository;
    private $jwtManager;
    private $passwordHasher;

    public function __construct(
        PassengerManager $passengerManager,
        EntityManagerInterface $em,
        UserOtpRepository $userOtpRepository,
        UserRepository $userRepository,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->passengerManager = $passengerManager;
        $this->em = $em;
        $this->userOtpRepository = $userOtpRepository;
        $this->userRepository = $userRepository;
        $this->jwtManager = $jwtManager;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @Route("/send-otp", name="send_otp_public", methods={"POST"},
     * options={"description"="Envoyer un code OTP au numéro de téléphone passager"})
     */
    #[Route('/send-otp', name: 'send_otp_public', methods: ['POST'], options: ['description' => 'Envoyer un code OTP au numéro de téléphone passager'])]
    public function sendOtp(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        $phone = $data->phone ?? $data->phoneNumber ?? null;

        if (!$phone) {
            return $this->json(['message' => 'Le numéro de téléphone est obligatoire'], 400);
        }

        $phoneClean = preg_replace('/[^0-9]/', '', $phone);
        $targetDigits = strlen($phoneClean) >= 9 ? substr($phoneClean, -9) : $phoneClean;

        $passengerRepo = $this->em->getRepository(\App\Entity\Business\Passenger::class);
        $allPassengers = $passengerRepo->findAll();
        foreach ($allPassengers as $p) {
            $pClean = preg_replace('/[^0-9]/', '', $p->getPhoneNumber() ?? '');
            $pDigits = strlen($pClean) >= 9 ? substr($pClean, -9) : $pClean;
            if ($pDigits && $pDigits === $targetDigits) {
                return $this->json([
                    'status' => 'account_exists',
                    'accountExists' => true,
                    'message' => 'Un compte passager existe déjà avec ce numéro de téléphone (' . $phone . ').'
                ], 200);
            }
        }

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

        return $this->json([
            'status' => 'success',
            'message' => 'Code OTP généré avec succès',
            'otpCode' => $otpCode,
            'expiresAt' => $expiresAt->format(\DateTime::ATOM)
        ], 200);
    }

    /**
     * @Route("/verify-otp", name="verify_otp_public", methods={"POST"},
     * options={"description"="Vérifier le code OTP saisi par le passager"})
     */
    #[Route('/verify-otp', name: 'verify_otp_public', methods: ['POST'], options: ['description' => 'Vérifier le code OTP saisi par le passager'])]
    public function verifyOtp(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        $phone = $data->phone ?? $data->phoneNumber ?? null;
        $otpCode = $data->otpCode ?? $data->code ?? null;

        if (!$phone || !$otpCode) {
            return $this->json(['message' => 'Téléphone et code OTP requis'], 400);
        }

        $userOtp = $this->userOtpRepository->findOneBy([
            'phone' => $phone,
            'code' => (string) $otpCode,
            'isUsed' => false
        ]);

        if (!$userOtp) {
            return $this->json(['message' => 'Code OTP invalide ou déja utilisé'], 400);
        }

        if ($userOtp->getExpiresAt() < new \DateTime()) {
            return $this->json(['message' => 'Le code OTP a expiré'], 400);
        }

        $userOtp->setIsUsed(true);
        $this->em->persist($userOtp);
        $this->em->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Code OTP vérifié avec succès'
        ], 200);
    }

    /**
     * @Route("/register", name="register_passenger_public", methods={"POST"},
     * options={"description"="Création publique autonome du compte passager"})
     */
    #[Route('/register', name: 'register_passenger_public', methods: ['POST'], options: ['description' => 'Création publique autonome du compte passager'])]
    public function registerPassenger(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $passenger = $this->passengerManager->registerPublic($data);

            $token = null;
            $phoneClean = preg_replace('/[^0-9]/', '', $passenger->getPhoneNumber() ?? '');
            $user = $this->userRepository->findOneBy(['username' => $phoneClean, 'type' => User::TYPE['PASSENGER']]);
            if (!$user) {
                $user = $this->userRepository->findOneBy(['username' => $phoneClean . '_PASSENGER', 'type' => User::TYPE['PASSENGER']]);
            }
            if (!$user && $passenger->getPhoneNumber()) {
                $user = $this->userRepository->findOneBy(['telephone' => $passenger->getPhoneNumber(), 'type' => User::TYPE['PASSENGER']]);
            }

            if ($user) {
                $payload = [
                    'username' => $user->getUserIdentifier(),
                    'exp' => time() + 315360000,
                ];
                $token = $this->jwtManager->createFromPayload($user, $payload);
            }

            return $this->json([
                'status' => 'success',
                'message' => 'Compte passager créé avec succès',
                'token' => $token,
                'passenger' => $passenger
            ], 201, [], ['groups' => ['passenger:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de l\'inscription : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/login", name="login_passenger_public", methods={"POST"},
     * options={"description"="Connexion publique mobile d'un passager (Téléphone + PIN)"})
     */
    #[Route('/login', name: 'login_passenger_public', methods: ['POST'], options: ['description' => 'Connexion publique mobile d\'un passager (Téléphone + PIN)'])]
    public function loginPassenger(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        $phone = $data->phone ?? $data->phoneNumber ?? null;
        $pinCode = $data->pinCode ?? $data->password ?? null;

        if (!$phone || !$pinCode) {
            return $this->json(['message' => 'Téléphone et code PIN requis'], 400);
        }

        $phoneClean = preg_replace('/[^0-9]/', '', $phone);
        $user = $this->userRepository->findOneBy(['username' => $phoneClean, 'type' => User::TYPE['PASSENGER']]);
        if (!$user) {
            $user = $this->userRepository->findOneBy(['username' => $phoneClean . '_PASSENGER', 'type' => User::TYPE['PASSENGER']]);
        }
        if (!$user) {
            $user = $this->userRepository->findOneBy(['telephone' => $phoneClean, 'type' => User::TYPE['PASSENGER']]);
        }

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $pinCode)) {
            return $this->json(['message' => 'Numéro de téléphone ou code PIN incorrect'], 401);
        }

        if ($user->getType() === User::TYPE['PASSENGER'] || $user->getPassenger()) {
            if (!$user->isEnabled()) {
                $user->setIsEnabled(true);
                $this->em->persist($user);
                $this->em->flush();
            }
        } elseif (method_exists($user, 'isEnabled') && !$user->isEnabled()) {
            return $this->json(['message' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'], 403);
        }

        $fcmToken = $data->fcmToken ?? $data->fcm_token ?? null;
        if ($fcmToken) {
            $user->setFcmToken($fcmToken);
            $this->em->persist($user);
            if ($user->getPassenger()) {
                $user->getPassenger()->setFcmToken($fcmToken);
                $this->em->persist($user->getPassenger());
            }
            $this->em->flush();
        }

        $payload = [
            'username' => $user->getUserIdentifier(),
            'exp' => time() + 315360000,
        ];
        $token = $this->jwtManager->createFromPayload($user, $payload);
        $passenger = $user->getPassenger();

        return $this->json([
            'status' => 'success',
            'token' => $token,
            'passenger' => $passenger
        ], 200, [], ['groups' => ['passenger:read']]);
    }
}
