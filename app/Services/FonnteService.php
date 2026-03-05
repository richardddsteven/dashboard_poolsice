<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    protected string $token;
    protected string $apiUrl = 'https://api.fonnte.com/send';

    public function __construct()
    {
        $this->token = config('services.fonnte.token');
    }

    /**
     * Kirim pesan WhatsApp via Fonnte
     *
     * @param string $phone  Nomor tujuan (format: 08xx atau 628xx)
     * @param string $message  Isi pesan
     * @return bool
     */
    public function sendMessage(string $phone, string $message): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->apiUrl, [
                'target'  => $phone,
                'message' => $message,
            ]);

            // Log::info('Fonnte send response', [
            //     'phone'   => $phone,
            //     'status'  => $response->status(),
            //     'body'    => $response->json(),
            // ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Fonnte send error: ' . $e->getMessage());
            return false;
        }
    }
}
