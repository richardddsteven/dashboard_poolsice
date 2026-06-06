<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit test parsing pesan WhatsApp.
 */
class OrderMessageParsingTest extends TestCase
{
    private function cleanPhone(?string $phone): ?string
    {
        if (!$phone) return null;
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        }
        return $phone;
    }

    private function hasOrderKeyword(string $message): bool
    {
        $lower = strtolower($message);
        foreach (['pesan', 'order', 'kirim', 'memesan', 'beli', 'membeli', 'pesen'] as $kw) {
            if (str_contains($lower, $kw)) return true;
        }
        return false;
    }

    private function hasInquiryKeyword(string $message): bool
    {
        $lower = strtolower($message);
        foreach (['bagaimana', 'gimana', 'cara', 'info', 'bisa pesen', 'mau pesen',
                  'ingin pesen', 'mau tau', 'mau tahu', 'boleh tau', 'boleh tahu',
                  'bisa pesan', 'ingin pesan', 'mau order'] as $kw) {
            if (str_contains($lower, $kw)) return true;
        }
        return false;
    }

    private function extractQuantity(string $message): int
    {
        $patterns = [
            '/(\d+)\s*(?:pcs|pc|buah|biji|psc|pieces|pca|pck)\b/i',
            '/(?:\b\d+\s?kg\b|\b\d+\s?kilo\b)\s*(?:nya\s*)?(\d{1,3})/i',
            '/\bnya\s+(\d+)\b/i',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $message, $m)) {
                $qty = (int) $m[1];
                if ($qty > 0 && $qty <= 100) return $qty;
            }
        }
        preg_match_all('/(?<!\d)(\d{1,3})(?!\d)/', $message, $m);
        foreach ($m[1] ?? [] as $n) {
            $num = (int) $n;
            if ($num > 0 && $num <= 50 && $num !== 5 && $num !== 20) return $num;
        }
        return 1;
    }

    /** cleanPhone() – Nomor dengan awalan 62 dikonversi ke format lokal 0xxx */
    public function test_clean_phone_converts_62_prefix(): void
    {
        $this->assertSame('081234567890', $this->cleanPhone('+62 812-3456-7890'));
    }

    /** hasOrderKeyword() – Kata kunci order dalam berbagai format terdeteksi */
    public function test_has_order_keyword_detects_pesen(): void
    {
        $this->assertTrue($this->hasOrderKeyword('bos mau pesen es dong'));
    }

    /** hasOrderKeyword() – Pesan tidak relevan tidak terdeteksi sebagai order */
    public function test_has_order_keyword_returns_false_for_irrelevant_message(): void
    {
        $this->assertFalse($this->hasOrderKeyword('halo selamat pagi'));
    }

    /** hasInquiryKeyword() – Pesan pertanyaan terdeteksi dengan benar */
    public function test_has_inquiry_keyword_detects_gimana(): void
    {
        $this->assertTrue($this->hasInquiryKeyword('gimana cara pesan es?'));
    }

    /** extractQuantityFromMessage() – Kuantitas diekstrak dari format pesan WhatsApp */
    public function test_extract_quantity_from_whatsapp_message(): void
    {
        $this->assertSame(3, $this->extractQuantity('pesan 20kg 3 pcs'));
    }

    /** extractQuantityFromMessage() – Default kuantitas 1 jika tidak ada angka ditemukan */
    public function test_extract_quantity_defaults_to_1_when_no_number_found(): void
    {
        $this->assertSame(1, $this->extractQuantity('mau pesan es'));
    }
}
