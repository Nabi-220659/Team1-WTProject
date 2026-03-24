<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;

try {
    $client = new Client('mongodb://localhost:27017');
    $db     = $client->selectDatabase('fundbee_db');
    $partnerId = 1;

    $db->partner_risk_insights->deleteMany(['partner_id' => $partnerId]);
    
    $db->partner_risk_insights->insertOne([
        'partner_id'       => $partnerId,
        'is_premium'       => true,
        'portfolio_health' => 'Low Risk',
        'health_color'     => '🟢',
        'score'            => 87,
        'npa_rate'         => '2.1',
        'at_risk_loans'    => 87,
        'early_warning'    => 14,
        'gauges'           => [
            'npa'         => '2.1%',
            'repayment'   => '97.8%',
            'delinquency' => '1.6%',
            'write_off'   => '0.45%'
        ],
        'segments' => [
            ['segment' => 'Personal',  'npa' => '2.8%', 'cibil' => 718, 'risk' => 'B+'],
            ['segment' => 'Business',  'npa' => '3.1%', 'cibil' => 704, 'risk' => 'B'],
            ['segment' => 'Home',      'npa' => '0.6%', 'cibil' => 748, 'risk' => 'A'],
            ['segment' => 'Education', 'npa' => '1.1%', 'cibil' => 732, 'risk' => 'A-'],
            ['segment' => 'Vehicle',   'npa' => '1.4%', 'cibil' => 726, 'risk' => 'A-']
        ],
        'stress_test' => [
            ['scenario' => 'Mild Recession (+3% NPA)',   'impact' => '−₹7.1 Cr impact'],
            ['scenario' => 'Severe Recession (+8% NPA)', 'impact' => '−₹19.0 Cr impact'],
            ['scenario' => 'Rate Hike (+2%)',            'impact' => '+₹4.7 Cr benefit']
        ]
    ]);

    echo "Risk insights seeded successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
