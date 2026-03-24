<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$range = $_GET['range'] ?? '1m';

try {
    // Connect to MongoDB
    $client = new Client('mongodb://localhost:27017');
    $db = $client->selectDatabase('fundbee_db');
    
    // Query the database for the partner's analytics document
    $partnerId = 1; // Default to 1 for this demonstration
    
    $analyticsData = $db->partner_analytics->findOne(
        ['partner_id' => $partnerId],
        ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]
    );
    
    if ($analyticsData) {
        // Simulation logic based on range
        // Multipliers for randomization
        $multiplier = 1.0;
        if ($range === '3m') $multiplier = 1.05;
        if ($range === '1y') $multiplier = 1.15;

        // KPI simulation
        $analyticsData['kpis']['avg_yield'] = number_format($analyticsData['kpis']['avg_yield'] * $multiplier, 1);
        $analyticsData['kpis']['repayment_rate'] = number_format(min(99.9, $analyticsData['kpis']['repayment_rate'] * (1 / $multiplier) * 1.01), 1);
        $analyticsData['kpis']['npa_rate'] = number_format($analyticsData['kpis']['npa_rate'] * $multiplier, 1);
        $analyticsData['kpis']['aum'] = number_format($analyticsData['kpis']['aum'] * $multiplier, 0);

        // Synthetic chart data (SVG paths) for different ranges
        $charts = [
            '1m' => [
                'fill' => "M0,90 C50,80 80,70 120,65 C160,60 180,55 220,48 C260,41 290,35 330,28 C360,22 380,20 400,18 L400,130 L0,130 Z",
                'stroke' => "M0,90 C50,80 80,70 120,65 C160,60 180,55 220,48 C260,41 290,35 330,28 C360,22 380,20 400,18"
            ],
            '3m' => [
                'fill' => "M0,100 C40,95 70,85 110,80 C150,75 190,60 230,55 C270,50 310,40 350,35 C380,30 390,25 400,20 L400,130 L0,130 Z",
                'stroke' => "M0,100 C40,95 70,85 110,80 C150,75 190,60 230,55 C270,50 310,40 350,35 C380,30 390,25 400,20"
            ],
            '1y' => [
                'fill' => "M0,110 C60,105 100,90 140,85 C180,80 220,65 260,55 C300,45 340,30 380,20 C390,15 395,10 400,5 L400,130 L0,130 Z",
                'stroke' => "M0,110 C60,105 100,90 140,85 C180,80 220,65 260,55 C300,45 340,30 380,20 C390,15 395,10 400,5"
            ]
        ];

        $performance = $analyticsData['performance_metrics'];
        if ($range === '3m') {
            $performance['disbursement_rate'] = number_format($performance['disbursement_rate'] * 1.1, 2);
            $performance['on_time_payment_rate'] = 98.2;
        } else if ($range === '1y') {
            $performance['disbursement_rate'] = number_format($performance['disbursement_rate'] * 1.4, 2);
            $performance['on_time_payment_rate'] = 98.9;
        }

        $cohorts = $analyticsData['cohorts'] ?? [];
        if ($range === '3m') {
            array_unshift($cohorts, ["quarter" => "Q2 2026 (Est)", "loans" => 150, "disbursed" => 8.5, "repayment" => 99.1, "yield" => 15.2, "npa" => 0.8]);
        } else if ($range === '1y') {
            $cohorts = array_merge([
                ["quarter" => "FY 2026-27 Proj", "loans" => 1200, "disbursed" => 55.0, "repayment" => 98.5, "yield" => 14.9, "npa" => 1.2]
            ], $cohorts);
        }

        // Prepare the response
        $reformattedData = [
            "success" => true,
            "range" => $range,
            "partner" => $analyticsData['partner'],
            "kpis" => $analyticsData['kpis'],
            "portfolio_mix" => $analyticsData['portfolio_mix'],
            "performance_metrics" => $performance,
            "cohorts" => $cohorts,
            "ai_insights" => $analyticsData['ai_insights'] ?? [],
            "chart_data" => $charts[$range] ?? $charts['1m']
        ];
        
        echo json_encode($reformattedData);
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'Analytics data not found in database. Please run simple_seed_db.php first to generate the MongoDB records!'
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
