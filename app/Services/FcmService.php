<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Firebase Cloud Messaging (FCM) HTTP v1 API Service
 *
 * Mengirim push notification ke device supir menggunakan
 * Google OAuth2 access token dari Service Account JSON.
 *
 * Setup:
 *   1. Download Service Account JSON dari Firebase Console
 *      (Project Settings > Service Accounts > Generate new private key)
 *   2. Simpan file JSON ke: storage/app/firebase-service-account.json
 *   3. Tambahkan ke .env:
 *      FIREBASE_PROJECT_ID=aplikasi-supir
 *      FIREBASE_CREDENTIALS=storage/app/firebase-service-account.json
 */
class FcmService
{
    private string $projectId;
    private string $credentialsPath;
    private string $fcmEndpoint;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id', env('FIREBASE_PROJECT_ID', 'aplikasi-supir'));
        $this->credentialsPath = base_path(config('services.firebase.credentials', env('FIREBASE_CREDENTIALS', 'storage/app/firebase-service-account.json')));
        $this->fcmEndpoint = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
    }

    /**
     * Kirim notifikasi order baru ke satu supir.
     */
    public function sendOrderNotification(string $fcmToken, array $order): bool
    {
        $orderId = $order['id'] ?? 0;
        $customerName = $order['customer_name'] ?? '-';
        $zone = $order['zone'] ?? '-';
        $items = $order['items'] ?? '-';

        return $this->send($fcmToken, [
            'title' => "🛵 Order Baru #$orderId",
            'body'  => "Pelanggan: $customerName | Zona: $zone",
        ], [
            'order_id'      => (string) $orderId,
            'customer_name' => $customerName,
            'zone'          => $zone,
            'items'         => $items,
            'type'          => 'new_order',
        ]);
    }

    /**
     * Kirim notifikasi umum ke satu device berdasarkan FCM token.
     *
     * @param string $token  FCM token device tujuan
     * @param array  $notification  ['title' => ..., 'body' => ...]
     * @param array  $data  key-value data payload (semua harus string)
     */
    public function send(string $token, array $notification, array $data = []): bool
    {
        if (empty($token)) {
            Log::warning('[FCM] Token kosong, notifikasi tidak dikirim.');
            return false;
        }

        try {
            $accessToken = $this->getAccessToken();

            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $notification['title'] ?? 'Pools Ice',
                        'body'  => $notification['body']  ?? '',
                    ],
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id'    => 'driver_orders',
                            'sound'         => 'default',
                            'click_action'  => 'FLUTTER_NOTIFICATION_CLICK',
                        ],
                    ],
                    'data' => array_map('strval', $data),
                ],
            ];

            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->post($this->fcmEndpoint, $payload);

            if ($response->successful()) {
                Log::info('[FCM] Notifikasi terkirim.', [
                    'fcm_name' => $response->json('name'),
                    'title'    => $notification['title'],
                ]);
                return true;
            }

            Log::error('[FCM] Gagal kirim notifikasi.', [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);
            return false;

        } catch (\Throwable $e) {
            Log::error('[FCM] Exception saat kirim notifikasi: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil Google OAuth2 Access Token dari Service Account JSON.
     * Token di-cache selama 55 menit (expire setiap 60 menit).
     */
    private function getAccessToken(): string
    {
        return Cache::remember('fcm_access_token', now()->addMinutes(55), function () {
            return $this->generateAccessToken();
        });
    }

    /**
     * Generate JWT dan tukar dengan access token ke Google OAuth2.
     */
    private function generateAccessToken(): string
    {
        if (!file_exists($this->credentialsPath)) {
            throw new \RuntimeException(
                "Firebase credentials tidak ditemukan: {$this->credentialsPath}\n" .
                "Download dari Firebase Console > Project Settings > Service Accounts > Generate new private key\n" .
                "Simpan sebagai: storage/app/firebase-service-account.json"
            );
        }

        $credentials = json_decode(file_get_contents($this->credentialsPath), true);

        if (empty($credentials['private_key']) || empty($credentials['client_email'])) {
            throw new \RuntimeException('Firebase service account JSON tidak valid.');
        }

        $now = time();

        // Build JWT Header dan Payload
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));

        $payload = $this->base64UrlEncode(json_encode([
            'iss'   => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $signingInput = "$header.$payload";

        // Sign dengan private key RSA
        $privateKey = openssl_pkey_get_private($credentials['private_key']);
        if (!$privateKey) {
            throw new \RuntimeException('Gagal membaca private key dari Service Account.');
        }

        $signature = '';
        if (!openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Gagal menandatangani JWT untuk FCM.');
        }

        $jwt = "$signingInput." . $this->base64UrlEncode($signature);

        // Tukar JWT dengan Access Token
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Gagal mendapat access token dari Google: ' . $response->body());
        }

        return $response->json('access_token');
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
