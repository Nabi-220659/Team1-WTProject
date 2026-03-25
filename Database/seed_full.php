<?php
/**
 * seed_full.php — Full Database Seeder for FUNDBEE
 * ===================================================
 * Run once via browser or CLI:  php Database/seed_full.php
 *
 * Creates:
 *  1. Demo user  (arjun.sharma@email.com / password123)
 *  2. Admin user (admin@fundbee.in / admin123)
 *  3. Sample loan applications linked to the demo user
 *  4. Sample notifications
 *  5. Sample admin notification (new loan alert)
 *
 * Safe to run multiple times — checks for existing records first.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Backend/index/config/db.php';

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

$db = getDB();
echo "<pre>\n";

// ─────────────────────────────────────────────────────
// 1. DEMO USER
// ─────────────────────────────────────────────────────
$users = $db->selectCollection('users');
$existing = $users->findOne(['email' => 'arjun.sharma@email.com']);
if ($existing) {
    $userId = (string)$existing['_id'];
    echo "✅ Demo user already exists: arjun.sharma@email.com (ID: $userId)\n";
} else {
    $uid = $users->insertOne([
        'first_name'     => 'Arjun',
        'last_name'      => 'Sharma',
        'name'           => 'Arjun Sharma',
        'initials'       => 'AS',
        'email'          => 'arjun.sharma@email.com',
        'phone'          => '9876543210',
        'password_hash'  => password_hash('password123', PASSWORD_DEFAULT),
        'cibil_score'    => 745,
        'kyc_status'     => 'verified',
        'pan_verified'   => true,
        'aadhaar_linked' => true,
        'selfie_done'    => true,
        'video_kyc_done' => false,
        'city'           => 'Mumbai',
        'address'        => '42 Marine Drive, Mumbai 400001',
        'created_at'     => new UTCDateTime(),
        'updated_at'     => new UTCDateTime(),
    ])->getInsertedId();
    $userId = (string)$uid;
    echo "✅ Created demo user: arjun.sharma@email.com / password123 (ID: $userId)\n";
}

// ─────────────────────────────────────────────────────
// 2. ADMIN USER
// ─────────────────────────────────────────────────────
$admins = $db->selectCollection('admins');
if ($admins->countDocuments(['email' => 'admin@fundbee.in']) === 0) {
    $admins->insertOne([
        'email'         => 'admin@fundbee.in',
        'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        'name'          => 'FUNDBEE Admin',
        'created_at'    => new UTCDateTime(),
    ]);
    echo "✅ Created admin: admin@fundbee.in / admin123\n";
} else {
    echo "✅ Admin already exists: admin@fundbee.in\n";
}

// ─────────────────────────────────────────────────────
// 3. SAMPLE LOAN APPLICATIONS
// ─────────────────────────────────────────────────────
$loans = $db->selectCollection('loan_applications');
if ($loans->countDocuments(['user_id' => $userId]) === 0) {
    $now = new DateTime();

    $loanData = [
        [
            'application_id' => '#FB-202410001',
            'loan_type'      => 'personal',
            'amount'         => 100000,
            'tenure_months'  => 24,
            'interest_rate'  => '10.5% p.a.',
            'emi_amount'     => 4642,
            'status'         => 'active',
            'total_repaid'   => 41778,
            'next_emi_date'  => (clone $now)->modify('+5 days')->format('M d, Y'),
            'disbursed_at'   => new UTCDateTime(strtotime('-9 months') * 1000),
            'submitted_at'   => new UTCDateTime(strtotime('-9 months - 3 days') * 1000),
            'name'           => 'Arjun Sharma',
            'phone'          => '9876543210',
            'purpose'        => 'Home renovation',
            'monthly_income' => 60000,
            'employment_type'=> 'Salaried',
        ],
        [
            'application_id' => '#FB-202310189',
            'loan_type'      => 'home',
            'amount'         => 2500000,
            'tenure_months'  => 120,
            'interest_rate'  => '8.5% p.a.',
            'emi_amount'     => 30987,
            'status'         => 'active',
            'total_repaid'   => 371844,
            'next_emi_date'  => (clone $now)->modify('+1 days')->format('M d, Y'),
            'disbursed_at'   => new UTCDateTime(strtotime('-12 months') * 1000),
            'submitted_at'   => new UTCDateTime(strtotime('-12 months - 5 days') * 1000),
            'name'           => 'Arjun Sharma',
            'phone'          => '9876543210',
            'purpose'        => 'Purchase of 2BHK apartment',
            'monthly_income' => 60000,
            'employment_type'=> 'Salaried',
        ],
        [
            'application_id' => '#FB-202610312',
            'loan_type'      => 'business',
            'amount'         => 250000,
            'tenure_months'  => 36,
            'interest_rate'  => '12% p.a.',
            'emi_amount'     => 8306,
            'status'         => 'pending',
            'total_repaid'   => 0,
            'next_emi_date'  => '',
            'submitted_at'   => new UTCDateTime(strtotime('-2 days') * 1000),
            'name'           => 'Arjun Sharma',
            'phone'          => '9876543210',
            'purpose'        => 'Working capital for retail business',
            'monthly_income' => 60000,
            'employment_type'=> 'Business Owner',
        ],
        [
            'application_id' => '#FB-202119874',
            'loan_type'      => 'education',
            'amount'         => 75000,
            'tenure_months'  => 18,
            'interest_rate'  => '9% p.a.',
            'emi_amount'     => 4451,
            'status'         => 'closed',
            'total_repaid'   => 75000,
            'next_emi_date'  => '',
            'disbursed_at'   => new UTCDateTime(strtotime('-3 years') * 1000),
            'submitted_at'   => new UTCDateTime(strtotime('-3 years - 1 week') * 1000),
            'closed_at'      => new UTCDateTime(strtotime('-18 months') * 1000),
            'name'           => 'Arjun Sharma',
            'phone'          => '9876543210',
            'purpose'        => 'MBA tuition fees',
            'monthly_income' => 45000,
            'employment_type'=> 'Salaried',
        ],
    ];

    foreach ($loanData as $l) {
        $l['user_id'] = $userId;
        $loans->insertOne($l);
    }
    echo "✅ Created 4 sample loan applications for demo user\n";
} else {
    echo "✅ Loan applications already exist for demo user\n";
}

// ─────────────────────────────────────────────────────
// 4. USER NOTIFICATIONS
// ─────────────────────────────────────────────────────
$notifs = $db->selectCollection('notifications');
if ($notifs->countDocuments(['user_id' => $userId]) === 0) {
    $notifs->insertMany([
        [
            'user_id'    => $userId,
            'title'      => '🎉 Welcome to FUNDBEE!',
            'body'       => 'Your account is active. Complete KYC to unlock higher loan limits.',
            'type'       => 'general',
            'read'       => true,
            'created_at' => new UTCDateTime(strtotime('-1 year') * 1000),
        ],
        [
            'user_id'    => $userId,
            'title'      => '✅ Personal Loan Disbursed',
            'body'       => 'Your Personal Loan of ₹1,00,000 (App #FB-202410001) has been disbursed to your bank account.',
            'type'       => 'loan',
            'read'       => true,
            'created_at' => new UTCDateTime(strtotime('-9 months') * 1000),
        ],
        [
            'user_id'    => $userId,
            'title'      => '📅 EMI Reminder — Home Loan',
            'body'       => 'Your Home Loan EMI of ₹30,987 is due in 3 days. Ensure sufficient balance.',
            'type'       => 'emi',
            'read'       => false,
            'created_at' => new UTCDateTime(strtotime('-1 day') * 1000),
        ],
        [
            'user_id'    => $userId,
            'title'      => '🕐 Business Loan Under Review',
            'body'       => 'Your Business Loan application #FB-202610312 has been received and is under review.',
            'type'       => 'loan',
            'read'       => false,
            'created_at' => new UTCDateTime(strtotime('-2 days') * 1000),
        ],
    ]);
    echo "✅ Created sample notifications for demo user\n";
} else {
    echo "✅ Notifications already exist for demo user\n";
}

// ─────────────────────────────────────────────────────
// 5. ADMIN NOTIFICATION (new loan alert)
// ─────────────────────────────────────────────────────
$adminNotifs = $db->selectCollection('admin_notifications');
if ($adminNotifs->countDocuments([]) === 0) {
    $adminNotifs->insertMany([
        [
            'type'         => 'new_loan_application',
            'title'        => '📋 New Loan Application',
            'message'      => '#FB-202610312 — Business Loan of ₹2,50,000 from Arjun Sharma',
            'reference_id' => '#FB-202610312',
            'user_id'      => $userId,
            'user_name'    => 'Arjun Sharma',
            'user_email'   => 'arjun.sharma@email.com',
            'user_phone'   => '9876543210',
            'loan_type'    => 'business',
            'amount'       => 250000,
            'emi_amount'   => 8306,
            'read'         => false,
            'created_at'   => new UTCDateTime(strtotime('-2 days') * 1000),
        ],
        [
            'type'         => 'new_loan_application',
            'title'        => '📋 New Loan Application',
            'message'      => '#FB-202410001 — Personal Loan of ₹1,00,000 from Arjun Sharma',
            'reference_id' => '#FB-202410001',
            'user_id'      => $userId,
            'user_name'    => 'Arjun Sharma',
            'user_email'   => 'arjun.sharma@email.com',
            'user_phone'   => '9876543210',
            'loan_type'    => 'personal',
            'amount'       => 100000,
            'emi_amount'   => 4642,
            'read'         => true,
            'created_at'   => new UTCDateTime(strtotime('-9 months') * 1000),
        ],
    ]);
    echo "✅ Created admin notifications\n";
} else {
    echo "✅ Admin notifications already exist\n";
}

// ─────────────────────────────────────────────────────
// 6. CREATE MongoDB INDEXES for performance
// ─────────────────────────────────────────────────────
try {
    $db->selectCollection('users')->createIndex(['email' => 1], ['unique' => true]);
    $db->selectCollection('users')->createIndex(['phone' => 1], ['unique' => true]);
    $db->selectCollection('user_sessions')->createIndex(['token' => 1]);
    $db->selectCollection('user_sessions')->createIndex(['expires_at' => 1]);
    $db->selectCollection('loan_applications')->createIndex(['user_id' => 1]);
    $db->selectCollection('loan_applications')->createIndex(['application_id' => 1]);
    $db->selectCollection('notifications')->createIndex(['user_id' => 1, 'read' => 1]);
    $db->selectCollection('admin_notifications')->createIndex(['read' => 1]);
    $db->selectCollection('admin_notifications')->createIndex(['created_at' => -1]);
    echo "✅ MongoDB indexes created\n";
} catch (Exception $e) {
    echo "⚠️  Index creation (may already exist): " . $e->getMessage() . "\n";
}

echo "\n════════════════════════════════════════\n";
echo "🚀 SEED COMPLETE\n";
echo "════════════════════════════════════════\n";
echo "Demo User:   arjun.sharma@email.com / password123\n";
echo "Admin Login: admin@fundbee.in / admin123\n";
echo "Admin URL:   Frontend/admin.html\n";
echo "User URL:    user/user1.html  (login first)\n";
echo "</pre>\n";
?>
