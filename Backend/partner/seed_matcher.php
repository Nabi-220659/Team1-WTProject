<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;

try {
    $client    = new Client('mongodb://localhost:27017');
    $db        = $client->selectDatabase('fundbee_db');
    $partnerId = 1;
    $now       = date('Y-m-d H:i:s');

    // 1. KPIs
    $db->partner_matcher_kpis->deleteMany(['partner_id' => $partnerId]);
    $db->partner_matcher_kpis->insertOne([
        'partner_id'  => $partnerId,
        'new_matches' => 8,
        'avg_yield'   => '13.4%',
        'avg_cibil'   => 742,
        'total_pool'  => '₹1.8 Cr',
        'updated_at'  => $now
    ]);

    // 2. Preferences
    $db->partner_preferences->deleteMany(['partner_id' => $partnerId]);
    $db->partner_preferences->insertOne([
        'partner_id'   => $partnerId,
        'min_yield'    => '12%',
        'min_cibil'    => 720,
        'max_exposure' => '₹5L',
        'segments'     => ['Personal', 'Business', 'Education'],
        'auto_fund'    => false,
        'daily_digest' => true,
        'updated_at'   => $now
    ]);

    // 3. Matches
    $db->partner_matches->deleteMany(['partner_id' => $partnerId]);
    $db->partner_matches->insertMany([
        [
            'partner_id'    => $partnerId,
            'loan_id'      => 'L1001',
            'name'          => 'Rahul Sharma',
            'type'          => 'Personal Loan',
            'match_percent' => '98',
            'match_tier'    => 'high',
            'amount'        => '₹2.5L',
            'tenure'        => '24 Months',
            'cibil'         => 768,
            'yield'         => '14.2%',
            'risk'          => 'A+',
            'income'        => '₹85k/mo',
            'initials'      => 'RS',
            'color_class'   => 'da-green',
            'ring_class'    => 'ms-high',
            'ai_reason'     => 'Excellent repayment history and stable income aligns with your low-risk appetite.'
        ],
        [
            'partner_id'    => $partnerId,
            'loan_id'      => 'L1002',
            'name'          => 'Amit Enterprises',
            'type'          => 'Business Loan',
            'match_percent' => '92',
            'match_tier'    => 'medium',
            'amount'        => '₹15L',
            'tenure'        => '36 Months',
            'cibil'         => 741,
            'yield'         => '16.5%',
            'risk'          => 'B+',
            'income'        => '₹4.2L/mo',
            'initials'      => 'AE',
            'color_class'   => 'da-gold',
            'ring_class'    => 'ms-mid',
            'ai_reason'     => 'High-yield opportunity in a growing sector, within your business segment preference.'
        ],
        [
            'partner_id'    => $partnerId,
            'loan_id'      => 'L1003',
            'name'          => 'Sneha Kapoor',
            'type'          => 'Education Loan',
            'match_percent' => '96',
            'match_tier'    => 'high',
            'amount'        => '₹12L',
            'tenure'        => '60 Months',
            'cibil'         => 752,
            'yield'         => '11.8%',
            'risk'          => 'A',
            'income'        => '₹65k/mo',
            'initials'      => 'SK',
            'color_class'   => 'da-blue',
            'ring_class'    => 'ms-high',
            'ai_reason'     => 'Low delinquency risk and strong collateral coverage.'
        ]
    ]);

    echo "Deal Matcher data seeded successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
