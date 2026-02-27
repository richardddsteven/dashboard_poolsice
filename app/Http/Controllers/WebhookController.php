<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\IceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function fonnte(Request $request)
    {
        try {
            // Skip status update (read/delivered) SECEPATNYA tanpa logging
            // State-only payload: hanya punya device, stateid, state
            if ($request->has('state') && !$request->has('message') && !$request->has('text')) {
                return response()->json(['status' => 'ok']);
            }

            // Log semua data yang masuk untuk debugging (hanya message nyata)
            Log::info('Webhook received', $request->all());

            // Ambil nomor pengirim dan pesan dari berbagai kemungkinan format Fonnte
            $phone = $this->cleanPhone(
                $request->input('sender') ?? 
                $request->input('from') ?? 
                $request->input('phone') ??
                null
            );
            
            $message = $request->input('message') ?? 
            $request->input('text') ?? 
            $request->input('body') ??
            null;

            if (!$phone || !$message) {
                Log::warning('Webhook missing data', [
                    'phone' => $phone,
                    'message' => $message,
                    'payload' => $request->all()
                ]);
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Phone or message missing',
                    'received' => $request->all()
                ], 400);
            }

            // Validasi pesan order yang komprehensif
            $isValidOrder = $this->validateOrderMessage($message);

            if (!$isValidOrder['is_valid']) {
                Log::info('Message skipped - not a valid order', [
                    'phone' => $phone,
                    'message' => $message,
                    'reason' => $isValidOrder['reason']
                ]);
                return response()->json([
                    'status' => 'ok', 
                    'message' => 'Message received but not a valid order: ' . $isValidOrder['reason']
                ]);
            }

            // Parse pesan untuk mendapatkan jenis es dan jumlah
            // Format: keyword - jenis es - jumlah pcs
            // Contoh: "order - 5kg - 10 pcs" atau "pesan - 20kg - 5"
            $parsedOrder = $this->parseOrderMessage($message);

            // Validasi hasil parsing - pastikan ice type dan quantity berhasil diparse
            if (!$parsedOrder['ice_type_id'] || $parsedOrder['quantity'] < 1) {
                Log::warning('Order parsing failed', [
                    'phone' => $phone,
                    'message' => $message,
                    'parsed_order' => $parsedOrder
                ]);
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Unable to parse order details from message'
                ], 400);
            }

            // Cari customer berdasarkan nomor telepon
            $customer = Customer::where('phone', $phone)->first();

            // Jika customer tidak ada, buat customer baru dengan nama = nomor telepon
            if (!$customer) {
                $customer = Customer::create([
                    'name' => $phone,
                    'phone' => $phone,
                    'address' => null,
                    'zone' => null,
                ]);
                Log::info('New customer created', ['phone' => $phone]);
            }

            // Buat order baru
            $order = Order::create([
                'customer_id' => $customer->id,
                'ice_type_id' => $parsedOrder['ice_type_id'],
                'quantity' => $parsedOrder['quantity'],
                'phone' => $phone,
                'items' => $message,
                'status' => 'pending',
                'raw_payload' => $request->all(),
            ]);

            Log::info('Order created', ['order_id' => $order->id, 'customer_id' => $customer->id]);

            return response()->json([
                'status' => 'success', 
                'message' => 'Order created',
                'order_id' => $order->id,
                'customer' => $customer->name
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);
            return response()->json([
                'status' => 'error', 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validasi pesan untuk memastikan ini adalah order yang valid
     * Harus memiliki: 1) keyword order, 2) jenis es (5kg/20kg), 3) jumlah/pcs
     */
    private function validateOrderMessage($message)
    {
        $messageLower = strtolower($message);
        
        // 1. Cek keyword order
        $orderKeywords = ['pesan', 'order', 'kirim', 'memesan', 'beli', 'membeli', 'pesen'];
        $hasOrderKeyword = false;
        
        foreach ($orderKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                $hasOrderKeyword = true;
                break;
            }
        }
        
        if (!$hasOrderKeyword) {
            return [
                'is_valid' => false,
                'reason' => 'No order keywords found'
            ];
        }

        // 2. Cek jenis es (5kg, 20kg)
        $iceTypePatterns = [
            '/\b5\s?kg\b/i',
            '/\b20\s?kg\b/i',
            '/\b5\s?kilo\b/i',
            '/\b20\s?kilo\b/i'
        ];
        
        $hasIceType = false;
        foreach ($iceTypePatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $hasIceType = true;
                break;
            }
        }
        
        if (!$hasIceType) {
            return [
                'is_valid' => false,
                'reason' => 'No ice type (5kg/20kg) specified'
            ];
        }

        // 3. Cek quantity/pcs
        $quantityPatterns = [
            '/\d+\s*(?:pcs|pc|buah|biji|psc|pieces|lagi)/i', // "5 pcs", "10 pc", "3 buah", "2 lagi" dll
            '/(?:pcs|pc|buah|biji|psc|pieces|lagi)\s*\d+/i', // "pcs 5", "pc 10", "lagi 2" dll  
            '/\d+\s*(?:5kg|20kg|kilo)/i', // "3 5kg", "2 20kg"
        ];
        
        $hasQuantity = false;
        foreach ($quantityPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $hasQuantity = true;
                break;
            }
        }
        
        // Jika tidak ada pattern pcs/buah, cek apakah ada angka setelah/sebelum jenis es
        if (!$hasQuantity) {
            // Pattern: "5kg 3", "20kg 5", "3 5kg", "5 20kg"
            if (preg_match('/\d+\s*(?:kg|kilo)\s*\d+/i', $message) || 
                preg_match('/\d+\s*(?:5kg|20kg)/i', $message)) {
                $hasQuantity = true;
            }
        }
        
        // Fallback: Cek apakah ada angka standalone yang bukan 5 atau 20 (kemungkinan quantity)
        if (!$hasQuantity) {
            preg_match_all('/\b(\d+)\b/', $message, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $number) {
                    $num = (int) $number;
                    if ($num > 0 && $num <= 100 && $num != 5 && $num != 20) {
                        $hasQuantity = true;
                        break;
                    }
                }
            }
        }
        
        if (!$hasQuantity) {
            return [
                'is_valid' => false,
                'reason' => 'No quantity/pieces specified'
            ];
        }

        // Tambahan: Cek apakah ini pertanyaan (mengandung tanda tanya dan kata tanya)
        $questionWords = ['bagaimana', 'gimana', 'cara', 'how', 'what', 'apa', 'berapa'];
        $isQuestion = str_contains($message, '?');
        
        if ($isQuestion) {
            foreach ($questionWords as $qWord) {
                if (str_contains($messageLower, $qWord)) {
                    return [
                        'is_valid' => false,
                        'reason' => 'Detected as question, not an order'
                    ];
                }
            }
        }

        return [
            'is_valid' => true,
            'reason' => 'Valid order message'
        ];
    }

    private function parseOrderMessage($message)
    {
        $messageLower = strtolower($message);
        
        // Default values
        $result = [
            'ice_type_id' => null,
            'quantity' => 0
        ];

        // Ambil semua ice types yang aktif
        $iceTypes = IceType::getActiveTypes();
        
        // 1. Cari jenis es (5kg atau 20kg)
        foreach ($iceTypes as $iceType) {
            $patterns = [
                '/\b' . preg_quote($iceType->name, '/') . '\b/i', // exact match "5kg", "20kg"
                '/\b' . preg_quote(str_replace('kg', '', $iceType->name), '/') . '\s?kg\b/i', // "5 kg", "20 kg"
                '/\b' . preg_quote(str_replace('kg', '', $iceType->name), '/') . '\s?kilo\b/i', // "5 kilo", "20 kilo"
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $message)) {
                    $result['ice_type_id'] = $iceType->id;
                    break 2; // break kedua loop
                }
            }
        }
        
        // 2. Cari quantity dengan berbagai pattern
        $quantityPatterns = [
            // Pattern: "5 pcs", "10 pc", "3 buah", "15 biji", "2 lagi"
            '/(\d+)\s*(?:pcs|pc|buah|biji|psc|pieces|lagi)/i',
            // Pattern: "pcs 5", "pc 10", "buah 3", "lagi 2"  
            '/(?:pcs|pc|buah|biji|psc|pieces|lagi)\s*(\d+)/i',
            // Pattern: "5kg 3", "20kg 5" (angka setelah jenis es)
            '/(?:5kg|20kg|5\s?kg|20\s?kg)\s*(\d+)/i',
            // Pattern: "3 5kg", "5 20kg" (angka sebelum jenis es)
            '/(\d+)\s*(?:5kg|20kg|5\s?kg|20\s?kg)/i',
        ];
        
        foreach ($quantityPatterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $quantity = (int) $matches[1];
                if ($quantity > 0 && $quantity <= 100) { // Validasi range wajar
                    $result['quantity'] = $quantity;
                    break;
                }
            }
        }
        
        // Fallback: jika belum dapat quantity, cari angka standalone yang masuk akal
        if ($result['quantity'] === 0) {
            // Cari semua angka dalam pesan
            preg_match_all('/\b(\d+)\b/', $message, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $number) {
                    $num = (int) $number;
                    // Skip angka yang kemungkinan bukan quantity (terlalu besar/kecil atau format tanggal/telepon)
                    if ($num > 0 && $num <= 50 && $num != 5 && $num != 20) { // 5,20 kemungkinan dari "5kg", "20kg"
                        $result['quantity'] = $num;
                        break;
                    }
                }
            }
        }
        
        // Jika masih belum dapat quantity, set default 1
        if ($result['quantity'] === 0) {
            $result['quantity'] = 1;
        }

        // Log parsed result untuk debugging
        Log::info('Parsed order message', [
            'original_message' => $message,
            'parsed_result' => $result,
            'ice_type_found' => $result['ice_type_id'] ? 'yes' : 'no',
            'quantity_found' => $result['quantity']
        ]);

        return $result;
    }

    private function cleanPhone($phone)
    {
        if (!$phone) return null;
        
        // Hapus karakter non-digit
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Normalisasi format Indonesia (hapus +62 atau 62 di depan, ganti dengan 0)
        if (substr($phone, 0, 2) === '62') {
            $phone = '0' . substr($phone, 2);
        }
        
        return $phone;
    }
}
