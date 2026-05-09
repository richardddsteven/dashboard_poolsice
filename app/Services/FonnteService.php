<?php

namespace App\Services;

use App\Models\IceType;
use App\Models\Order;
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

    /**
     * Build WhatsApp menu message dengan list jenis es yang tersedia
     * 
     * @return string
     */
    public function buildIceTypesMenu(): string
    {
        $iceTypes = IceType::getActiveTypes();

        if ($iceTypes->isEmpty()) {
            return '🧊 Maaf, saat ini tidak ada jenis es yang tersedia. Silakan coba lagi nanti.';
        }

        $message = "🧊 *Daftar Jenis Es Tersedia:*\n\n";
        
        foreach ($iceTypes as $index => $type) {
            $no = $index + 1;
            $message .= "$no. *{$type->name}*";
            
            if ($type->price > 0) {
                $message .= " - Rp " . number_format($type->price, 0, ',', '.');
            }
            
            // if ($type->description) {
            //     $message .= "\n   _{$type->description}_";
            // }
            
            $message .= "\n";
        }

        $message .= "\nKetik nama atau berat es yang ingin Anda pesan, contoh: *5kg* atau *15kg*\n";
        $message .= "Cantumkan jumlah pcs, contoh: *5kg 10pcs* atau *10pcs 5kg*";

        return $message;
    }

    /**
     * Kirim menu jenis es ke pelanggan
     * 
     * @param string $phone
     * @return bool
     */
    public function sendIceTypesMenu(string $phone): bool
    {
        $message = $this->buildIceTypesMenu();
        return $this->sendMessage($phone, $message);
    }

    /**
     * Build pesan fallback saat customer meminta jenis es yang tidak tersedia.
     */
    public function buildUnavailableIceTypeMessage(): string
    {
        $availableWeights = IceType::getActiveTypes()
            ->pluck('weight')
            ->map(fn ($weight) => rtrim(rtrim(number_format((float) $weight, 2, '.', ''), '0'), '.'))
            ->unique()
            ->sort(fn ($a, $b) => (float) $a <=> (float) $b)
            ->values();

        if ($availableWeights->isEmpty()) {
            return "Maaf kak, jenis es yang Anda minta tidak ada.
\nSaat ini kami belum memiliki jenis es yang aktif.";
        }

        $message = "Maaf kak, jenis es yang Anda minta tidak ada.\n\n";
        // $message .= 'Kami hanya tersedia: ' . $availableWeights->map(fn ($weight) => $weight . 'kg')->join(', ') . ".\n\n";
        $message .= $this->buildIceTypesMenu();

        return $message;
    }

    /**
     * Build pesan status order untuk pelanggan.
     */
    public function buildOrderStatusMessage(Order $order, string $status, ?string $note = null): string
    {
        $customerName = trim((string) ($order->customer?->name ?? 'Pelanggan'));
        $orderLabel = '#' . $order->id;
        $iceTypeLabel = trim((string) ($order->iceType?->name ?? ''));
        $quantityLabel = max(1, (int) ($order->effective_quantity ?? $order->quantity ?? 1));

        $message = "Halo {$customerName},\n\n";

        if ($status === 'approved') {
            $message .= "Pesanan sudah diterima driver dan akan segera diantarkan.";
        } elseif ($status === 'completed') {
            $message .= "Pesanan sudah selesai diantarkan. Terima kasih sudah memesan.";
        } elseif ($status === 'rejected') {
            $message .= "Maaf, pesanan anda tidak dapat diproses dan sudah ditolak.";
        } else {
            $message .= "Status pesanan {$orderLabel} telah diperbarui menjadi {$status}.";
        }

        if ($iceTypeLabel !== '') {
            $message .= "\nItem: {$iceTypeLabel} - {$quantityLabel} pcs";
        }

        if ($note) {
            $message .= "\nCatatan: {$note}";
        }

        return $message;
    }

    /**
     * Kirim notifikasi status order ke pelanggan.
     */
    public function sendOrderStatusUpdate(string $phone, Order $order, string $status, ?string $note = null): bool
    {
        $message = $this->buildOrderStatusMessage($order, $status, $note);

        return $this->sendMessage($phone, $message);
    }

    /**
     * Build welcome message dengan informasi ice types
     * 
     * @return string
     */
    public function buildWelcomeMessage(): string
    {
        $iceTypes = IceType::getActiveTypes();
        $typeNames = $iceTypes->pluck('name')->join(', ');

        $message = "👋 *Selamat datang di Layanan Es Kami!*\n\n";
        $message .= "Kami menyediakan berbagai jenis es:\n";
        $message .= "$typeNames\n\n";
        $message .= "Ketik menu untuk melihat daftar lengkap atau langsung order dengan format:\n";
        $message .= "*[jenis es] [jumlah]* contoh: *5kg 10pcs*";

        return $message;
    }
}
