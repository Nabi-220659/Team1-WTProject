<?php
/**
 * seed_user_dashboard.php  —  Seeds ALL collections needed by the User Dashboard
 *
 * Run once: php Database/seed_user_dashboard.php
 * Or visit: http://localhost/Loan-Management-System/Database/seed_user_dashboard.php
 *
 * Collections created / seeded:
 *   users                    Demo user with CIBIL score, profile
 *   loan_applications        4 loans (active / pending / closed) with EMI data
 *   emi_payments             Historical EMI payment records
 *   user_documents           Sample uploaded documents
 *   notifications            Pre-seeded notifications (unread)
 *   user_bank_accounts       Linked bank accounts
 *   advisor_callbacks        (empty — created on demand)
 *   advisor_messages         (empty — created on demand)
 *   video_kyc_requests       (empty — created on demand)
 *   user_sessions            (empty — created on login)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;

$client = new Client('mongodb://localhost:27017');
$db     = $client->selectDatabase('fundbee_db');

echo "<pre style='font-family:monospace;background:#111;color:#0f0;padding:20px;font-size:13px'>";
echo "🌱 FUNDBEE User Dashboard Seeder\n";
echo str_repeat('─', 50) . "\n\n";

// ══════════════════════════════════════════════
// 1. DEMO USER
// ══════════════════════════════════════════════
$db->users->deleteMany(['email' => 'arjun.sharma@email.com']);
$userResult = $db->users->insertOne([
    'name'           => 'Arjun Sharma',
    'email'          => 'arjun.sharma@email.com',
    'phone'          => '+91 98765 43210',
    'password_hash'  => password_hash('password123', PASSWORD_DEFAULT),
    'dob'            => '1992-08-14',
    'city'           => 'Bengaluru',
    'address'        => '12, MG Road, Bengaluru, Karnataka — 560001',
    'cibil_score'    => 745,
    'kyc_status'     => 'verified',
    'pan_verified'   => true,
    'aadhaar_linked' => true,
    'selfie_done'    => true,
    'video_kyc_done' => false,
    'kyc_message'    => 'Your KYC is verified. Complete Video KYC for highest loan limits.',
    'notif_emi_reminder' => true,
    'notif_loan_update'  => true,
    'notif_offers'       => false,
    'notif_sms'          => true,
    'notif_push'         => true,
    'status'         => 'active',
    'created_at'     => new UTCDateTime(strtotime('2025-01-12') * 1000),
]);
$userId = (string)$userResult->getInsertedId();
echo "✅ users — demo user created (arjun.sharma@email.com / password123)\n";
echo "   user_id: $userId\n\n";

// ══════════════════════════════════════════════
// 2. LOAN APPLICATIONS
// ══════════════════════════════════════════════
$db->loan_applications->deleteMany(['user_id' => $userId]);

// Personal Loan — Active, 18% repaid
$db->loan_applications->insertOne([
    'application_id' => '#FB-20241',
    'user_id'        => $userId,
    'loan_type'      => 'personal',
    'amount'         => 100000,
    'tenure_months'  => 24,
    'interest_rate'  => '10.5% p.a.',
    'emi_amount'     => 5200,
    'total_repaid'   => 18200,
    'outstanding'    => 81800,
    'name'           => 'Arjun Sharma',
    'phone'          => '9876543210',
    'purpose'        => 'Home renovation',
    'monthly_income' => 75000,
    'employment_type'=> 'Salaried',
    'status'         => 'active',
    'next_emi_date'  => date('Y-m-d', strtotime('2026-04-05')),
    'submitted_at'   => new UTCDateTime(strtotime('2026-01-01') * 1000),
    'disbursed_at'   => new UTCDateTime(strtotime('2026-01-10') * 1000),
    'kyc_passed_at'  => new UTCDateTime(strtotime('2026-01-08') * 1000),
    'docs_verified_at'  => new UTCDateTime(strtotime('2026-01-07') * 1000),
    'credit_assessed_at'=> new UTCDateTime(strtotime('2026-01-09') * 1000),
]);

// Home Loan — Active, 10% repaid
$db->loan_applications->insertOne([
    'application_id' => '#FB-20189',
    'user_id'        => $userId,
    'loan_type'      => 'home',
    'amount'         => 800000,
    'tenure_months'  => 120,
    'interest_rate'  => '8.5% p.a.',
    'emi_amount'     => 6800,
    'total_repaid'   => 82000,
    'outstanding'    => 718000,
    'name'           => 'Arjun Sharma',
    'phone'          => '9876543210',
    'purpose'        => 'Purchase of residential property',
    'monthly_income' => 75000,
    'employment_type'=> 'Salaried',
    'status'         => 'active',
    'next_emi_date'  => date('Y-m-d', strtotime('2026-04-01')),
    'submitted_at'   => new UTCDateTime(strtotime('2025-10-15') * 1000),
    'disbursed_at'   => new UTCDateTime(strtotime('2025-11-01') * 1000),
    'kyc_passed_at'  => new UTCDateTime(strtotime('2025-10-20') * 1000),
    'docs_verified_at'  => new UTCDateTime(strtotime('2025-10-18') * 1000),
    'credit_assessed_at'=> new UTCDateTime(strtotime('2025-10-22') * 1000),
]);

// Business Loan — Pending review
$db->loan_applications->insertOne([
    'application_id' => '#FB-20312',
    'user_id'        => $userId,
    'loan_type'      => 'business',
    'amount'         => 250000,
    'tenure_months'  => 36,
    'interest_rate'  => '12.0% p.a.',
    'emi_amount'     => 0,
    'total_repaid'   => 0,
    'outstanding'    => 250000,
    'name'           => 'Arjun Sharma',
    'phone'          => '9876543210',
    'purpose'        => 'Business expansion',
    'monthly_income' => 75000,
    'employment_type'=> 'Self-Employed',
    'status'         => 'pending',
    'next_emi_date'  => '',
    'submitted_at'   => new UTCDateTime(strtotime('2026-03-15') * 1000),
    'docs_verified_at' => new UTCDateTime(strtotime('2026-03-17') * 1000),
]);

// Education Loan — Closed / Fully repaid
$db->loan_applications->insertOne([
    'application_id' => '#FB-19874',
    'user_id'        => $userId,
    'loan_type'      => 'education',
    'amount'         => 75000,
    'tenure_months'  => 18,
    'interest_rate'  => '9.0% p.a.',
    'emi_amount'     => 0,
    'total_repaid'   => 75000,
    'outstanding'    => 0,
    'name'           => 'Arjun Sharma',
    'phone'          => '9876543210',
    'purpose'        => 'Post-graduation course fees',
    'monthly_income' => 60000,
    'employment_type'=> 'Salaried',
    'status'         => 'closed',
    'next_emi_date'  => '',
    'submitted_at'   => new UTCDateTime(strtotime('2024-06-01') * 1000),
    'disbursed_at'   => new UTCDateTime(strtotime('2024-06-05') * 1000),
    'kyc_passed_at'  => new UTCDateTime(strtotime('2024-06-03') * 1000),
    'docs_verified_at'  => new UTCDateTime(strtotime('2024-06-02') * 1000),
    'credit_assessed_at'=> new UTCDateTime(strtotime('2024-06-04') * 1000),
    'closed_at'      => new UTCDateTime(strtotime('2025-12-20') * 1000),
]);

echo "✅ loan_applications — 4 loans seeded (active x2, pending x1, closed x1)\n";

// ══════════════════════════════════════════════
// 3. EMI PAYMENT HISTORY
// ══════════════════════════════════════════════
$db->emi_payments->deleteMany(['user_id' => $userId]);

// Personal Loan: 3 EMIs paid
for ($i = 1; $i <= 3; $i++) {
    $db->emi_payments->insertOne([
        'loan_id' => '#FB-20241',
        'user_id' => $userId,
        'emi_no'  => $i,
        'amount'  => 5200,
        'method'  => 'upi',
        'paid_at' => new UTCDateTime(strtotime("2026-0{$i}-05") * 1000),
    ]);
}

// Home Loan: 4 EMIs paid (Nov–Feb)
$months = [11,12,1,2];
foreach ($months as $idx => $m) {
    $yr   = $m >= 11 ? 2025 : 2026;
    $date = sprintf('%04d-%02d-01', $yr, $m);
    $db->emi_payments->insertOne([
        'loan_id' => '#FB-20189',
        'user_id' => $userId,
        'emi_no'  => $idx + 1,
        'amount'  => 6800,
        'method'  => 'net_banking',
        'paid_at' => new UTCDateTime(strtotime($date) * 1000),
    ]);
}

// Education Loan: all 18 EMIs paid
for ($i = 1; $i <= 18; $i++) {
    $db->emi_payments->insertOne([
        'loan_id' => '#FB-19874',
        'user_id' => $userId,
        'emi_no'  => $i,
        'amount'  => 4250,
        'method'  => 'auto_debit',
        'paid_at' => new UTCDateTime(strtotime("2024-06-{$i}") * 1000 + ($i * 30 * 86400 * 1000)),
    ]);
}

echo "✅ emi_payments — 25 payment records seeded\n";

// ══════════════════════════════════════════════
// 4. USER DOCUMENTS
// ══════════════════════════════════════════════
$db->user_documents->deleteMany(['user_id' => $userId]);
$db->user_documents->insertMany([
    ['user_id'=>$userId,'file_name'=>'aadhar_arjun.pdf','original_name'=>'Aadhar Card — Arjun Sharma.pdf','category'=>'kyc','loan_ref'=>'','size_kb'=>420,'mime_type'=>'application/pdf','status'=>'verified','uploaded_at'=> new UTCDateTime(strtotime('2026-01-08') * 1000)],
    ['user_id'=>$userId,'file_name'=>'pan_arjun.pdf','original_name'=>'PAN Card — Arjun Sharma.pdf','category'=>'kyc','loan_ref'=>'','size_kb'=>310,'mime_type'=>'application/pdf','status'=>'verified','uploaded_at'=> new UTCDateTime(strtotime('2026-01-08') * 1000)],
    ['user_id'=>$userId,'file_name'=>'salary_slip_jan.pdf','original_name'=>'Salary Slip Jan 2026.pdf','category'=>'income','loan_ref'=>'#FB-20241','size_kb'=>820,'mime_type'=>'application/pdf','status'=>'verified','uploaded_at'=> new UTCDateTime(strtotime('2026-01-09') * 1000)],
    ['user_id'=>$userId,'file_name'=>'bank_stmt_3m.pdf','original_name'=>'Bank Statement 3 Months.pdf','category'=>'income','loan_ref'=>'#FB-20241','size_kb'=>1250,'mime_type'=>'application/pdf','status'=>'verified','uploaded_at'=> new UTCDateTime(strtotime('2026-01-09') * 1000)],
    ['user_id'=>$userId,'file_name'=>'property_doc.pdf','original_name'=>'Property Agreement — Home Loan.pdf','category'=>'loan','loan_ref'=>'#FB-20189','size_kb'=>2100,'mime_type'=>'application/pdf','status'=>'uploaded','uploaded_at'=> new UTCDateTime(strtotime('2025-10-18') * 1000)],
    ['user_id'=>$userId,'file_name'=>'noc_education.pdf','original_name'=>'NOC — Education Loan #FB-19874.pdf','category'=>'loan','loan_ref'=>'#FB-19874','size_kb'=>180,'mime_type'=>'application/pdf','status'=>'uploaded','uploaded_at'=> new UTCDateTime(strtotime('2025-12-21') * 1000)],
]);
echo "✅ user_documents — 6 documents seeded\n";

// ══════════════════════════════════════════════
// 5. NOTIFICATIONS
// ══════════════════════════════════════════════
$db->notifications->deleteMany(['user_id' => $userId]);
$db->notifications->insertMany([
    ['user_id'=>$userId,'title'=>'⏰ EMI Due in 11 Days','body'=>'Your Home Loan EMI of ₹6,800 is due on Apr 01, 2026. Ensure sufficient balance.','type'=>'emi','read'=>false,'created_at'=> new UTCDateTime()],
    ['user_id'=>$userId,'title'=>'📋 Business Loan Under Review','body'=>'Your Business Loan application #FB-20312 is currently in credit assessment. Expected decision by Apr 1, 2026.','type'=>'loan','read'=>false,'created_at'=> new UTCDateTime(strtotime('-1 day') * 1000)],
    ['user_id'=>$userId,'title'=>'✅ EMI Payment Confirmed','body'=>'Personal Loan EMI #3 of ₹5,200 has been processed successfully on Mar 05, 2026.','type'=>'emi','read'=>false,'created_at'=> new UTCDateTime(strtotime('-5 days') * 1000)],
    ['user_id'=>$userId,'title'=>'🎁 Pre-approved Offer','body'=>'Congratulations! You are pre-approved for an Instant Loan of up to ₹1,00,000 at 11.5% p.a.','type'=>'promo','read'=>true,'created_at'=> new UTCDateTime(strtotime('-7 days') * 1000)],
    ['user_id'=>$userId,'title'=>'📄 NOC Ready','body'=>'Your No Objection Certificate for Education Loan #FB-19874 is available for download.','type'=>'loan','read'=>true,'created_at'=> new UTCDateTime(strtotime('-90 days') * 1000)],
    ['user_id'=>$userId,'title'=>'✅ KYC Verified','body'=>'Your KYC documents have been verified. You now have access to all loan products.','type'=>'kyc','read'=>true,'created_at'=> new UTCDateTime(strtotime('-100 days') * 1000)],
]);
echo "✅ notifications — 6 notifications seeded (3 unread)\n";

// ══════════════════════════════════════════════
// 6. BANK ACCOUNTS
// ══════════════════════════════════════════════
$db->user_bank_accounts->deleteMany(['user_id' => $userId]);
$db->user_bank_accounts->insertMany([
    ['user_id'=>$userId,'bank_name'=>'HDFC Bank','account_holder'=>'Arjun Sharma','account_last4'=>'4521','ifsc'=>'HDFC0001234','is_primary'=>true,'verified'=>true,'added_at'=> new UTCDateTime(strtotime('2026-01-08') * 1000)],
    ['user_id'=>$userId,'bank_name'=>'State Bank of India','account_holder'=>'Arjun Sharma','account_last4'=>'7788','ifsc'=>'SBIN0001234','is_primary'=>false,'verified'=>true,'added_at'=> new UTCDateTime(strtotime('2026-02-01') * 1000)],
]);
echo "✅ user_bank_accounts — 2 accounts seeded\n";

// ══════════════════════════════════════════════
// 7. ENSURE EMPTY COLLECTIONS EXIST
// ══════════════════════════════════════════════
foreach (['advisor_callbacks','advisor_messages','video_kyc_requests','user_sessions'] as $col) {
    try { $db->createCollection($col); } catch (Exception $e) { /* already exists */ }
}
echo "✅ advisor_callbacks, advisor_messages, video_kyc_requests, user_sessions — collections ready\n";

// ══════════════════════════════════════════════
// SUMMARY
// ══════════════════════════════════════════════
echo "\n🎉 User Dashboard seed complete!\n\n";
echo "Demo credentials:\n";
echo "  Email   : arjun.sharma\@email.com\n";
echo "  Password: password123\n";
echo "  user_id : $userId\n\n";
echo "Collections & counts:\n";
$cols = ['users','loan_applications','emi_payments','user_documents','notifications','user_bank_accounts'];
foreach ($cols as $c) {
    echo "  → $c: " . $db->$c->countDocuments(['user_id' => $userId]) . " docs\n";
}
echo "</pre>";
?>
