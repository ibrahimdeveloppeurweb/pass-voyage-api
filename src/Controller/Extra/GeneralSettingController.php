<?php

namespace App\Controller\Extra;

use App\Helpers\JsonHelper;
use App\Exception\ExceptionApi;
use App\Manager\Extra\GeneralSettingManager;
use App\Manager\Extra\NotificationSettingManager;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/private/extra/settings")
 */
#[Route(path: '/api/private/extra/settings')]
class GeneralSettingController extends AbstractController
{
    private $generalSettingManager;
    private $notificationSettingManager;

    public function __construct(GeneralSettingManager $generalSettingManager, NotificationSettingManager $notificationSettingManager)
    {
        $this->generalSettingManager = $generalSettingManager;
        $this->notificationSettingManager = $notificationSettingManager;
    }

    /**
     * @Route("/general", name="get_general_settings", methods={"GET"},
     * options={"description"="Récupérer la configuration générale", "permission"="SETTING:READ"})
     */
    #[Route('/general', name: 'get_general_settings', methods: ['GET'], options: ['description' => 'Récupérer la configuration générale', 'permission' => 'SETTING:READ'])]
    public function getSettings(): JsonResponse
    {
        try {
            $setting = $this->generalSettingManager->getSettings();
            $response = (new JsonHelper($setting, null, 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['setting']]);
        }
        catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, $e->getCode(), [], ['groups' => ['setting']]);
        }
    }

    /**
     * @Route("/general/update", name="update_general_settings", methods={"POST", "PUT"},
     * options={"description"="Mettre à jour la configuration générale", "permission"="SETTING:EDIT"})
     */
    #[Route('/general/update', name: 'update_general_settings', methods: ['POST', 'PUT'], options: ['description' => 'Mettre à jour la configuration générale', 'permission' => 'SETTING:EDIT'])]
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), false);
            $user = $this->getUser();
            $setting = $this->generalSettingManager->updateOrCreate($data, $user);
            $response = (new JsonHelper($setting, 'Paramètres enregistrés avec succès', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['setting']]);
        }
        catch (\Exception $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', 500, []))->serialize();
            return $this->json($response, 500, [], ['groups' => ['setting']]);
        }
    }

    /**
     * @Route("/general/history", name="get_general_settings_history", methods={"GET"},
     * options={"description"="Récupérer l'historique des modifications", "permission"="SETTING:READ"})
     */
    #[Route('/general/history', name: 'get_general_settings_history', methods: ['GET'], options: ['description' => 'Récupérer l\'historique des modifications', 'permission' => 'SETTING:READ'])]
    public function getHistory(): JsonResponse
    {
        try {
            $history = $this->generalSettingManager->getHistory();
            $response = (new JsonHelper($history, null, 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['setting_history']]);
        }
        catch (\Exception $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', 500, []))->serialize();
            return $this->json($response, 500, [], ['groups' => ['setting_history']]);
        }
    }

    /**
     * @Route("/notifications", name="get_extra_notification_settings", methods={"GET"}, 
     * options={"description"="Récupérer la configuration des notifications", "permission"="NOTIFICATION:SETTINGS_READ"})
     */
    #[Route('/notifications', name: 'get_extra_notification_settings', methods: ['GET'], options: ['description' => 'Récupérer la configuration des notifications', 'permission' => 'NOTIFICATION:SETTINGS_READ'])]
    public function getNotificationSettings()
    {
        try {
            $setting = $this->notificationSettingManager->getSettings();
            $response = (new JsonHelper($setting, null, 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['setting']]);
        }
        catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, $e->getCode(), [], ['groups' => ['setting']]);
        }
    }

    /**
     * @Route("/notifications/update", name="update_extra_notification_settings", methods={"POST", "PUT"},
     * options={"description"="Mettre à jour la configuration des notifications", "permission"="NOTIFICATION:SETTINGS_EDIT"})
     */
    #[Route('/notifications/update', name: 'update_extra_notification_settings', methods: ['POST', 'PUT'], options: ['description' => 'Mettre à jour la configuration des notifications', 'permission' => 'NOTIFICATION:SETTINGS_EDIT'])]
    public function updateNotificationSettings(Request $request)
    {
        try {
            $data = json_decode($request->getContent());
            $setting = $this->notificationSettingManager->updateOrCreate($data);
            $response = (new JsonHelper($setting, 'Paramètres enregistrés avec succès', 'success', 200, []))->serialize();
            return $this->json($response, 200, [], ['groups' => ['setting']]);
        }
        catch (ExceptionApi $e) {
            $response = (new JsonHelper(null, $e->getMessage(), 'bad_request', $e->getCode(), $e->getErrors()))->serialize();
            return $this->json($response, $e->getCode(), [], ['groups' => ['setting']]);
        }
    }
}