<?php
/**
 * seed_mongo.php — Run this ONCE to seed MongoDB collections
 *
 * How to run (in PowerShell from project root):
 *   php Database/seed_mongo.php
 *
 * Or visit in browser (while XAMPP is running):
 *   http://localhost/Loan-Management-System/Database/seed_mongo.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;

$client = new Client('mongodb://localhost:27017');
$db     = $client->selectDatabase('fundbee_db');

echo "<pre style='font-family:monospace; background:#111; color:#0f0; padding:20px;'>";
echo "🌱 FUNDBEE MongoDB Seeder\n";
echo str_repeat('─', 40) . "\n\n";

// ════════════════════════════════════════
// 1. SITE STATS
// ════════════════════════════════════════
$db->site_stats->drop();

$db->site_stats->insertMany([
    ['stat_key' => 'experience', 'stat_value' => '10+',  'stat_label' => 'Years of Experience', 'display_order' => 1],
    ['stat_key' => 'downloads',  'stat_value' => '2M+',  'stat_label' => 'App Downloads',        'display_order' => 2],
    ['stat_key' => 'loans',      'stat_value' => '50K+', 'stat_label' => 'Loans Approved',       'display_order' => 3],
    ['stat_key' => 'customers',  'stat_value' => '25K+', 'stat_label' => 'Happy Customers',      'display_order' => 4],
]);
echo "✅ site_stats — " . $db->site_stats->countDocuments() . " documents inserted\n";

// ════════════════════════════════════════
// 2. LOAN PRODUCTS
// ════════════════════════════════════════
$db->loan_products->drop();

$db->loan_products->insertMany([
    [
        'name'          => 'Personal Loan',
        'icon'          => '👤',
        'description'   => 'Instant approvals with minimal documentation. Fund your dreams without the wait.',
        'interest_rate' => 'Starting at 10.5% p.a.',
        'badge'         => 'Popular',
        'image_path'    => '/Frontend/images/img2.jpg',
        'is_active'     => true,
        'display_order' => 1
    ],
    [
        'name'          => 'Business Loan',
        'icon'          => '🏢',
        'description'   => 'Fuel your enterprise growth with tailored capital and flexible repayment options.',
        'interest_rate' => 'Starting at 12% p.a.',
        'badge'         => null,
        'image_path'    => '/Frontend/images/img3.jpg',
        'is_active'     => true,
        'display_order' => 2
    ],
    [
        'name'          => 'Home Loan',
        'icon'          => '🏠',
        'description'   => 'Make your home ownership dream a reality with competitive rates and long tenures.',
        'interest_rate' => 'Starting at 8.5% p.a.',
        'badge'         => 'Low Rate',
        'image_path'    => '/Frontend/images/img4.jpg',
        'is_active'     => true,
        'display_order' => 3
    ],
    [
        'name'          => 'Instant Loan',
        'icon'          => '⚡',
        'description'   => 'Disbursal in under 24 hours. For those moments when every minute counts.',
        'interest_rate' => 'Starting at 14% p.a.',
        'badge'         => null,
        'image_path'    => '/Frontend/images/bank.jpg',
        'is_active'     => true,
        'display_order' => 4
    ],
]);
echo "✅ loan_products — " . $db->loan_products->countDocuments() . " documents inserted\n";

// ════════════════════════════════════════
// 3. INDEXES
// ════════════════════════════════════════
$db->newsletter_subscribers->createIndex(['email' => 1], ['unique' => true]);
echo "✅ newsletter_subscribers — unique index on email created\n";

$db->contact_inquiries->createIndex(['created_at' => -1]);
echo "✅ contact_inquiries — index on created_at created\n";

echo "\n🎉 All done! MongoDB collections are ready.\n";
echo "</pre>";
?>
