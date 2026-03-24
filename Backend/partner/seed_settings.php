<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;

try {
    $client = new Client('mongodb://localhost:27017');
    $db     = $client->selectDatabase('fundbee_db');
    $partnerId = 1;
    $now       = date('Y-m-d H:i:s');

    // 1. Profile
    $db->partner_profile->deleteMany(['partner_id' => $partnerId]);
    $db->partner_profile->insertOne([
        'partner_id'   => $partnerId,
        'full_name'    => 'Priya Nair',
        'partner_ref'  => 'FBPRT-20240882',
        'email'        => 'priya.nair@finance.in',
        'mobile'       => '+91 99887 76655',
        'organization' => 'Nair Capital Partners LLP',
        'city'         => 'Mumbai',
        'business_reg' => 'U65929MH2018PTC308821',
        'updated_at'   => $now
    ]);

    // 2. Banks
    $db->partner_banks->deleteMany(['partner_id' => $partnerId]);
    $db->partner_banks->insertMany([
        [
            'partner_id' => $partnerId,
            'bank_name'  => 'HDFC Bank',
            'account'    => '****4821',
            'type'       => 'Current',
            'is_primary' => true
        ],
        [
            'partner_id' => $partnerId,
            'bank_name'  => 'ICICI Bank',
            'account'    => '****9234',
            'type'       => 'Current',
            'is_primary' => false
        ]
    ]);

    // 3. Notifications
    $db->partner_notifications->deleteMany(['partner_id' => $partnerId]);
    $db->partner_notifications->insertOne([
        'partner_id'         => $partnerId,
        'settlement_alerts'  => true,
        'new_matches'        => true,
        'npa_alerts'         => true,
        'portfolio_reports'  => true,
        'regulatory_updates' => true,
        'updated_at'         => $now
    ]);

    // 4. KYC
    $db->partner_kyc->deleteMany(['partner_id' => $partnerId]);
    $db->partner_kyc->insertOne([
        'partner_id'            => $partnerId,
        'identity_verified'     => true,
        'business_verified'     => true,
        'bank_verified'         => true,
        'aml_audit_last_date'   => 'Jan 2026',
        'compliance_status'     => 'Compliant',
        'premium_status'        => 'Active',
        'next_review'           => 'Jan 2027',
        'updated_at'            => $now
    ]);

    // 5. Preferences (Shared with Deal Matcher)
    // Checking if already exists from previous seed
    $existingPref = $db->partner_preferences->findOne(['partner_id' => $partnerId]);
    if (!$existingPref) {
        $db->partner_preferences->insertOne([
            'partner_id'   => $partnerId,
            'min_yield'    => '11%',
            'min_cibil'    => 700,
            'max_exposure' => '₹20,00,000',
            'updated_at'   => $now
        ]);
    }

    echo "Settings data seeded successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
