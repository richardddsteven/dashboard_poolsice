<?php
namespace App\Http\Controllers;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Order;
use App\Models\IceType;
use App\Models\Zone;
use App\Services\FcmService;
use App\Services\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected FonnteService $fonnte;

    public function __construct(FonnteService $fonnte)
    {
        $this->fonnte = $fonnte;
    }

    public function fonnte(Request $request)
    {
        try {
            // Skip state-only payloads (read/delivered)
            if ($request->has('state') && !$request->has('message') && !$request->has('text')) {
                return response()->json(['status' => 'ok']);
            }

            Log::info('Webhook received', $request->all());

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
                    'phone'   => $phone,
                    'message' => $message,
                    'payload' => $request->all(),
                ]);
                return response()->json([
                    'status'   => 'error',
                    'message'  => 'Phone or message missing',
                    'received' => $request->all(),
                ], 400);
            }

            // ----------------------------------------------------------------
            // CONVERSATION FLOW: tanya nama & alamat untuk customer baru
            // ----------------------------------------------------------------
            // Refresh dari database untuk memastikan state terbaru
            $customer = Customer::where('phone', $phone)->first();
            $currentState = $customer?->conversation_state;

            // 1. Customer baru
            if (!$customer) {
                $hasOrderKw  = $this->hasOrderKeyword($message);
                $hasInquiry  = $this->hasInquiryKeyword($message);

                if ($hasOrderKw && $this->shouldReplyUnavailableIceType($message, false)) {
                    $this->fonnte->sendMessage(
                        $phone,
                        $this->fonnte->buildUnavailableIceTypeMessage()
                    );

                    return response()->json([
                        'status' => 'ok',
                        'message' => 'Unavailable ice type reply sent',
                    ]);
                }

                // Abaikan jika tidak ada keyword order maupun inquiry
                if (!$hasOrderKw && !$hasInquiry) {
                    Log::info('New number, no relevant keyword - ignored', ['phone' => $phone, 'message' => $message]);
                    return response()->json(['status' => 'ok', 'message' => 'Ignored - no relevant keyword']);
                }

                // Tentukan pending_message:
                // Jika pesan adalah order valid → simpan aslinya
                // Jika inquiry / pertanyaan → tandai '__INQUIRY__'
                $isValidOrder   = $hasOrderKw ? $this->validateOrderMessage($message) : ['is_valid' => false];
                $pendingMessage = ($hasOrderKw && $isValidOrder['is_valid']) ? $message : '__INQUIRY__';

                $customer = Customer::create([
                    'name'               => $phone,
                    'phone'              => $phone,
                    'address'            => null,
                    'zone'               => null,
                    'conversation_state' => 'awaiting_name',
                    'pending_message'    => $pendingMessage,
                ]);

                $this->fonnte->sendMessage(
                    $phone,
                    "Halo! Selamat datang di Pools Ice 🧊\n\nSebelum pesanan Anda diproses, boleh tahu nama toko Anda?"
                );

                Log::info('New customer created, awaiting name', ['phone' => $phone, 'pending' => $pendingMessage]);
                return response()->json(['status' => 'ok', 'message' => 'Awaiting customer name']);
            }

            // 2. Menunggu nama toko
            if ($currentState === 'awaiting_name') {
                $name = trim($message);
                $customer->update([
                    'name'               => $name,
                    'conversation_state' => 'awaiting_address',
                ]);

                $this->fonnte->sendMessage(
                    $phone,
                    "Terima kasih, {$name}!\n\nSekarang, boleh tahu alamat toko Anda?"
                );

                Log::info('Customer name saved', ['phone' => $phone, 'name' => $name, 'state' => 'awaiting_name']);
                return response()->json(['status' => 'ok', 'message' => 'Awaiting customer address']);
            }

            // 3. Menunggu alamat toko
            if ($currentState === 'awaiting_address') {
                $address        = trim($message);
                $pendingMessage = $customer->pending_message;
                $detectedZone   = $this->detectZoneFromAddress($address);
                $addressCoordinates = $this->resolveAddressCoordinates($address);

                Log::info('Customer address saved', [
                    'phone'   => $phone,
                    'address' => $address,
                    'zone'    => $detectedZone,
                    'latitude' => $addressCoordinates['latitude'] ?? null,
                    'longitude' => $addressCoordinates['longitude'] ?? null,
                    'state' => 'awaiting_address',
                ]);

                // Jika pending_message adalah order yang valid → proses langsung
                if ($pendingMessage && $pendingMessage !== '__INQUIRY__') {
                    $customer->update([
                        'address'            => $address,
                        'zone'               => $detectedZone,
                        'latitude'           => $addressCoordinates['latitude'] ?? null,
                        'longitude'          => $addressCoordinates['longitude'] ?? null,
                        'conversation_state' => null,
                        'pending_message'    => null,
                    ]);
                    return $this->processOrder($phone, $pendingMessage, $customer, $request);
                }

                // Inquiry / tidak ada order → tanya mau pesan apa
                $customer->update([
                    'address'            => $address,
                    'zone'               => $detectedZone,
                    'latitude'           => $addressCoordinates['latitude'] ?? null,
                    'longitude'          => $addressCoordinates['longitude'] ?? null,
                    'conversation_state' => 'awaiting_order',
                    'pending_message'    => null,
                ]);

                $this->fonnte->sendMessage(
                    $phone,
                    "Terima kasih, {$customer->name}! Data Anda sudah tersimpan 😊\n\nMau pesan apa ya kak?\n\nContoh: *20kg 2 pcs* atau *5kg 3 pcs*"
                );

                return response()->json(['status' => 'ok', 'message' => 'Awaiting order details']);
            }

            // 4. Menunggu detail pesanan (dari alur inquiry)
            if ($currentState === 'awaiting_order') {
                $customer->update(['conversation_state' => null]);

                Log::info('Processing order from inquiry flow', ['phone' => $phone, 'message' => $message, 'state' => 'awaiting_order']);
                // Skip validasi keyword order - pelanggan sudah dalam konteks pemesanan
                return $this->processOrder($phone, $message, $customer, $request, true);
            }

            // ----------------------------------------------------------------
            // CUSTOMER SUDAH ADA - tapi cek dulu apakah ada conversation state
            // ----------------------------------------------------------------
            if ($customer && !$currentState) {
                Log::info('Customer exists but no conversation state', [
                    'phone' => $phone,
                    'customer_name' => $customer->name,
                    'has_address' => !empty($customer->address),
                ]);
                
                // Jika belum ada nama (baru di-reset), restart flow
                if ($customer->name === $phone || empty($customer->name)) {
                    $customer->update(['conversation_state' => 'awaiting_name']);
                    $this->fonnte->sendMessage(
                        $phone,
                        "Halo kembali! 👋\n\nBoleh tahu nama toko Anda?"
                    );
                    Log::info('Restarted conversation flow - awaiting name', ['phone' => $phone]);
                    return response()->json(['status' => 'ok', 'message' => 'Restarted - awaiting name']);
                }
                
                // Sudah ada nama dan address, langsung proses order dengan validasi keyword
                // (TETAP cek keyword "pesen", tapi TIDAK tanya nama/alamat lagi)
                Log::info('Customer registered with complete data, processing order with keyword validation', [
                    'phone' => $phone,
                    'name' => $customer->name,
                    'address' => $customer->address,
                ]);
                return $this->processOrder($phone, $message, $customer, $request);
            }

            // ----------------------------------------------------------------
            // CUSTOMER SUDAH ADA DENGAN STATE NULL - validasi keyword sebelum proses
            // ----------------------------------------------------------------
            return $this->processOrder($phone, $message, $customer, $request);

        } catch (\Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage(), [
                'trace'   => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function processOrder(string $phone, string $message, Customer $customer, Request $request, bool $skipValidation = false)
    {
        Log::info('processOrder called', [
            'phone' => $phone,
            'message' => $message,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'skip_validation' => $skipValidation,
        ]);

        if ($this->shouldReplyUnavailableIceType($message, $skipValidation)) {
            $this->fonnte->sendMessage(
                $phone,
                $this->fonnte->buildUnavailableIceTypeMessage()
            );

            return response()->json([
                'status'  => 'ok',
                'message' => 'Unavailable ice type reply sent',
            ]);
        }

        if (!$skipValidation) {
            $isValidOrder = $this->validateOrderMessage($message);

            if (!$isValidOrder['is_valid']) {
                Log::info('Message skipped - not a valid order', [
                    'phone'   => $phone,
                    'message' => $message,
                    'reason'  => $isValidOrder['reason'],
                    'skip_validation' => $skipValidation,
                ]);
                return response()->json([
                    'status'  => 'ok',
                    'message' => 'Message received but not a valid order: ' . $isValidOrder['reason'],
                ]);
            }
        }

        $parsedOrder = $this->parseOrderMessage($message);

        if (!$parsedOrder['ice_type_id'] || $parsedOrder['quantity'] < 1) {
            Log::warning('Order parsing failed', [
                'phone'        => $phone,
                'message'      => $message,
                'parsed_order' => $parsedOrder,
                'skip_validation' => $skipValidation,
            ]);

            // Jika dari alur inquiry/new customer dalam state awaiting_order, beri tahu format benar
            if ($skipValidation) {
                $this->fonnte->sendMessage(
                    $phone,
                    "Maaf kak, format pesanan belum dikenali 🙏\n\nSilakan kirim dengan format:\n*[jenis es] [jumlah] pcs*\n\nContoh:\n- *20kg 2 pcs*\n- *5kg 3 pcs*"
                );

                return response()->json([
                    'status'  => 'ok',
                    'message' => 'Order format not recognized, asked customer to retry',
                ]);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Unable to parse order details from message',
            ], 400);
        }

        $order = Order::create([
            'customer_id' => $customer->id,
            'ice_type_id' => $parsedOrder['ice_type_id'],
            'quantity'    => $parsedOrder['quantity'],
            'phone'       => $phone,
            'items'       => $message,
            'status'      => 'pending',
            'raw_payload' => $request->all(),
        ]);

        Log::info('Order created', [
            'order_id'    => $order->id,
            'customer_id' => $customer->id,
        ]);

        $this->fonnte->sendMessage(
            $phone,
            "Terima kasih, {$customer->name}!\n\nPesanan Anda sudah kami terima dan sedang dicek stok supir.\nNanti akan ada update status otomatis ketika pesanan diterima, ditolak, atau selesai diantar."
        );

        // Kirim push notification ke semua supir di zona yang sama.
        $this->notifyDriversInZone($order, $customer);

        return response()->json([
            'status'   => 'success',
            'message'  => 'Order created',
            'order_id' => $order->id,
            'customer' => $customer->name,
        ]);
    }

    /**
     * Kirim push notification ke semua supir di zona yang sama dengan customer.
     * Berjalan asynchronous (fire and forget) agar tidak memperlambat response webhook.
     */
    private function notifyDriversInZone(Order $order, Customer $customer): void
    {
        $customerZone = strtolower(trim((string) ($customer->zone ?? '')));

        if ($customerZone === '') {
            Log::info('[FCM] Zone kosong, notifikasi supir dilewati.', ['order_id' => $order->id]);
            return;
        }

        try {
            // Cari semua supir di zona yang sama yang memiliki FCM token
            $drivers = Driver::with('zone:id,name')
                ->whereNotNull('fcm_token')
                ->whereNotNull('api_token') // hanya supir yang sedang login
                ->whereHas('zone', function ($query) use ($customerZone) {
                    $query->whereRaw('LOWER(name) = ?', [$customerZone]);
                })
                ->get();

            if ($drivers->isEmpty()) {
                Log::info('[FCM] Tidak ada supir online dengan FCM token di zona: ' . $customerZone);
                return;
            }

            $fcm = app(FcmService::class);

            $orderData = [
                'id'            => $order->id,
                'customer_name' => $customer->name,
                'zone'          => $customer->zone,
                'items'         => $order->items ?? '',
            ];

            foreach ($drivers as $driver) {
                $fcm->sendOrderNotification($driver->fcm_token, $orderData);

                Log::info('[FCM] Notifikasi order terkirim ke supir.', [
                    'driver_id'   => $driver->id,
                    'driver_name' => $driver->name,
                    'order_id'    => $order->id,
                    'zone'        => $customerZone,
                ]);
            }
        } catch (\Throwable $e) {
            // Jangan gagalkan webhook karena error FCM
            Log::error('[FCM] Gagal kirim notifikasi ke supir: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'zone'     => $customerZone,
            ]);
        }
    }

    private function validateOrderMessage($message)
    {
        $messageLower = strtolower($message);

        $orderKeywords = ['pesan', 'order', 'kirim', 'memesan', 'beli', 'membeli', 'pesen'];
        $hasOrderKeyword = false;
        foreach ($orderKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                $hasOrderKeyword = true;
                break;
            }
        }
        if (!$hasOrderKeyword) {
            $hasIceTypeWithoutKeyword = false;
            $iceTypes = IceType::getActiveTypes();

            foreach ($iceTypes as $iceType) {
                $weightLabel = preg_quote((string) $iceType->weight, '/');
                $nameLabel = preg_quote((string) $iceType->name, '/');

                if (
                    preg_match('/\b' . $nameLabel . '\b/i', $message) ||
                    preg_match('/\b' . $weightLabel . '\s?kg\b/i', $message) ||
                    preg_match('/\b' . $weightLabel . '\s?kilo\b/i', $message)
                ) {
                    $hasIceTypeWithoutKeyword = true;
                    break;
                }
            }

            if (!$hasIceTypeWithoutKeyword) {
                return ['is_valid' => false, 'reason' => 'No order keywords found'];
            }
        }

        $iceTypePatterns = [];
        $iceTypes = IceType::getActiveTypes();
        foreach ($iceTypes as $iceType) {
            $weightLabel = preg_quote((string) $iceType->weight, '/');
            $nameLabel = preg_quote((string) $iceType->name, '/');

            $iceTypePatterns[] = '/\b' . $nameLabel . '\b/i';
            $iceTypePatterns[] = '/\b' . $weightLabel . '\s?kg\b/i';
            $iceTypePatterns[] = '/\b' . $weightLabel . '\s?kilo\b/i';
        }
        $hasIceType = false;
        foreach ($iceTypePatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $hasIceType = true;
                break;
            }
        }
        if (!$hasIceType) {
            return ['is_valid' => false, 'reason' => 'No active ice type specified'];
        }

        $quantityPatterns = [
            '/\d+\s*(?:pcs|pc|buah|biji|psc|pieces|lagi)/i',
            '/(?:pcs|pc|buah|biji|psc|pieces|lagi)\s*\d+/i',
            '/\d+\s*(?:5kg|20kg|kilo)/i',
            '/\bnya\s+(\d+)/i',                   // "5kg nya 5"
            '/\bsebanyak\s+(\d+)/i',              // "sebanyak 5"
            '/\b(\d+)\s*(?:ya|aja|saja)\b/i',    // "5 ya", "5 aja"
        ];
        $hasQuantity = false;
        foreach ($quantityPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $hasQuantity = true;
                break;
            }
        }
        if (!$hasQuantity) {
            if (preg_match('/\d+\s*(?:kg|kilo)\s*\d+/i', $message) ||
                preg_match('/\d+\s*(?:5kg|20kg)/i', $message)) {
                $hasQuantity = true;
            }
        }
        if (!$hasQuantity) {
            preg_match_all('/\b(\d+)\b/', $message, $matches);
            foreach ($matches[1] ?? [] as $number) {
                $num = (int) $number;
                // Angka 5/20 dalam '5kg'/'20kg' tidak akan match \b\d+\b karena tidak ada
                // word boundary - jadi aman untuk tidak mengecualikan 5 dan 20 lagi
                if ($num > 0 && $num <= 100) {
                    $hasQuantity = true;
                    break;
                }
            }
        }
        if (!$hasQuantity) {
            return ['is_valid' => false, 'reason' => 'No quantity/pieces specified'];
        }

        $questionWords = ['bagaimana', 'gimana', 'cara', 'how', 'what', 'apa', 'berapa'];
        if (str_contains($message, '?')) {
            foreach ($questionWords as $qWord) {
                if (str_contains($messageLower, $qWord)) {
                    return ['is_valid' => false, 'reason' => 'Detected as question, not an order'];
                }
            }
        }

        return ['is_valid' => true, 'reason' => 'Valid order message'];
    }

    private function parseOrderMessage($message)
    {
        $result = ['ice_type_id' => null, 'quantity' => 0];

        $iceTypes = IceType::getActiveTypes();
        foreach ($iceTypes as $iceType) {
            $patterns = [
                '/\b' . preg_quote($iceType->name, '/') . '\b/i',
                '/\b' . preg_quote(str_replace('kg', '', $iceType->name), '/') . '\s?kg\b/i',
                '/\b' . preg_quote(str_replace('kg', '', $iceType->name), '/') . '\s?kilo\b/i',
            ];
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $message)) {
                    $result['ice_type_id'] = $iceType->id;
                    break 2;
                }
            }
        }

        $quantityPatterns = [];
        foreach ($iceTypes as $iceType) {
            $weight = preg_quote((string) $iceType->weight, '/');
            $name = preg_quote((string) $iceType->name, '/');

            // "15kg nya 5 pcs", "15kg 5pcs", "5pcs 15kg"
            $quantityPatterns[] = '/\b' . $weight . '\s?kg\b\D{0,18}(?:nya\s*)?(\d{1,3})\s*(?:pcs|pc|buah|biji|psc|pieces|pca|pck)\b/i';
            $quantityPatterns[] = '/\b(?:nya\s*)?(\d{1,3})\s*(?:pcs|pc|buah|biji|psc|pieces|pca|pck)\b\D{0,18}\b' . $weight . '\s?kg\b/i';
            $quantityPatterns[] = '/\b' . $name . '\b\D{0,18}(?:nya\s*)?(\d{1,3})\s*(?:pcs|pc|buah|biji|psc|pieces|pca|pck)\b/i';
            $quantityPatterns[] = '/\b(?:nya\s*)?(\d{1,3})\s*(?:pcs|pc|buah|biji|psc|pieces|pca|pck)\b\D{0,18}\b' . $name . '\b/i';
        }

        $quantityPatterns = [
            ...$quantityPatterns,
            // "3 pcs", "5 pc", "2 buah", "10 biji" - TANPA 'lagi' karena "pesen lagi 5kg" akan salah parse
            '/(\d+)\s*(?:pcs|pc|buah|biji|psc|pieces|pca|pck)\b/i',
            // "pcs 3", "pc 5", "buah 2"
            '/(?:pcs|pc|buah|biji|psc|pieces|pca|pck)\s*(\d+)/i',
            // "15kg 5 pcs" / "15kg nya 5 pcs" / "15kg = 5 pcs"
            '/(?:\b\d+\s?kg\b|\b\d+\s?kilo\b)\s*(?:nya\s*)?(\d{1,3})/i',
            // "5 pcs 15kg"
            '/(\d+)\s*(?:pcs|pc|buah|biji|psc|pieces|pca|pck)\b\D{0,18}(?:\b\d+\s?kg\b|\b\d+\s?kilo\b)/i',
            // "nya 3", "nya 5" - angka setelah kata "nya"
            '/\bnya\s+(\d+)\b/i',
        ];
        foreach ($quantityPatterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $quantity = (int) $matches[1];
                if ($quantity > 0 && $quantity <= 100) {
                    $result['quantity'] = $quantity;
                    break;
                }
            }
        }

        if ($result['quantity'] === 0) {
            preg_match_all('/(?<!\d)(\d{1,3})(?!\d)/', $message, $matches);
            foreach ($matches[1] ?? [] as $number) {
                $num = (int) $number;
                if ($num > 0 && $num <= 50 && $num != 5 && $num != 20) {
                    $result['quantity'] = $num;
                    break;
                }
            }
        }

        if ($result['quantity'] === 0) {
            $result['quantity'] = 1;
        }

        Log::info('Parsed order message', [
            'original_message' => $message,
            'parsed_result'    => $result,
        ]);

        return $result;
    }

    private function shouldReplyUnavailableIceType(string $message, bool $skipValidation): bool
    {
        $messageLower = strtolower($message);

        if (!$skipValidation && !$this->hasOrderKeyword($message)) {
            return false;
        }

        $activeWeights = IceType::getActiveTypes()
            ->pluck('weight')
            ->map(fn ($weight) => (float) $weight)
            ->unique()
            ->values()
            ->all();

        if (empty($activeWeights)) {
            return false;
        }

        if (preg_match_all('/\b(\d{1,3})\s?(?:kg|kilo)\b/i', $message, $matches)) {
            foreach ($matches[1] ?? [] as $weightValue) {
                $weight = (float) $weightValue;

                $isAvailable = false;
                foreach ($activeWeights as $activeWeight) {
                    if (abs($activeWeight - $weight) < 0.01) {
                        $isAvailable = true;
                        break;
                    }
                }

                if (!$isAvailable) {
                    return true;
                }
            }
        }

        if (preg_match('/\bjenis\s+es\b/i', $message)) {
            return true;
        }

        if (preg_match('/\bes\b/i', $message) && preg_match('/\b(?:pesan|order|beli|mau|minta|kirim)\b/i', $messageLower)) {
            return true;
        }

        return false;
    }

    private function cleanPhone($phone)
    {
        if (!$phone) return null;

        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 2) === '62') {
            $phone = '0' . substr($phone, 2);
        }

        return $phone;
    }

    private function hasOrderKeyword(string $message): bool
    {
        $messageLower = strtolower($message);
        $keywords = ['pesan', 'order', 'kirim', 'memesan', 'beli', 'membeli', 'pesen'];

        foreach ($keywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function hasInquiryKeyword(string $message): bool
    {
        $messageLower = strtolower($message);
        $keywords = [
            'bagaimana', 'gimana', 'cara', 'info', 'bisa pesen', 'mau pesen', 'ingin pesen',
            'nanya', 'tanya', 'mau tau', 'mau tahu', 'boleh tau',
            'boleh tahu', 'bisa pesan', 'ingin pesan', 'mau order',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function detectZoneFromAddress(string $address): ?string
    {
        $zones = Zone::query()->pluck('name');

        if ($zones->isEmpty()) {
            return null;
        }

        $zoneNameMap = $zones->mapWithKeys(function ($name) {
            return [strtolower($name) => $name];
        });

        $normalizedAddress = $this->normalizeText($address);
        $apiAddressContext = $this->getAddressContextFromApi($address);
        $searchHaystack = $normalizedAddress . $apiAddressContext;

        // Prioritas 1: cocokkan langsung nama zona yang ada di database.
        foreach ($zoneNameMap as $zoneLower => $zoneName) {
            if (str_contains($searchHaystack, $this->normalizeText($zoneLower))) {
                return $zoneName;
            }
        }

        // Prioritas 2: cocokkan keyword area umum per zona.
        $zoneAliases = [
            'canggu' => ['canggu', 'berawa', 'batu bolong', 'batubolong', 'pererenan', 'padonan', 'babakan'],
            'jimbaran' => ['jimbaran', 'kuta selatan', 'kedonganan', 'balangan', 'ungasan'],
            'uluwatu' => ['uluwatu', 'pecatu', 'padang padang', 'bingin', 'suluban'],
            'tabanan' => ['tabanan', 'kediri', 'marga', 'kerambitan', 'selemadeg'],
            'denpasar' => ['denpasar', 'renon', 'sanur', 'teuku umar', 'imam bonjol'],
            'surabaya' => ['surabaya', 'rungkut', 'wonokromo', 'sukolilo', 'genteng', 'gubeng', 'wiyung', 'dukuh pakis', 'jambangan', 'lakarsantri', 'mulyorejo', 'tandes'],
        ];

        foreach ($zoneAliases as $zoneLower => $aliases) {
            if (!$zoneNameMap->has($zoneLower)) {
                continue;
            }

            foreach ($aliases as $alias) {
                if (str_contains($searchHaystack, $this->normalizeText($alias))) {
                    return $zoneNameMap->get($zoneLower);
                }
            }
        }

        return null;
    }

    private function normalizeText(string $text): string
    {
        $normalized = strtolower($text);
        $normalized = preg_replace('/[^a-z0-9]+/', '', $normalized);

        return $normalized ?? '';
    }

    private function getAddressContextFromApi(string $address): string
    {
        $geocoding = $this->getAddressGeocodingResult($address);

        if (!$geocoding) {
            return '';
        }

        return $geocoding['context'] ?? '';
    }

    private function resolveAddressCoordinates(string $address): ?array
    {
        $geocoding = $this->getAddressGeocodingResult($address);

        if (!$geocoding) {
            return null;
        }

        return [
            'latitude' => $geocoding['latitude'],
            'longitude' => $geocoding['longitude'],
        ];
    }

    private function getAddressGeocodingResult(string $address): ?array
    {
        if (!config('services.zone_geocoding.enabled', true)) {
            return null;
        }

        $cacheKey = 'customer-address-geocoding:' . md5(strtolower(trim($address)));

        return Cache::remember($cacheKey, now()->addDays(14), function () use ($address) {
            $endpoint = config('services.zone_geocoding.endpoint', 'https://nominatim.openstreetmap.org/search');
            $timeout = (int) config('services.zone_geocoding.timeout', 8);
            $email = trim((string) config('services.zone_geocoding.email', ''));

            $queries = $this->buildGeocodingQueries($address);

            try {
                foreach ($queries as $q) {
                    $payload = [
                        'q' => $q,
                        'format' => 'jsonv2',
                        'addressdetails' => 1,
                        'limit' => 1,
                        'countrycodes' => 'id',
                        'accept-language' => 'id',
                    ];

                    if ($email !== '') {
                        $payload['email'] = $email;
                    }

                    $response = Http::timeout($timeout)
                        ->retry(1, 200)
                        ->withHeaders([
                            'User-Agent' => config('app.name', 'Laravel') . '/1.0 (zone-detection)',
                        ])
                        ->acceptJson()
                        ->get($endpoint, $payload);

                    if (!$response->ok()) {
                        continue;
                    }

                    $data = $response->json();
                    if (!is_array($data) || empty($data[0]) || !is_array($data[0])) {
                        continue;
                    }

                    $first = $data[0];
                    if (!isset($first['lat'], $first['lon']) || !is_numeric($first['lat']) || !is_numeric($first['lon'])) {
                        continue;
                    }

                    return [
                        'latitude' => (float) $first['lat'],
                        'longitude' => (float) $first['lon'],
                        'context' => $this->buildAddressContextFromGeocodingResult($first),
                    ];
                }

                Log::info('Zone geocoding returned empty results', [
                    'address' => $address,
                ]);

                return null;
            } catch (\Throwable $e) {
                Log::warning('Zone geocoding API failed', [
                    'address' => $address,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    private function buildAddressContextFromGeocodingResult(array $result): string
    {
        $parts = [];

        if (!empty($result['display_name'])) {
            $parts[] = $result['display_name'];
        }

        $addressParts = $result['address'] ?? [];
        if (is_array($addressParts)) {
            foreach (['road', 'suburb', 'village', 'city_district', 'city', 'county', 'state'] as $field) {
                if (!empty($addressParts[$field])) {
                    $parts[] = $addressParts[$field];
                }
            }
        }

        return $this->normalizeText(implode(' ', array_unique($parts)));
    }

    private function buildGeocodingQueries(string $address): array
    {
        $cleaned = $this->simplifyAddressForGeocoding($address);

        return array_values(array_unique([
            trim($address . ', Indonesia'),
            trim($address . ', Surabaya, Jawa Timur, Indonesia'),
            trim($address . ', Bali, Indonesia'),
            trim($cleaned . ', Indonesia'),
            trim($cleaned . ', Surabaya, Jawa Timur, Indonesia'),
            trim($cleaned . ', Jawa Timur, Indonesia'),
            trim($cleaned . ', Bali, Indonesia'),
            trim($cleaned . ', Denpasar, Bali, Indonesia'),
            trim($cleaned),
        ]));
    }

    private function simplifyAddressForGeocoding(string $address): string
    {
        $text = strtolower($address);

        // Samakan penulisan jalan agar mudah dikenali geocoder.
        $text = preg_replace('/\b(jln|jl\.|jl)\b/i', 'jalan', $text);

        // Buang nomor rumah/blok agar pencarian lokasi lebih umum.
        $text = preg_replace('/\b(no|nomor|no\.)\s*\d+[a-zA-Z0-9\/-]*\b/i', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}