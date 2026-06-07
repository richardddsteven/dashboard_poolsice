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
            'title' => "Order Baru #$orderId",
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

            $title = $notification['title'] ?? 'Pools Ice';
            $body  = $notification['body']  ?? '';

            $payload = [
                'message' => [
                    'token' => $token,

                    // Tambah notification block agar FCM bisa menampilkan
                    // notifikasi secara native bahkan saat app dalam kondisi terminated/killed,
                    // tanpa harus bergantung pada background handler Flutter.
                    // Sebelumnya hanya data payload → notifikasi hilang jika handler tidak aktif.
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],

                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => [
                            // Pastikan notification masuk ke channel yang sama
                            // dengan yang didefinisikan di Flutter (_kDriverOrderChannel)
                            'channel_id'     => 'driver_orders_v2',
                            'notification_priority' => 'PRIORITY_MAX',
                            'sound'          => 'default',
                            'default_sound'  => true,
                            'default_vibrate_timings' => true,
                        ],
                    ],

                    // Data block tetap ada agar Flutter dapat memproses payload
                    // saat notifikasi ditap (onMessageOpenedApp) atau saat foreground (onMessage).
                    'data' => array_map('strval', array_merge($data, [
                        'title' => $title,
                        'body'  => $body,
                    ])),
                ],
            ];

            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->post($this->fcmEndpoint, $payload);

            if ($response->successful()) {
                Log::info('[FCM] Notifikasi terkirim.', [
                    'fcm_name' => $response->json('name'),
                    'title'    => $title,
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
     * Pre-warm FCM access token ke cache.
     * Dipanggil saat app boot agar order pertama tidak kena delay.
     */
    public function warmAccessToken(): void
    {
        try {
            // Hanya generate jika belum ada di cache
            if (!Cache::has('fcm_access_token')) {
                $this->getAccessToken();
            }
        } catch (\Throwable $e) {
            // Gagal diam-diam — tidak mengganggu boot
            Log::warning('[FCM] Gagal pre-warm access token: ' . $e->getMessage());
        }
    }

    /**
     * Ambil Google OAuth2 Access Token dari Service Account JSON.
     * Token di-cache selama 50 menit (expire setiap 60 menit).
     * Lebih pendek dari sebelumnya untuk hindari edge case token hampir expire.
     */
    private function getAccessToken(): string
    {
        return Cache::remember('fcm_access_token', now()->addMinutes(50), function () {
            return $this->generateAccessToken();
        });
    }

    /**
     * Generate JWT dan tukar dengan access token ke Google OAuth2.
     */
    private function generateAccessToken(): string
    {
        // Prioritas 1: baca dari environment variable (untuk Railway/cloud deployment)
        $credentialsJson = env('FIREBASE_CREDENTIALS_JSON');
        if ($credentialsJson) {
            $credentials = json_decode($credentialsJson, true);
        } elseif (file_exists($this->credentialsPath)) {
            // Prioritas 2: baca dari file lokal
            $credentials = json_decode(file_get_contents($this->credentialsPath), true);
        } else {
            throw new \RuntimeException(
                "Firebase credentials tidak ditemukan.\n" .
                "Set env FIREBASE_CREDENTIALS_JSON dengan isi JSON service account,\n" .
                "atau simpan file ke: {$this->credentialsPath}"
            );
        }

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
