<?php

namespace App\Service;

use App\Entity\Admin\User;
use App\Entity\Business\Passenger;
use Psr\Log\LoggerInterface;

class FcmPushService
{
    private ?LoggerInterface $logger;
    private string $serviceAccountPath;
    private ?string $cachedAccessToken = null;
    private int $tokenExpiresAt = 0;

    public function __construct(
        ?LoggerInterface $logger = null,
        string $projectDir = ''
    ) {
        $this->logger = $logger;
        $baseDir = $projectDir ?: dirname(__DIR__, 2);
        $this->serviceAccountPath = $baseDir . '/config/firebase/firebase_service_account.json';
    }

    /**
     * Générer ou récupérer un Jeton d'Accès OAuth2 Google valide
     */
    private function getGoogleAccessToken(): ?string
    {
        if ($this->cachedAccessToken && time() < ($this->tokenExpiresAt - 60)) {
            return $this->cachedAccessToken;
        }

        if (!file_exists($this->serviceAccountPath)) {
            if ($this->logger) {
                $this->logger->error("[FCM PUSH] Service Account JSON introuvable: " . $this->serviceAccountPath);
            }
            return null;
        }

        $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);
        if (!$serviceAccount || empty($serviceAccount['private_key']) || empty($serviceAccount['client_email'])) {
            if ($this->logger) {
                $this->logger->error("[FCM PUSH] Service Account JSON invalide");
            }
            return null;
        }

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $payload = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ];

        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));
        $signatureData = $base64UrlHeader . "." . $base64UrlPayload;

        $privateKey = $serviceAccount['private_key'];
        $signature = '';
        if (!openssl_sign($signatureData, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            if ($this->logger) {
                $this->logger->error("[FCM PUSH] Signature JWT échouée");
            }
            return null;
        }

        $jwt = $signatureData . "." . $this->base64UrlEncode($signature);

        // Requête HTTP POST vers Google OAuth2 Token endpoint
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            $this->cachedAccessToken = $data['access_token'];
            $this->tokenExpiresAt = $now + ($data['expires_in'] ?? 3600);
            return $this->cachedAccessToken;
        }

        if ($this->logger) {
            $this->logger->error("[FCM PUSH OAuth2 Failed] " . $response);
        }

        return null;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Envoyer une notification Push FCM via l'API HTTP v1 officielle
     */
    public function sendPushNotification(?string $fcmToken, string $title, string $message, array $data = []): bool
    {
        if (!$fcmToken) {
            return false;
        }

        $accessToken = $this->getGoogleAccessToken();
        if (!$accessToken) {
            return false;
        }

        if (!file_exists($this->serviceAccountPath)) {
            return false;
        }

        $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);
        $projectId = $serviceAccount['project_id'] ?? 'passvoyage-c6c03';

        $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

        $payload = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $message,
                ],
                'data' => array_map('strval', array_merge([
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'title' => $title,
                    'message' => $message,
                ], $data)),
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'channel_id' => 'high_importance_channel',
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'alert' => [
                                'title' => $title,
                                'body' => $message,
                            ],
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($this->logger) {
                $this->logger->info("[FCM PUSH HTTP v1] Envoi à $fcmToken - Code: $httpCode - Response: $result");
            }

            return $httpCode >= 200 && $httpCode < 300;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("[FCM PUSH EXCEPTION] " . $e->getMessage());
            }
            return false;
        }
    }

    public function sendToUser(User $user, string $title, string $message, array $data = []): bool
    {
        $token = $user->getFcmToken();
        if (!$token && $user->getPassenger()) {
            $token = $user->getPassenger()->getFcmToken();
        }
        return $this->sendPushNotification($token, $title, $message, $data);
    }

    public function sendToPassenger(Passenger $passenger, string $title, string $message, array $data = []): bool
    {
        $token = $passenger->getFcmToken();
        return $this->sendPushNotification($token, $title, $message, $data);
    }
}
