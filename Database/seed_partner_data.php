<?php
/**
 * seed_partner_data.php — Seeds ALL partner-related collections used by the Partner Dashboard
 *
 * Run: php Database/seed_partner_data.php
 * Or visit in browser: http://localhost/Loan-Management-System/Database/seed_partner_data.php
 *
 * Collections seeded:
 *   partner_dashboard_kpis      → Dashboard summary KPIs
 *   partner_earnings            → Earnings breakdown and history
 *   partner_portfolio           → Portfolio summary
 *   partner_analytics           → Analytics charts and KPIs
 *   partner_borrowers           → Borrower records
 *   partner_matches             → Deal matcher entries
 *   partner_risk_insights       → Risk analysis data
 *   partner_profile             → Partner profile settings
 *   partner_banks               → Linked bank accounts
 *   partner_notifications       → Notification preferences
 *   partner_kyc                 → KYC documents
 *   partner_preferences         → App preferences
 *   users                       → Seed demo user for virtual bank
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;

$client = new Client('mongodb://localhost:27017');
$db     = $client->selectDatabase('fundbee_db');

echo "<pre style='font-family:monospace; background:#111; color:#0f0; padding:20px;'>";
echo "🌱 FUNDBEE Partner Data Seeder\n";
echo str_repeat('─', 50) . "\n\n";

// ══════════════════════════════════════════════
// 1. PARTNER DASHBOARD KPIs
// ══════════════════════════════════════════════
$db->partner_dashboard_kpis->drop();
$db->partner_dashboard_kpis->insertOne([
    'partner_id'      => 1,
    'total_earnings'  => '₹33.8 Cr',
    'active_loans'    => 127,
    'approval_rate'   => '82%',
    'avg_yield'       => '14.2%',
    'aum'             => '₹12.4 Cr',
    'pending_actions' => 3,
    'updated_at'      => new UTCDateTime(),
]);
echo "✅ partner_dashboard_kpis — seeded\n";

// ══════════════════════════════════════════════
// 2. PARTNER EARNINGS
// ══════════════════════════════════════════════
$db->partner_earnings->drop();
$db->partner_earnings->insertOne([
    'partner_id'         => 1,
    'total_earned'       => 33800000,
    'available_balance'  => 824500,
    'pending_settlement' => 14000000,
    'total_withdrawn'    => 34260000,
    'this_month'         => 3200000,
    'last_month'         => 2980000,
    'monthly_breakdown'  => [
        ['month' => 'Jan 2026', 'amount' => 2890000, 'loans' => 41],
        ['month' => 'Feb 2026', 'amount' => 2980000, 'loans' => 44],
        ['month' => 'Mar 2026', 'amount' => 3200000, 'loans' => 48],
    ],
    'commission_rate'    => '1.5%',
    'updated_at'         => new UTCDateTime(),
]);
echo "✅ partner_earnings — seeded\n";

// ══════════════════════════════════════════════
// 3. PARTNER PORTFOLIO
// ══════════════════════════════════════════════
$db->partner_portfolio->drop();
$db->partner_portfolio->insertOne([
    'partner_id'        => 1,
    'total_aum'         => 124000000,
    'active_loans'      => 127,
    'total_deployed'    => 340000000,
    'avg_ticket_size'   => 976378,
    'npa_rate'          => '0.8%',
    'repayment_rate'    => '98.2%',
    'portfolio_mix' => [
        ['type' => 'Personal Loan', 'percentage' => 45, 'amount' => 55800000],
        ['type' => 'Business Loan', 'percentage' => 35, 'amount' => 43400000],
        ['type' => 'Home Loan',     'percentage' => 15, 'amount' => 18600000],
        ['type' => 'Instant Loan',  'percentage' => 5,  'amount' => 6200000],
    ],
    'updated_at' => new UTCDateTime(),
]);
echo "✅ partner_portfolio — seeded\n";

// ══════════════════════════════════════════════
// 4. PARTNER ANALYTICS
// ══════════════════════════════════════════════
$db->partner_analytics->drop();
$db->partner_analytics->insertOne([
    'partner_id' => 1,
    'partner'    => ['name' => 'Rajesh Kumar', 'id' => 'FBP-KUM001'],
    'kpis' => [
        'avg_yield'      => 14.2,
        'repayment_rate' => 98.2,
        'npa_rate'       => 0.8,
        'aum'            => 12400000,
    ],
    'portfolio_mix' => [
        ['label' => 'Personal',  'pct' => 45, 'color' => '#1a6b3c'],
        ['label' => 'Business',  'pct' => 35, 'color' => '#c49a1a'],
        ['label' => 'Home',      'pct' => 15, 'color' => '#1e3a5f'],
        ['label' => 'Instant',   'pct' => 5,  'color' => '#6c757d'],
    ],
    'performance_metrics' => [
        'disbursement_rate'    => 2.84,
        'on_time_payment_rate' => 97.6,
        'avg_approval_days'    => 1.8,
        'repeat_borrowers'     => 38,
    ],
    'cohorts' => [
        ['quarter' => 'Q3 2025', 'loans' => 98,  'disbursed' => 4.2,  'repayment' => 97.8, 'yield' => 14.1, 'npa' => 0.9],
        ['quarter' => 'Q4 2025', 'loans' => 115, 'disbursed' => 5.8,  'repayment' => 98.1, 'yield' => 14.3, 'npa' => 0.8],
        ['quarter' => 'Q1 2026', 'loans' => 133, 'disbursed' => 7.1,  'repayment' => 98.4, 'yield' => 14.4, 'npa' => 0.7],
    ],
    'ai_insights' => [
        'Your highest-performing segment is Business Loans at 14.8% yield with 0.6% NPA.',
        'Consider increasing exposure to Tier-2 city borrowers — 22% lower NPA in your cohort.',
        'March bookings are trending 12% above February. On track for best quarter.',
    ],
    'updated_at' => new UTCDateTime(),
]);
echo "✅ partner_analytics — seeded\n";

// ══════════════════════════════════════════════
// 5. PARTNER BORROWERS
// ══════════════════════════════════════════════
$db->partner_borrowers->drop();
$db->partner_borrowers->insertMany([
    [
        'partner_id'   => 1,
        'loan_id'      => 'LN-2024-001',
        'name'         => 'Priya Sharma',
        'email'        => 'priya.sharma@email.com',
        'phone'        => '9876543210',
        'loan_type'    => 'Personal Loan',
        'amount'       => 150000,
        'outstanding'  => 112000,
        'emi'          => 8500,
        'cibil'        => 742,
        'status'       => 'active',
        'dpd'          => 0,
        'next_due'     => '2026-04-05',
        'disbursed_at' => '2025-10-01',
        'last_action'  => null,
    ],
    [
        'partner_id'   => 1,
        'loan_id'      => 'LN-2024-002',
        'name'         => 'Arjun Mehta',
        'email'        => 'arjun.m@email.com',
        'phone'        => '9123456780',
        'loan_type'    => 'Business Loan',
        'amount'       => 500000,
        'outstanding'  => 450000,
        'emi'          => 18000,
        'cibil'        => 698,
        'status'       => 'overdue',
        'dpd'          => 12,
        'next_due'     => '2026-03-12',
        'disbursed_at' => '2025-11-15',
        'last_action'  => 'alert_sent',
    ],
    [
        'partner_id'   => 1,
        'loan_id'      => 'LN-2025-010',
        'name'         => 'Sneha Nambiar',
        'email'        => 'sneha.n@email.com',
        'phone'        => '9988776655',
        'loan_type'    => 'Home Loan',
        'amount'       => 2500000,
        'outstanding'  => 2430000,
        'emi'          => 22000,
        'cibil'        => 778,
        'status'       => 'active',
        'dpd'          => 0,
        'next_due'     => '2026-04-01',
        'disbursed_at' => '2026-01-20',
        'last_action'  => null,
    ],
]);
echo "✅ partner_borrowers — 3 records seeded\n";

// ══════════════════════════════════════════════
// 6. PARTNER DEAL MATCHES
// ══════════════════════════════════════════════
$db->partner_matches->drop();
$db->partner_matches->insertMany([
    [
        'partner_id'  => 1,
        'loan_id'     => 'DEAL-2026-001',
        'applicant'   => 'Ravi Teja',
        'loan_type'   => 'Business Loan',
        'amount'      => 350000,
        'cibil'       => 754,
        'income'      => 75000,
        'tenure'      => 36,
        'risk_grade'  => 'B+',
        'yield'       => 14.8,
        'match_score' => 94,
        'status'      => 'available',
        'created_at'  => new UTCDateTime(),
    ],
    [
        'partner_id'  => 1,
        'loan_id'     => 'DEAL-2026-002',
        'applicant'   => 'Lakshmi Devi',
        'loan_type'   => 'Personal Loan',
        'amount'      => 120000,
        'cibil'       => 720,
        'income'      => 45000,
        'tenure'      => 24,
        'risk_grade'  => 'B',
        'yield'       => 13.5,
        'match_score' => 88,
        'status'      => 'available',
        'created_at'  => new UTCDateTime(),
    ],
]);
echo "✅ partner_matches — 2 deal records seeded\n";

// ══════════════════════════════════════════════
// 7. PARTNER RISK INSIGHTS
// ══════════════════════════════════════════════
$db->partner_risk_insights->drop();
$db->partner_risk_insights->insertOne([
    'partner_id' => 1,
    'overall_risk_score' => 72,
    'risk_grade'         => 'B+',
    'npa_rate'           => 0.8,
    'overdue_borrowers'  => 4,
    'concentration_risk' => 'Low',
    'interest_rate_risk' => 'Medium',
    'liquidity_risk'     => 'Low',
    'risk_factors' => [
        ['factor' => 'NPA Rate',           'value' => '0.8%',  'status' => 'good',    'note' => 'Well below 2% threshold'],
        ['factor' => 'Overdue > 30 DPD',   'value' => '3',     'status' => 'warning', 'note' => 'Monitor closely'],
        ['factor' => 'Concentration',      'value' => '45%',   'status' => 'good',    'note' => 'Personal Loan segment'],
        ['factor' => 'Rate Exposure',      'value' => 'Fixed',  'status' => 'good',   'note' => '82% fixed-rate book'],
    ],
    'recommendations' => [
        'Diversify into Home Loans to reduce personal loan concentration.',
        'Send proactive reminders to 3 borrowers approaching 30 DPD.',
        'Review 2 accounts flagged for income inconsistency.',
    ],
    'updated_at' => new UTCDateTime(),
]);
echo "✅ partner_risk_insights — seeded\n";

// ══════════════════════════════════════════════
// 8. PARTNER PROFILE
// ══════════════════════════════════════════════
$db->partner_profile->drop();
$db->partner_profile->insertOne([
    'partner_id'       => 1,
    'name'             => 'Rajesh Kumar',
    'email'            => 'rajesh.kumar@email.com',
    'phone'            => '9876500001',
    'city'             => 'Hyderabad',
    'state'            => 'Telangana',
    'partner_type'     => 'Individual DSA',
    'reference_id'     => 'FBP-KUM001',
    'status'           => 'active',
    'joined'           => '2024-06-01',
    'profile_complete' => 85,
    'updated_at'       => new UTCDateTime(),
]);
echo "✅ partner_profile — seeded\n";

// ══════════════════════════════════════════════
// 9. PARTNER BANKS
// ══════════════════════════════════════════════
$db->partner_banks->drop();
$db->partner_banks->insertMany([
    [
        'partner_id'     => 1,
        'bank_name'      => 'HDFC Bank',
        'account_holder' => 'Rajesh Kumar',
        'account_no'     => '****4521',
        'ifsc'           => 'HDFC0001234',
        'is_primary'     => true,
        'verified'       => true,
    ],
]);
echo "✅ partner_banks — 1 bank seeded\n";

// ══════════════════════════════════════════════
// 10. PARTNER NOTIFICATIONS
// ══════════════════════════════════════════════
$db->partner_notifications->drop();
$db->partner_notifications->insertOne([
    'partner_id'       => 1,
    'email_new_match'  => true,
    'email_repayment'  => true,
    'email_overdue'    => true,
    'sms_settlement'   => false,
    'push_alerts'      => true,
]);
echo "✅ partner_notifications — seeded\n";

// ══════════════════════════════════════════════
// 11. PARTNER KYC
// ══════════════════════════════════════════════
$db->partner_kyc->drop();
$db->partner_kyc->insertOne([
    'partner_id'   => 1,
    'pan'          => 'ABCDE1234F',
    'aadhaar_last4'=> '5678',
    'gstin'        => '',
    'kyc_verified' => true,
    'verified_at'  => '2024-06-10',
]);
echo "✅ partner_kyc — seeded\n";

// ══════════════════════════════════════════════
// 12. PARTNER PREFERENCES
// ══════════════════════════════════════════════
$db->partner_preferences->drop();
$db->partner_preferences->insertOne([
    'partner_id'     => 1,
    'language'       => 'English',
    'currency'       => 'INR',
    'date_format'    => 'DD/MM/YYYY',
    'theme'          => 'light',
    'auto_fund'      => false,
    'min_cibil'      => 680,
    'preferred_types'=> ['Personal Loan', 'Business Loan'],
]);
echo "✅ partner_preferences — seeded\n";

// ══════════════════════════════════════════════
// 13. DEMO USER (for virtual bank)
// ══════════════════════════════════════════════
$existingUser = $db->users->findOne(['email' => 'rahul@fundbee.in']);
if (!$existingUser) {
    $db->users->insertOne([
        'user_id'      => 'user_demo_001',
        'name'         => 'Rahul Sharma',
        'email'        => 'rahul@fundbee.in',
        'phone'        => '9876543210',
        'password_hash'=> password_hash('password123', PASSWORD_DEFAULT),
        'status'       => 'active',
        'created_at'   => new UTCDateTime(),
    ]);
    echo "✅ users — demo user created (rahul@fundbee.in / password123)\n";
} else {
    echo "ℹ️  users — demo user already exists, skipped\n";
}

echo "\n🎉 All partner collections seeded successfully!\n";
echo "\nCollections ready:\n";
$collections = [
    'partner_dashboard_kpis', 'partner_earnings', 'partner_portfolio',
    'partner_analytics', 'partner_borrowers', 'partner_matches',
    'partner_risk_insights', 'partner_profile', 'partner_banks',
    'partner_notifications', 'partner_kyc', 'partner_preferences', 'users'
];
foreach ($collections as $col) {
    echo "  → $col: " . $db->$col->countDocuments() . " document(s)\n";
}
echo "</pre>";
?>
