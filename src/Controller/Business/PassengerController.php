<?php

namespace App\Controller\Business;

use App\Entity\Business\Passenger;
use App\Manager\Business\PassengerManager;
use App\Repository\Business\PassengerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PassengerController extends AbstractController
{
    private $passengerRepository;
    private $passengerManager;

    public function __construct(
        PassengerRepository $passengerRepository,
        PassengerManager $passengerManager
    ) {
        $this->passengerRepository = $passengerRepository;
        $this->passengerManager = $passengerManager;
    }

    /**
     * @Route("/api/private/passenger", name="index_passenger", methods={"GET"},
     * options={"description"="Liste des passagers", "permission"="PASSENGER:LIST"})
     */
    #[Route('/api/private/passenger', name: 'index_passenger', methods: ['GET'], options: ['description' => 'Liste des passagers', 'permission' => 'PASSENGER:LIST'])]
    #[Route('/api/private/passenger/', name: 'index_passenger_slash', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $result = $this->passengerManager->getFormattedPassengerList($request->query->all());
        return $this->json($result);
    }

    /**
     * @Route("/api/private/passenger/new", name="new_passenger", methods={"POST"},
     * options={"description"="Ajouter un passager", "permission"="PASSENGER:NEW"})
     */
    #[Route('/api/private/passenger/new', name: 'new_passenger', methods: ['POST'], options: ['description' => 'Ajouter un passager', 'permission' => 'PASSENGER:NEW'])]
    public function new(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $passenger = $this->passengerManager->create($data);
            return $this->json($passenger, 201, [], ['groups' => ['passenger:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de la création : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/api/private/passenger/{uuid}/show", name="show_passenger", methods={"GET"},
     * options={"description"="Détails d'un passager", "permission"="PASSENGER:SHOW"})
     */
    #[Route('/api/private/passenger/{uuid}/show', name: 'show_passenger', methods: ['GET'], options: ['description' => 'Détails d\'un passager', 'permission' => 'PASSENGER:SHOW'])]
    public function show(string $uuid): JsonResponse
    {
        $passenger = $this->passengerRepository->findOneBy(['uuid' => $uuid]);
        if (!$passenger && is_numeric($uuid)) {
            $passenger = $this->passengerRepository->find((int)$uuid);
        }
        if (!$passenger) {
            return $this->json(['message' => 'Passager introuvable'], 404);
        }

        return $this->json($passenger, 200, [], ['groups' => ['passenger:read']]);
    }

    /**
     * @Route("/api/private/passenger/{uuid}/edit", name="edit_passenger", methods={"PUT", "POST"},
     * options={"description"="Modifier un passager", "permission"="PASSENGER:EDIT"})
     */
    #[Route('/api/private/passenger/{uuid}/edit', name: 'edit_passenger', methods: ['PUT', 'POST'], options: ['description' => 'Modifier un passager', 'permission' => 'PASSENGER:EDIT'])]
    public function edit(Request $request, string $uuid): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $passenger = $this->passengerManager->update($uuid, $data);
            return $this->json($passenger, 200, [], ['groups' => ['passenger:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de la modification : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/api/private/passenger/{uuid}/verify-kyc", name="verify_kyc_passenger", methods={"POST", "PUT"},
     * options={"description"="Valider la vérification KYC du passager", "permission"="PASSENGER:VERIFY_KYC"})
     */
    #[Route('/api/private/passenger/{uuid}/verify-kyc', name: 'verify_kyc_passenger', methods: ['POST', 'PUT'], options: ['description' => 'Valider la vérification KYC du passager', 'permission' => 'PASSENGER:VERIFY_KYC'])]
    public function verifyKyc(Request $request, string $uuid): JsonResponse
    {
        $passenger = $this->passengerRepository->findOneBy(['uuid' => $uuid]);
        if (!$passenger && is_numeric($uuid)) {
            $passenger = $this->passengerRepository->find((int)$uuid);
        }
        if (!$passenger) {
            return $this->json(['message' => 'Passager introuvable'], 404);
        }

        $data = json_decode($request->getContent());
        $status = $data->status ?? 'VERIFIED';
        $reason = $data->reason ?? null;

        try {
            $passenger = $this->passengerManager->verifyKyc($passenger, $status, $reason);
            return $this->json($passenger, 200, [], ['groups' => ['passenger:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de la validation KYC : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/api/private/passenger/{uuid}/delete", name="delete_passenger", methods={"DELETE", "POST"},
     * options={"description"="Supprimer un passager", "permission"="PASSENGER:DELETE"})
     */
    #[Route('/api/private/passenger/{uuid}/delete', name: 'delete_passenger', methods: ['DELETE', 'POST'], options: ['description' => 'Supprimer un passager', 'permission' => 'PASSENGER:DELETE'])]
    public function delete(string $uuid): JsonResponse
    {
        $passenger = $this->passengerRepository->findOneBy(['uuid' => $uuid]);
        if (!$passenger && is_numeric($uuid)) {
            $passenger = $this->passengerRepository->find((int)$uuid);
        }
        if (!$passenger) {
            return $this->json(['message' => 'Passager introuvable'], 404);
        }

        try {
            $this->passengerManager->delete($passenger);
            return $this->json(['message' => 'Passager supprimé avec succès'], 200);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de la suppression : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/api/private/passenger/identity", name="submit_passenger_identity_private", methods={"POST"},
     * options={"description"="Soumettre les pièces d'identité du passager", "permission"="PASSENGER:IDENTITY"})
     */
    #[Route('/api/private/passenger/identity', name: 'submit_passenger_identity_private', methods: ['POST'], options: ['description' => 'Soumettre les pièces d\'identité du passager', 'permission' => 'PASSENGER:IDENTITY'])]
    public function submitIdentity(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        $user = $this->getUser();
        $phone = $data->phone ?? $data->phoneNumber ?? null;

        if (!$phone && $user && method_exists($user, 'getPhoneNumber')) {
            $phone = $user->getPhoneNumber();
        }
        if (!$phone && $user && method_exists($user, 'getPhone')) {
            $phone = $user->getPhone();
        }

        $passenger = $this->passengerManager->findByPhone($phone);
        if (!$passenger && $user instanceof Passenger) {
            $passenger = $user;
        }

        if (!$passenger) {
            return $this->json(['message' => 'Numéro de téléphone ou utilisateur authentifié requis'], 400);
        }

        try {
            $passenger = $this->passengerManager->submitIdentityDocuments($passenger, $data);
            return $this->json([
                'status' => 'success',
                'message' => 'Documents d\'identité transmis avec succès.',
                'passenger' => $passenger
            ], 200, [], ['groups' => ['passenger:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de l\'enregistrement des pièces : ' . $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/api/private/passenger/dashboard", name="get_passenger_dashboard", methods={"GET", "POST"})
     */
    #[Route('/api/private/passenger/dashboard', name: 'get_passenger_dashboard', methods: ['GET', 'POST'])]
    #[Route('/api/public/passenger/dashboard', name: 'get_passenger_dashboard_public', methods: ['GET', 'POST'])]
    public function getDashboardData(Request $request): JsonResponse
    {
        $phone = $request->query->get('phone');
        if (!$phone) {
            $data = json_decode($request->getContent());
            $phone = $data->phone ?? $data->phoneNumber ?? null;
        }

        try {
            $result = $this->passengerManager->getDashboardData($phone);
            return $this->json($result, 200, [], ['groups' => ['passenger:read']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de la récupération du tableau de bord : ' . $e->getMessage()], 500);
        }
    }

    /**
     * @Route("/api/private/passenger/notifications", name="get_passenger_notifications", methods={"GET", "POST"})
     */
    #[Route('/api/private/passenger/notifications', name: 'get_passenger_notifications', methods: ['GET', 'POST'])]
    public function getNotifications(Request $request): JsonResponse
    {
        $phone = $request->query->get('phone');
        if (!$phone) {
            $data = json_decode($request->getContent());
            $phone = $data->phone ?? $data->phoneNumber ?? null;
        }

        try {
            $result = $this->passengerManager->getNotifications($phone);
            return $this->json($result, 200, [], ['groups' => ['notification:read']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de la récupération des notifications : ' . $e->getMessage()], 500);
        }
    }

    /**
     * @Route("/api/private/passenger/notifications/mark-read", name="mark_notifications_read", methods={"POST"})
     */
    #[Route('/api/private/passenger/notifications/mark-read', name: 'mark_notifications_read', methods: ['POST'])]
    public function markNotificationsRead(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        $phone = $data->phone ?? $data->phoneNumber ?? null;

        try {
            $this->passengerManager->markNotificationsAsRead($phone);
            return $this->json(['status' => 'success', 'message' => 'Notifications marquées comme lues'], 200);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors du marquage des notifications : ' . $e->getMessage()], 500);
        }
    }

    /**
     * @Route("/api/private/passenger/rating", name="submit_passenger_rating", methods={"POST"})
     */
    #[Route('/api/private/passenger/rating', name: 'submit_passenger_rating', methods: ['POST'])]
    public function submitRating(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        try {
            $result = $this->passengerManager->submitRating($data);
            return $this->json($result, 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de l\'enregistrement de la note : ' . $e->getMessage()], 500);
        }
    }

    /**
     * @Route("/api/private/passenger/fcm-token", name="update_passenger_fcm_token", methods={"POST"})
     */
    #[Route('/api/private/passenger/fcm-token', name: 'update_passenger_fcm_token', methods: ['POST'])]
    public function updateFcmToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        $fcmToken = $data->fcmToken ?? $data->fcm_token ?? null;
        $phone = $data->phone ?? $data->phoneNumber ?? null;

        try {
            $this->passengerManager->updateFcmToken($fcmToken, $phone, $this->getUser());
            return $this->json(['status' => 'success', 'message' => 'Token FCM enregistré avec succès'], 200);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de l\'enregistrement du token FCM : ' . $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint protégé pour récupérer la configuration des compagnies, trajets, villes et tarifs
     * @Route("/api/private/passenger/referentiel/config", name="passenger_referentiel_config", methods={"GET", "POST"})
     * @Route("/api/private/passenger/demande-credit-config", name="passenger_demande_credit_config", methods={"GET", "POST"})
     */
    #[Route('/api/private/passenger/referentiel/config', name: 'passenger_referentiel_config', methods: ['GET', 'POST'])]
    #[Route('/api/private/passenger/demande-credit-config', name: 'passenger_demande_credit_config', methods: ['GET', 'POST'])]
    public function getDemandeCreditConfig(): JsonResponse
    {
        $config = $this->passengerManager->getDemandeCreditConfig();
        $response = (new \App\Helpers\JsonHelper($config, null, 'success', 200, []))->serialize();
        return $this->json($response, 200);
    }

    #[Route('/api/private/passenger/reimburse', name: 'reimburse_passenger_private', methods: ['POST'])]
    #[Route('/api/public/passenger/reimburse', name: 'reimburse_passenger_public', methods: ['POST'])]
    #[Route('/api/private/passenger/credit/reimburse', name: 'reimburse_passenger_credit_private', methods: ['POST'])]
    #[Route('/api/public/passenger/credit/reimburse', name: 'reimburse_passenger_credit_public', methods: ['POST'])]
    public function submitReimbursement(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        $phone = $request->query->get('phone') ?? ($data->phone ?? $data->phoneNumber ?? null);
        $amount = (int)($data->amount ?? 0);
        $paymentMethod = $data->paymentMethod ?? 'MOBILE_MONEY';
        $creditUuid = $data->creditUuid ?? null;

        try {
            $result = $this->passengerManager->processReimbursement(
                $amount,
                $paymentMethod,
                $phone,
                $creditUuid,
                $this->getUser()
            );
            return $this->json($result, 200);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    #[Route('/api/private/passenger/reimbursements', name: 'get_passenger_reimbursements_private', methods: ['GET', 'POST'])]
    #[Route('/api/public/passenger/reimbursements', name: 'get_passenger_reimbursements_public', methods: ['GET', 'POST'])]
    public function getReimbursements(Request $request): JsonResponse
    {
        $phone = $request->query->get('phone');
        if (!$phone) {
            $data = json_decode($request->getContent());
            $phone = $data->phone ?? $data->phoneNumber ?? null;
        }

        $result = $this->passengerManager->getReimbursements($phone, $this->getUser());
        return $this->json($result, 200);
    }

    #[Route('/api/private/passenger/contacts/sync', name: 'sync_passenger_contacts_private', methods: ['POST'])]
    #[Route('/api/public/passenger/contacts/sync', name: 'sync_passenger_contacts_public', methods: ['POST'])]
    public function syncContacts(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $phone = $data['phone'] ?? $data['phoneNumber'] ?? null;
        $contacts = $data['contacts'] ?? [];

        $result = $this->passengerManager->syncPassengerContacts($phone, $contacts, $this->getUser());
        return $this->json($result, 200);
    }

    /**
     * @Route("/api/private/passenger/{uuid}/toggle-blacklist", name="toggle_blacklist_passenger", methods={"POST", "PUT"},
     * options={"description"="Bloquer ou Débloquer un passager", "permission"="PASSENGER:EDIT"})
     */
    #[Route('/api/private/passenger/{uuid}/toggle-blacklist', name: 'toggle_blacklist_passenger', methods: ['POST', 'PUT'], options: ['description' => 'Bloquer ou Débloquer un passager', 'permission' => 'PASSENGER:EDIT'])]
    public function toggleBlacklist(Request $request, string $uuid): JsonResponse
    {
        $passenger = $this->passengerRepository->findOneBy(['uuid' => $uuid]);
        if (!$passenger && is_numeric($uuid)) {
            $passenger = $this->passengerRepository->find((int)$uuid);
        }
        if (!$passenger) {
            return $this->json(['message' => 'Passager introuvable'], 404);
        }

        $data = json_decode($request->getContent());
        $status = isset($data->isBlacklisted) ? (bool)$data->isBlacklisted : null;
        $reason = $data->reason ?? null;

        try {
            $passenger = $this->passengerManager->toggleBlacklist($passenger, $status, $reason);
            $msg = $passenger->getIsBlacklisted() ? 'Passager bloqué avec succès' : 'Passager réactivé avec succès';
            return $this->json([
                'status' => 'success',
                'message' => $msg,
                'passenger' => $passenger
            ], 200, [], ['groups' => ['passenger:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de l\'opération : ' . $e->getMessage()], 400);
        }
    }

    #[Route('/api/private/passenger/reminder/send', name: 'send_passenger_reminder', methods: ['POST'])]
    public function sendPassengerReminder(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        $passengerId = $data->passengerId ?? $data->id ?? $data->passengerUuid ?? null;

        try {
            $result = $this->passengerManager->sendReminder($passengerId);
            return $this->json($result, 200);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => 'Erreur lors de l\'envoi de la relance : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/private/passenger/reminder/send-all', name: 'send_all_passengers_reminders', methods: ['POST'])]
    public function sendAllPassengerReminders(): JsonResponse
    {
        try {
            $result = $this->passengerManager->sendAllReminders();
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => 'Erreur lors de la relance globale : ' . $e->getMessage()], 500);
        }
    }
}
