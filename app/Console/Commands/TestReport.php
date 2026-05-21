<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class TestReport extends Command
{
    protected $signature = 'test:report
                            {--suite=Unit : Test suite yang dijalankan (Unit|Feature)}
                            {--filter= : Filter nama test tertentu}
                            {--export : Export hasil ke file HTML di public/test-report.html}';

    protected $description = 'Jalankan unit test dan tampilkan dalam tabel kolom (+ opsi export HTML)';

    private const ICON_PASS = '✓';
    private const ICON_FAIL = '✗';
    private const ICON_SKIP = '↷';

    // ═══════════════════════════════════════════════════════════════════════
    // HANDLE
    // ═══════════════════════════════════════════════════════════════════════

    public function handle(): int
    {
        $suite  = $this->option('suite');
        $filter = $this->option('filter');

        $this->renderHeader($suite);

        $logFile = storage_path('logs/phpunit-result.xml');

        $cmd = [
            (PHP_OS_FAMILY === 'Windows') ? 'vendor\\bin\\phpunit' : 'vendor/bin/phpunit',
            '--testsuite', $suite,
            '--log-junit', $logFile,
            '--colors=never',
        ];

        if ($filter) {
            $cmd[] = '--filter';
            $cmd[] = $filter;
        }

        $process = new Process($cmd, base_path());
        $process->setTimeout(120);
        $process->run();

        if (!file_exists($logFile)) {
            $this->error('Tidak dapat membaca hasil test.');
            $this->line($process->getOutput());
            return self::FAILURE;
        }

        $xml = simplexml_load_file($logFile);
        if ($xml === false) {
            $this->error('File XML tidak valid.');
            return self::FAILURE;
        }

        $rows = [];
        foreach ($xml->testsuite as $node) {
            $rows = array_merge($rows, $this->collectRows($node));
        }
        if (empty($rows)) {
            $rows = $this->collectRows($xml);
        }

        $totalPass = $totalFail = $totalSkip = 0;
        $totalTime = 0.0;
        foreach ($rows as $r) {
            $totalTime += (float) $r['time'];
            match ($r['status']) {
                'PASS'  => $totalPass++,
                'FAIL'  => $totalFail++,
                default => $totalSkip++,
            };
        }

        $this->renderTable($rows);
        $this->renderSummary($totalPass, $totalFail, $totalSkip, $totalTime);
        if ($totalFail > 0) {
            $this->renderFailureDetails($rows);
        }

        if ($this->option('export')) {
            $this->exportHtml($rows, $totalPass, $totalFail, $totalSkip, $totalTime, $suite);
        }

        @unlink($logFile);

        return $totalFail > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // COLLECT ROWS
    // ═══════════════════════════════════════════════════════════════════════

    private function collectRows(\SimpleXMLElement $node): array
    {
        $rows = [];

        foreach ($node->testcase as $tc) {
            $rawName = (string) $tc['name'];
            $name    = $this->humanizeName($rawName);
            $fn      = $this->inferTestedFunction($rawName);
            $time    = round((float) $tc['time'] * 1000, 2);
            $failure = $tc->failure ?? $tc->error ?? null;
            $skipped = $tc->skipped ?? null;
            $sysOut  = isset($tc->{'system-out'}) ? trim((string) $tc->{'system-out'}) : '';

            if ($skipped !== null) {
                $status = 'SKIP';
                $output = 'Test dilewati';
            } elseif ($failure !== null) {
                $status = 'FAIL';
                $output = $this->extractFailOutput((string) $failure);
            } else {
                $status = 'PASS';
                $output = $sysOut !== '' ? $this->truncate($sysOut, 55) : $this->inferPassOutput($rawName);
            }

            $rows[] = [
                'function' => $fn,
                'name'     => $name,
                'output'   => $output,
                'status'   => $status,
                'time'     => $time,
                'message'  => $failure ? (string) $failure : null,
            ];
        }

        foreach ($node->testsuite as $child) {
            $rows = array_merge($rows, $this->collectRows($child));
        }

        return $rows;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // RENDER TERMINAL
    // ═══════════════════════════════════════════════════════════════════════

    private function renderHeader(string $suite): void
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold> ┌────────────────────────────────────────────────────────┐</>');
        $this->line('<fg=cyan;options=bold> │         LAPORAN UNIT TEST – POOLS ICE DASHBOARD        │</>');
        $this->line('<fg=cyan;options=bold> └────────────────────────────────────────────────────────┘</>');
        $this->line(" Suite  : <fg=yellow>{$suite}</>");
        $this->line(' Waktu  : <fg=yellow>' . now()->format('d M Y, H:i:s') . '</>');
        $this->newLine();
    }

    private function renderTable(array $rows): void
    {
        $headers = ['#', 'Nama Function', 'Nama Test', 'Output', 'Status', 'Waktu (ms)'];
        $tableRows = [];

        foreach ($rows as $i => $row) {
            $statusTag = match ($row['status']) {
                'PASS'  => '<fg=green>' . self::ICON_PASS . ' PASS</>',
                'FAIL'  => '<fg=red>' . self::ICON_FAIL . ' FAIL</>',
                default => '<fg=yellow>' . self::ICON_SKIP . ' SKIP</>',
            };
            $outputTag = match ($row['status']) {
                'PASS'  => "<fg=green>{$row['output']}</>",
                'FAIL'  => "<fg=red>{$row['output']}</>",
                default => "<fg=yellow>{$row['output']}</>",
            };
            $timeTag = $row['time'] > 1000
                ? "<fg=red>{$row['time']}</>"
                : ($row['time'] > 200 ? "<fg=yellow>{$row['time']}</>" : "<fg=white>{$row['time']}</>");

            $tableRows[] = [
                '<fg=gray>' . ($i + 1) . '</>',
                "<fg=cyan>{$row['function']}</>",
                $row['name'],
                $outputTag,
                $statusTag,
                $timeTag,
            ];
        }

        $this->table($headers, $tableRows);
    }

    private function renderSummary(int $pass, int $fail, int $skip, float $totalMs): void
    {
        $total    = $pass + $fail + $skip;
        $duration = number_format($totalMs / 1000, 3);

        $this->newLine();
        $this->line('─────────────────────────────────────────────────────────────');
        $passLabel = '<fg=green;options=bold>' . self::ICON_PASS . " {$pass} Passed</>";
        $failLabel = $fail > 0 ? '<fg=red;options=bold>' . self::ICON_FAIL . " {$fail} Failed</>" : '<fg=gray>0 Failed</>';
        $skipLabel = $skip > 0 ? '<fg=yellow;options=bold>' . self::ICON_SKIP . " {$skip} Skipped</>" : '<fg=gray>0 Skipped</>';
        $this->line("  {$passLabel}   {$failLabel}   {$skipLabel}");
        $this->line("  <fg=gray>Total: {$total} tests  |  Durasi: {$duration}s</>");
        $this->line('─────────────────────────────────────────────────────────────');
        $this->newLine();
        $this->line($fail === 0
            ? '<fg=green;options=bold>  ✅ Semua test lulus! Sistem siap.</>'
            : "<fg=red;options=bold>  ❌ {$fail} test gagal.</>");
        $this->newLine();
    }

    private function renderFailureDetails(array $rows): void
    {
        $this->line('<fg=red;options=bold>══════════════════ DETAIL KEGAGALAN ══════════════════</>');
        $this->newLine();
        $no = 1;
        foreach ($rows as $row) {
            if ($row['status'] !== 'FAIL') continue;
            $this->line("<fg=red;options=bold>[{$no}] {$row['function']} → {$row['name']}</>");
            $this->newLine();
            if ($row['message']) {
                foreach (array_slice(explode("\n", trim($row['message'])), 0, 8) as $line) {
                    $this->line("    <fg=yellow>{$line}</>");
                }
            }
            $this->newLine();
            $no++;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HTML EXPORT
    // ═══════════════════════════════════════════════════════════════════════

    private function exportHtml(array $rows, int $pass, int $fail, int $skip, float $totalMs, string $suite): void
    {
        $total    = $pass + $fail + $skip;
        $duration = number_format($totalMs / 1000, 3);
        $date     = now()->format('d F Y, H:i:s');
        $statusBadge = $fail === 0
            ? '<span class="badge pass">LULUS SEMUA</span>'
            : "<span class=\"badge fail\">{$fail} GAGAL</span>";

        // ── Baris tabel ──────────────────────────────────────────────────
        $tableRows = '';
        foreach ($rows as $i => $row) {
            $no         = $i + 1;
            $fn         = htmlspecialchars($row['function']);
            $name       = htmlspecialchars($row['name']);
            $output     = htmlspecialchars($row['output']);
            $timeMs     = $row['time'];
            $statusClass = strtolower($row['status']);
            $statusText  = match ($row['status']) {
                'PASS'  => '✓ Lulus',
                'FAIL'  => '✗ Gagal',
                default => '↷ Lewati',
            };

            $tableRows .= <<<HTML
            <tr class="{$statusClass}">
                <td class="center">{$no}</td>
                <td class="fn">{$fn}</td>
                <td>{$name}</td>
                <td>{$output}</td>
                <td class="center status-cell {$statusClass}">{$statusText}</td>
                <td class="center">{$timeMs} ms</td>
            </tr>
HTML;
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Unit Testing – Dashboard Admin Pools Ice</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1a1a2e; background: #f4f6fb; }
  .wrapper { max-width: 1100px; margin: 30px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 16px rgba(0,0,0,.12); overflow: hidden; }

  /* Header */
  .header { background: linear-gradient(135deg, #1a237e 0%, #283593 100%); color: #fff; padding: 28px 36px; }
  .header h1 { font-size: 18px; font-weight: 700; letter-spacing: .5px; margin-bottom: 6px; }
  .header .sub { font-size: 12px; opacity: .85; }
  .meta { display: flex; gap: 32px; margin-top: 18px; flex-wrap: wrap; }
  .meta-item { display: flex; flex-direction: column; gap: 2px; }
  .meta-item .label { font-size: 10px; text-transform: uppercase; opacity: .7; }
  .meta-item .value { font-size: 13px; font-weight: 600; }

  /* Summary */
  .summary { display: flex; gap: 16px; padding: 20px 36px; background: #eef0f8; border-bottom: 1px solid #dde; flex-wrap: wrap; }
  .stat { flex: 1; min-width: 110px; background: #fff; border-radius: 6px; padding: 14px 18px; text-align: center; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
  .stat .num { font-size: 28px; font-weight: 800; line-height: 1; }
  .stat .lbl { font-size: 10px; text-transform: uppercase; color: #888; margin-top: 4px; }
  .stat.total  .num { color: #1a237e; }
  .stat.passed .num { color: #2e7d32; }
  .stat.failed .num { color: #c62828; }
  .stat.skipped .num { color: #e65100; }

  /* Badge */
  .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
  .badge.pass { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
  .badge.fail { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }

  /* Table */
  .table-wrap { padding: 24px 36px 36px; overflow-x: auto; }
  .table-wrap h2 { font-size: 13px; font-weight: 700; color: #1a237e; margin-bottom: 14px; text-transform: uppercase; letter-spacing: .5px; }
  table { width: 100%; border-collapse: collapse; font-size: 12px; }
  thead tr { background: #1a237e; color: #fff; }
  thead th { padding: 10px 12px; text-align: left; font-weight: 600; white-space: nowrap; }
  thead th.center { text-align: center; }
  tbody tr { border-bottom: 1px solid #e8eaf6; }
  tbody tr:hover { background: #f3f4ff; }
  tbody td { padding: 9px 12px; vertical-align: middle; }
  td.center { text-align: center; }
  td.fn { font-family: 'Consolas', 'Courier New', monospace; color: #283593; font-weight: 600; white-space: nowrap; }

  /* Status cells */
  td.status-cell { font-weight: 700; white-space: nowrap; }
  td.pass { color: #2e7d32; }
  td.fail { color: #c62828; }
  td.skip { color: #e65100; }
  tr.fail { background: #fff8f8; }
  tr.skip { background: #fffde7; }

  /* Footer */
  .footer { text-align: center; padding: 14px; font-size: 10px; color: #aaa; border-top: 1px solid #eee; }

  @media print {
    body { background: #fff; }
    .wrapper { box-shadow: none; margin: 0; }
    .header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    thead tr { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>
<div class="wrapper">

  <!-- Header -->
  <div class="header">
    <h1>📋 Laporan Unit Testing – Dashboard Admin Pools Ice</h1>
    <div class="sub">Pengujian Perangkat Lunak · Metode Black-Box Testing (Unit)</div>
    <div class="meta">
      <div class="meta-item"><span class="label">Suite</span><span class="value">{$suite}</span></div>
      <div class="meta-item"><span class="label">Tanggal</span><span class="value">{$date}</span></div>
      <div class="meta-item"><span class="label">Durasi</span><span class="value">{$duration} detik</span></div>
      <div class="meta-item"><span class="label">Status</span><span class="value">{$statusBadge}</span></div>
    </div>
  </div>

  <!-- Summary -->
  <div class="summary">
    <div class="stat total">
      <div class="num">{$total}</div>
      <div class="lbl">Total Test</div>
    </div>
    <div class="stat passed">
      <div class="num">{$pass}</div>
      <div class="lbl">Lulus</div>
    </div>
    <div class="stat failed">
      <div class="num">{$fail}</div>
      <div class="lbl">Gagal</div>
    </div>
    <div class="stat skipped">
      <div class="num">{$skip}</div>
      <div class="lbl">Dilewati</div>
    </div>
  </div>

  <!-- Table -->
  <div class="table-wrap">
    <h2>Hasil Pengujian Unit Testing</h2>
    <table>
      <thead>
        <tr>
          <th class="center" style="width:42px">No</th>
          <th style="width:180px">Nama Function</th>
          <th>Nama Test</th>
          <th>Output</th>
          <th class="center" style="width:90px">Status</th>
          <th class="center" style="width:90px">Waktu</th>
        </tr>
      </thead>
      <tbody>
        {$tableRows}
      </tbody>
    </table>
  </div>

  <div class="footer">Dashboard Admin Pools Ice · Laporan dibuat otomatis oleh <code>php artisan test:report --export</code></div>
</div>
</body>
</html>
HTML;

        $outputPath = public_path('test-report.html');
        file_put_contents($outputPath, $html);

        $this->newLine();
        $this->line('<fg=green;options=bold>  📄 File HTML berhasil dibuat!</>');
        $this->line("  Path  : <fg=yellow>{$outputPath}</>");
        $this->line('  Buka di browser → klik kanan → <fg=cyan>Save As</> atau <fg=cyan>Print → Save as PDF</>');
        $this->newLine();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPERS: inferTestedFunction
    // ═══════════════════════════════════════════════════════════════════════

    private function inferTestedFunction(string $rawMethod): string
    {
        $s = strtolower(preg_replace('/^test_?/', '', $rawMethod));

        // ── RouteStopDistanceTest ─────────────────────────────────────────
        if (str_starts_with($s, 'distance') || $s === 'return_type_is_float') {
            return 'distanceMetersFrom()';
        }

        // ── RouteRoutingServiceTest ───────────────────────────────────────
        if (str_starts_with($s, 'cache_value')) {
            return 'cacheValue()';
        }
        if (str_starts_with($s, 'cache_key')) {
            return 'buildCacheKey()';
        }
        if (str_contains($s, 'zero') || str_starts_with($s, 'near_zero')
            || str_starts_with($s, 'only_lat') || str_starts_with($s, 'only_lng')
            || str_starts_with($s, 'negative_coord') || str_starts_with($s, 'valid_bali')) {
            return 'isInvalidCoordinate()';
        }

        // ── OrderMessageParsingTest ───────────────────────────────────────
        if (str_starts_with($s, 'clean_phone')) {
            return 'cleanPhone()';
        }
        if (str_starts_with($s, 'has_order_keyword')) {
            return 'hasOrderKeyword()';
        }
        if (str_starts_with($s, 'has_inquiry_keyword')) {
            return 'hasInquiryKeyword()';
        }
        if (str_starts_with($s, 'extract_quantity')) {
            return 'extractQuantityFromMessage()';
        }

        // ── OrderEligibilityTest ──────────────────────────────────────────
        if (str_contains($s, 'eligible') || str_contains($s, 'rejected_when_customer')) {
            return 'isEligibleByIndex()';
        }
        if (str_contains($s, 'backtrack') || str_contains($s, '500m') || str_contains($s, '1500m')) {
            return 'maxBacktrackDistanceMeters()';
        }
        if (str_starts_with($s, 'max_backtrack') || str_starts_with($s, 'distance_500') || str_starts_with($s, 'distance_1500')) {
            return 'maxBacktrackDistanceMeters()';
        }

        // ── IceTypeDriverStockTest ────────────────────────────────────────
        if (str_contains($s, 'remaining_stock') || str_contains($s, 'initial_stock')
            || str_starts_with($s, 'zero_initial') || str_starts_with($s, 'zero_stock')) {
            return 'getRemainingStockAfterOrder()';
        }
        if (str_contains($s, 'date_format') || str_contains($s, 'date_format_is_accepted')
            || (str_contains($s, 'date') && str_contains($s, 'valid'))) {
            return 'scopeForDate()';
        }
        if (str_contains($s, 'stock_data') || str_contains($s, 'stock_quantity')) {
            return 'getTodayStocks()';
        }

        return 'N/A';
    }

    private function inferPassOutput(string $raw): string
    {
        $s = strtolower($raw);
        if (str_contains($s, 'null'))         return 'Kembalian → null ✓';
        if (str_contains($s, 'false'))        return 'Kembalian → false ✓';
        if (str_contains($s, 'true'))         return 'Kembalian → true ✓';
        if (str_contains($s, 'zero'))         return 'Kembalian → 0 ✓';
        if (str_contains($s, 'negative'))     return 'Hasil tidak negatif ✓';
        if (str_contains($s, 'positive'))     return 'Hasil positif ✓';
        if (str_contains($s, 'invalid'))      return 'Terdeteksi tidak valid ✓';
        if (str_contains($s, 'valid'))        return 'Terdeteksi valid ✓';
        if (str_contains($s, 'eligible'))     return 'Eligibilitas terverifikasi ✓';
        if (str_contains($s, 'distance'))     return 'Kalkulasi jarak benar ✓';
        if (str_contains($s, 'symmetric'))    return 'Jarak A→B = B→A ✓';
        if (str_contains($s, 'format'))       return 'Format output sesuai ✓';
        if (str_contains($s, 'cache'))        return 'Cache key valid ✓';
        if (str_contains($s, 'phone'))        return 'Nomor telepon diformat ✓';
        if (str_contains($s, 'keyword'))      return 'Keyword terdeteksi ✓';
        if (str_contains($s, 'quantity'))     return 'Kuantitas diekstrak ✓';
        if (str_contains($s, 'stock'))        return 'Kalkulasi stok benar ✓';
        return 'Assertion lulus ✓';
    }

    private function extractFailOutput(string $message): string
    {
        if (preg_match('/Failed asserting that (.{0,80})/i', $message, $m)) {
            return $this->truncate('Assert: ' . $m[1], 55);
        }
        return $this->truncate(explode("\n", trim($message))[0] ?? '', 55);
    }

    private function humanizeName(string $method): string
    {
        // Hapus prefix test_ lalu cari di tabel translasi
        $key = strtolower(preg_replace('/^test_?/i', '', $method));

        $translations = [
            // ── RouteStopDistanceTest ─────────────────────────────────────
            'distance_to_same_point_is_zero'                => 'Jarak ke titik yang sama menghasilkan nol',
            'distance_between_canggu_and_kuta'              => 'Jarak antara Canggu dan Kuta sesuai estimasi',
            'distance_between_two_real_coordinates'         => 'Jarak dua koordinat nyata di Bali dihitung benar',
            'distance_is_symmetric'                         => 'Perhitungan jarak bersifat simetris (A→B = B→A)',
            'distance_short_range_accuracy'                 => 'Akurasi jarak pada jangkauan pendek (<200m)',
            'return_type_is_float'                          => 'Tipe nilai kembalian adalah float',
            'distance_never_negative'                       => 'Hasil jarak tidak pernah bernilai negatif',

            // ── IceTypeDriverStockTest ────────────────────────────────────
            'remaining_stock_equals_initial_when_no_orders' => 'Sisa stok sama dengan awal jika belum ada order',
            'remaining_stock_deducted_correctly'        => 'Sisa stok terpotong dengan benar setelah order',
            'remaining_stock_zero_when_fully_used'      => 'Sisa stok nol ketika seluruh stok habis terpakai',
            'remaining_stock_never_negative'            => 'Sisa stok tidak pernah bernilai negatif',
            'zero_initial_stock_remains_zero'           => 'Stok awal nol tetap menghasilkan nol',
            'zero_stock_and_zero_used_is_zero'          => 'Stok nol dan order nol menghasilkan sisa nol',
            'valid_date_format_is_accepted'              => 'Format tanggal Y-m-d yang valid dikenali',
            'valid_date_format'                             => 'Format tanggal Y-m-d yang valid dikenali',
            'invalid_date_format'                           => 'Format tanggal yang salah menghasilkan error',
            'stock_data_structure_contains_expected_keys' => 'Struktur data stok memiliki semua key yang diharapkan',
            'stock_quantity_is_non_negative'            => 'Kuantitas stok dalam output tidak bernilai negatif',

            // ── OrderEligibilityTest ──────────────────────────────────────
            'order_rejected_when_customer_behind_driver'    => 'Order ditolak jika customer sudah di belakang posisi supir',
            'eligible_when_driver_has_no_route_stop'        => 'Order diterima jika supir belum memiliki posisi jalur',
            'eligible_when_customer_has_no_route_stop'      => 'Order diterima jika customer belum terpetakan ke jalur',
            'eligible_when_customer_at_same_stop_as_driver' => 'Order diterima jika customer di jalur yang sama dengan supir',
            'eligible_when_customer_at_same_stop'           => 'Order diterima jika customer di jalur yang sama dengan supir',
            'eligible_when_customer_is_ahead_of_driver'     => 'Order diterima jika customer berada di depan posisi supir',
            'not_eligible_when_customer_is_one_stop_behind' => 'Order ditolak jika customer satu jalur di belakang supir',
            'not_eligible_when_customer_is_far_behind'      => 'Order ditolak jika customer jauh di belakang posisi supir',
            'not_eligible_for_first_stop_when_driver_passed'=> 'Order ditolak jika supir sudah melewati jalur pertama',
            'max_backtrack_distance_is_positive'            => 'Nilai jarak balik maksimum bertipe bilangan positif',
            'distance_500m_within_default_backtrack'        => 'Jarak 500m masih dalam batas toleransi putar balik',
            'distance_1500m_exceeds_default_backtrack'      => 'Jarak 1500m melebihi batas toleransi putar balik',

            // ── OrderMessageParsingTest ───────────────────────────────────
            'clean_phone_converts_62_prefix'                => 'Nomor dengan awalan 62 dikonversi ke format lokal 0',
            'clean_phone_converts_62_prefix_to_0'           => 'Nomor dengan awalan 62 dikonversi ke format lokal 0',
            'clean_phone_strips_special_characters'         => 'Karakter spesial (+, -, spasi) dihapus dari nomor',
            'clean_phone_returns_null_for_null_input'       => 'Input null menghasilkan nilai kembalian null',
            'clean_phone_keeps_local_number_unchanged'      => 'Nomor lokal (08xx) tidak berubah setelah diproses',
            'clean_phone_handles_empty_string'              => 'String kosong dianggap tidak valid, menghasilkan null',
            'has_order_keyword_detects_pesen'               => 'Kata kunci order dalam berbagai format terdeteksi',
            'has_order_keyword_detects_pesan'               => 'Kata kunci "pesan" terdeteksi sebagai pesan order',
            'has_order_keyword_detects_pesen_slang'         => 'Kata slang "pesen" terdeteksi sebagai pesan order',
            'has_order_keyword_detects_order_english'       => 'Kata "order" (Inggris) terdeteksi sebagai pesan order',
            'has_order_keyword_case_insensitive'            => 'Deteksi keyword order tidak sensitif huruf kapital',
            'has_order_keyword_returns_false_for_irrelevant_message' => 'Pesan tidak relevan tidak terdeteksi sebagai order',
            'has_order_keyword_returns_false_for_empty'     => 'Pesan kosong tidak terdeteksi sebagai order',
            'has_inquiry_keyword_detects_gimana'            => 'Pesan pertanyaan terdeteksi dengan benar',
            'has_inquiry_keyword_detects_mau_pesen'         => 'Frasa "mau pesen" terdeteksi sebagai pesan pertanyaan',
            'has_inquiry_keyword_detects_info'              => 'Kata "info" terdeteksi sebagai pesan pertanyaan',
            'has_inquiry_keyword_returns_false_for_plain_order' => 'Pesan order biasa tidak dianggap sebagai pertanyaan',
            'extract_quantity_from_whatsapp_message'        => 'Kuantitas diekstrak dari format pesan WhatsApp',
            'extract_quantity_from_pcs_format'              => 'Kuantitas dari format "pcs" berhasil diekstrak',
            'extract_quantity_from_buah_format'             => 'Kuantitas dari format "buah" berhasil diekstrak',
            'extract_quantity_from_nya_suffix'              => 'Kuantitas dengan akhiran "nya" berhasil diekstrak',
            'extract_quantity_single_digit_fallback'        => 'Angka satuan digunakan sebagai fallback kuantitas',
            'extract_quantity_does_not_count_kg_weight_as_quantity' => 'Bobot (5kg/20kg) tidak dihitung sebagai kuantitas',
            'extract_quantity_caps_at_100'                  => 'Kuantitas lebih dari 100 diabaikan',
            'extract_quantity_defaults_to_1_when_no_number_found' => 'Default kuantitas 1 jika tidak ada angka ditemukan',

            // ── RouteRoutingServiceTest ───────────────────────────────────
            'zero_coordinate_is_invalid'                    => 'Koordinat (0,0) dianggap tidak valid',
            'cache_key_has_correct_format'                  => 'Cache key dibentuk dengan format yang benar',
            'zero_zero_is_invalid'                          => 'Koordinat (0,0) dianggap tidak valid',
            'near_zero_both_axes_is_invalid'                => 'Koordinat mendekati nol di dua sumbu tidak valid',
            'valid_bali_coordinate_is_not_invalid'          => 'Koordinat Bali yang valid diterima sebagai koordinat sah',
            'only_lat_near_zero_is_valid'                   => 'Hanya latitude mendekati nol tetap dianggap valid',
            'only_lng_near_zero_is_valid'                   => 'Hanya longitude mendekati nol tetap dianggap valid',
            'negative_coordinates_are_not_invalid'          => 'Koordinat negatif (Selatan/Barat) tetap valid',
            'cache_value_formats_to_five_decimal_places'    => 'Nilai cache diformat menjadi 5 angka desimal',
            'cache_value_rounds_correctly'                  => 'Pembulatan nilai desimal pada cache key benar',
            'cache_value_pads_with_trailing_zeros'          => 'Nilai cache ditambahkan nol di belakang jika perlu',
            'cache_value_handles_zero'                      => 'Nilai nol diformat dengan benar menjadi "0.00000"',
            'cache_key_format_is_correct'                   => 'Format string cache key sesuai yang diharapkan',
            'cache_key_same_for_same_coordinates'           => 'Cache key identik untuk koordinat yang sama',
            'cache_key_differs_for_different_coordinates'   => 'Cache key berbeda untuk koordinat yang berbeda',
            'cache_key_is_symmetric_directional'            => 'Cache key A→B berbeda dengan B→A (bukan simetris)',
        ];

        if (isset($translations[$key])) {
            return $translations[$key];
        }

        // Fallback: konversi snake_case ke kalimat
        $name = str_replace('_', ' ', $key);
        return ucfirst(strtolower(trim($name)));
    }

    private function truncate(string $text, int $max): string
    {
        $text = preg_replace('/\s+/', ' ', $text);
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max - 1) . '…' : $text;
    }
}
