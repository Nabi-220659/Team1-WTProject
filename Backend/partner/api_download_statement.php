<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

try {
    $client    = new Client('mongodb://localhost:27017');
    $db        = $client->selectDatabase('fundbee_db');
    $partnerId = 1;
    $now       = date('Y-m-d H:i:s');
    $today     = date('Y-m-d');

    /* =========================================================
       POST — log download action (JSON response)
       ========================================================= */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $input  = json_decode(file_get_contents('php://input'), true);
        $format = strtoupper($input['format'] ?? 'CSV');
        $period = $input['period'] ?? 'all';

        $db->statement_downloads->insertOne([
            'partner_id'   => $partnerId,
            'format'       => $format,
            'period'       => $period,
            'downloaded_at'=> $now,
            'ip'           => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

        echo json_encode(['success' => true, 'message' => "Download logged ($format, period: $period)"]);
        exit;
    }

    /* =========================================================
       GET — history OR generate file
       ========================================================= */

    // ?history=1  → return recent downloads as JSON
    if (isset($_GET['history'])) {
        header('Content-Type: application/json');
        $downloads = $db->statement_downloads->find(
            ['partner_id' => $partnerId],
            ['sort' => ['downloaded_at' => -1], 'limit' => 10]
        )->toArray();

        $result = [];
        foreach ($downloads as $d) {
            $arr = iterator_to_array($d);
            unset($arr['_id']);
            $result[] = $arr;
        }
        echo json_encode(['success' => true, 'downloads' => $result]);
        exit;
    }


    $format = strtolower($_GET['format'] ?? 'csv');
    $period = $_GET['period'] ?? 'all';

    // Fetch earnings data
    $earnings = $db->partner_earnings->findOne(['partner_id' => $partnerId]);
    $history  = [];

    if ($earnings && isset($earnings['history_table'])) {
        foreach ($earnings['history_table'] as $row) {
            $r = is_array($row) ? $row : iterator_to_array($row);
            $history[] = $r;
        }
    } else {
        // Fallback static data if DB not seeded
        $history = [
            ['month'=>'March 2026',    'active_loans'=>47, 'earnings'=>'₹3.20 Cr', 'roi'=>'13.2%', 'change'=>'↑ 7.4%'],
            ['month'=>'February 2026', 'active_loans'=>44, 'earnings'=>'₹2.98 Cr', 'roi'=>'12.9%', 'change'=>'↑ 3.1%'],
            ['month'=>'January 2026',  'active_loans'=>41, 'earnings'=>'₹2.89 Cr', 'roi'=>'12.7%', 'change'=>'↑ 2.4%'],
            ['month'=>'December 2025', 'active_loans'=>40, 'earnings'=>'₹2.82 Cr', 'roi'=>'12.5%', 'change'=>'↑ 4.1%'],
            ['month'=>'November 2025', 'active_loans'=>38, 'earnings'=>'₹2.71 Cr', 'roi'=>'12.2%', 'change'=>'↑ 1.8%'],
            ['month'=>'October 2025',  'active_loans'=>36, 'earnings'=>'₹2.66 Cr', 'roi'=>'12.0%', 'change'=>'↓ 0.6%'],
        ];
    }

    // Filter by period
    if ($period !== 'all') {
        $history = array_filter($history, function($row) use ($period) {
            $month = strtolower($row['month'] ?? '');
            $key   = str_replace('_', ' ', strtolower($period));
            return strpos($month, $key) !== false;
        });
        $history = array_values($history);
    }

    // Log download to DB
    $db->statement_downloads->insertOne([
        'partner_id'    => $partnerId,
        'format'        => strtoupper($format),
        'period'        => $period,
        'rows_exported' => count($history),
        'downloaded_at' => $now,
        'ip'            => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    $filename = "FundBee_Statement_{$today}";

    /* ---- CSV ---- */
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        // Header block
        fputcsv($out, ['FUNDBEE Partner Statement']);
        fputcsv($out, ['Partner ID', $partnerId]);
        fputcsv($out, ['Generated On', $now]);
        fputcsv($out, ['Period', $period === 'all' ? 'All Time' : ucwords(str_replace('_', ' ', $period))]);
        fputcsv($out, []);

        // Column headers
        fputcsv($out, ['Month', 'Active Loans', 'Earnings', 'ROI', 'Change vs Prior Month']);

        // Data rows
        foreach ($history as $row) {
            fputcsv($out, [
                $row['month']        ?? '-',
                $row['active_loans'] ?? '-',
                $row['earnings']     ?? '-',
                $row['roi']          ?? '-',
                $row['change']       ?? '-',
            ]);
        }

        fputcsv($out, []);
        fputcsv($out, ['--- End of Statement ---']);
        fclose($out);
        exit;
    }

    /* ---- PDF (HTML-based — rendered by browser print) ---- */
    if ($format === 'pdf') {
        header('Content-Type: text/html; charset=UTF-8');

        $rowsHtml = '';
        foreach ($history as $row) {
            $changeColor = (strpos($row['change'] ?? '', '↓') !== false) ? '#ef4444' : '#10b981';
            $rowsHtml .= "<tr>
                <td>{$row['month']}</td>
                <td style='text-align:center'>{$row['active_loans']}</td>
                <td style='font-weight:700;color:#10b981'>{$row['earnings']}</td>
                <td style='color:#f5a623'>{$row['roi']}</td>
                <td style='color:{$changeColor};font-weight:600'>{$row['change']}</td>
            </tr>";
        }

        echo "<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<title>FundBee Statement</title>
<style>
  body { font-family: 'Segoe UI', sans-serif; padding: 40px; background: #fff; color: #1a1a2e; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #10b981; padding-bottom: 20px; margin-bottom: 30px; }
  .logo { font-size: 28px; font-weight: 900; color: #070f1e; }
  .logo span { color: #10b981; }
  .meta { font-size: 13px; color: #666; text-align: right; line-height: 1.8; }
  h2 { font-size: 18px; color: #070f1e; margin-bottom: 16px; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th { background: #070f1e; color: #fff; padding: 12px 16px; text-align: left; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; }
  td { padding: 12px 16px; border-bottom: 1px solid #eee; }
  tr:nth-child(even) { background: #f9fafb; }
  .footer { margin-top: 40px; font-size: 12px; color: #999; border-top: 1px solid #eee; padding-top: 16px; }
  @media print { body { padding: 20px; } button { display: none; } }
</style>
</head>
<body>
<div class='header'>
  <div class='logo'>FUND<span>BEE</span></div>
  <div class='meta'>
    <strong>Partner Statement</strong><br>
    Partner ID: {$partnerId}<br>
    Generated: {$now}<br>
    Period: " . ($period === 'all' ? 'All Time' : ucwords(str_replace('_', ' ', $period))) . "
  </div>
</div>

<h2>Monthly Earnings History</h2>
<table>
  <thead>
    <tr>
      <th>Month</th><th>Active Loans</th><th>Earnings</th><th>ROI</th><th>Change</th>
    </tr>
  </thead>
  <tbody>{$rowsHtml}</tbody>
</table>

<div class='footer'>
  This is an auto-generated statement. For queries, contact support@fundbee.in · 
  Downloaded via FundBee Partner Portal · {$now}
</div>

<script>
  window.onload = function() { window.print(); };
</script>
</body>
</html>";
        exit;
    }

    // Unknown format
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => "Unsupported format: $format"]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
