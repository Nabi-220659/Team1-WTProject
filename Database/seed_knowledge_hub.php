<?php
/**
 * seed_knowledge_hub.php — Seeds articles for the Knowledge Hub page
 *
 * Run: php Database/seed_knowledge_hub.php
 * Or visit: http://localhost/Loan-Management-System/Database/seed_knowledge_hub.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;

$client = new Client('mongodb://localhost:27017');
$db     = $client->selectDatabase('fundbee_db');

echo "<pre style='font-family:monospace; background:#111; color:#0f0; padding:20px;'>";
echo "🌱 FUNDBEE Knowledge Hub Seeder\n";
echo str_repeat('─', 40) . "\n\n";

// ═══════════════════════════════════════
// ARTICLES
// ═══════════════════════════════════════
$db->articles->drop();

$db->articles->insertMany([
    [
        'title'       => 'Personal Loan vs Credit Card: Which Is Cheaper in 2025?',
        'excerpt'     => 'Millions of Indians rely on credit cards for emergency spending without realising that a personal loan can be 40–60% cheaper. We break down the real cost comparison with examples so you can make the right call.',
        'category'    => 'loans',
        'author'      => 'Arjun Mehta',
        'date'        => 'March 5, 2025',
        'read_time'   => 8,
        'image_path'  => 'images/bank.jpg',
        'is_featured' => true,
        'is_active'   => true,
        'display_order' => 1,
    ],
    [
        'title'       => 'How to Get a Personal Loan with a Low CIBIL Score',
        'excerpt'     => "A low credit score doesn't have to mean automatic rejection. Here are 7 strategies that actually work.",
        'category'    => 'loans',
        'author'      => 'Priya Sharma',
        'date'        => 'Feb 28, 2025',
        'read_time'   => 5,
        'image_path'  => 'images/img2.jpg',
        'is_featured' => false,
        'is_active'   => true,
        'display_order' => 2,
    ],
    [
        'title'       => '10 Habits That Will Boost Your CIBIL Score to 800+',
        'excerpt'     => 'Your credit score is the single biggest factor in loan approvals. Here\'s how to build it systematically.',
        'category'    => 'credit',
        'author'      => 'Kavitha Reddy',
        'date'        => 'Feb 20, 2025',
        'read_time'   => 7,
        'image_path'  => 'images/img3.jpg',
        'is_featured' => false,
        'is_active'   => true,
        'display_order' => 3,
    ],
    [
        'title'       => 'The 50/30/20 Rule: A Simple Budget Framework for Indians',
        'excerpt'     => 'Take control of your monthly finances with this proven budgeting method, adapted for the Indian cost of living.',
        'category'    => 'savings',
        'author'      => 'Rohan Nair',
        'date'        => 'Feb 15, 2025',
        'read_time'   => 4,
        'image_path'  => 'images/img4.jpg',
        'is_featured' => false,
        'is_active'   => true,
        'display_order' => 4,
    ],
    [
        'title'       => 'Home Loan Tax Benefits Under Section 80C and 24(b) Explained',
        'excerpt'     => "Claiming tax deductions on a home loan can save you lakhs. Here's exactly how to do it correctly.",
        'category'    => 'tax',
        'author'      => 'Arjun Mehta',
        'date'        => 'Feb 10, 2025',
        'read_time'   => 6,
        'image_path'  => 'images/bank.jpg',
        'is_featured' => false,
        'is_active'   => true,
        'display_order' => 5,
    ],
    [
        'title'       => 'SIP vs Lump Sum: Which Investment Strategy Beats Inflation?',
        'excerpt'     => 'Data from the last 20 years shows a clear winner — but the answer might surprise you depending on your situation.',
        'category'    => 'invest',
        'author'      => 'Priya Sharma',
        'date'        => 'Jan 30, 2025',
        'read_time'   => 9,
        'image_path'  => 'images/img2.jpg',
        'is_featured' => false,
        'is_active'   => true,
        'display_order' => 6,
    ],
    [
        'title'       => "First-Time Borrower's Complete Guide to Getting a Loan in India",
        'excerpt'     => 'Everything you need to know before applying for your very first loan — eligibility, documents, tips and traps to avoid.',
        'category'    => 'guide',
        'author'      => 'Kavitha Reddy',
        'date'        => 'Jan 22, 2025',
        'read_time'   => 12,
        'image_path'  => 'images/img3.jpg',
        'is_featured' => false,
        'is_active'   => true,
        'display_order' => 7,
    ],
    [
        'title'       => 'Business Loan vs Overdraft: Which Is Better for Your SME?',
        'excerpt'     => 'Both options have their place — the right choice depends on how and when you need capital. We compare both in full.',
        'category'    => 'loans',
        'author'      => 'Rohan Nair',
        'date'        => 'Jan 18, 2025',
        'read_time'   => 6,
        'image_path'  => 'images/img4.jpg',
        'is_featured' => false,
        'is_active'   => true,
        'display_order' => 8,
    ],
    [
        'title'       => 'Emergency Fund 101: How Much Should You Actually Save?',
        'excerpt'     => 'Financial planners say 3–6 months. But what does that mean in rupees for a salaried Indian? Let\'s calculate it.',
        'category'    => 'savings',
        'author'      => 'Arjun Mehta',
        'date'        => 'Jan 12, 2025',
        'read_time'   => 5,
        'image_path'  => 'images/bank.jpg',
        'is_featured' => false,
        'is_active'   => true,
        'display_order' => 9,
    ],
    [
        'title'       => 'Why Your Credit Utilisation Ratio Matters More Than You Think',
        'excerpt'     => 'Keep it below 30% and your CIBIL score can jump significantly within just 2 billing cycles. Here\'s the proof.',
        'category'    => 'credit',
        'author'      => 'Priya Sharma',
        'date'        => 'Jan 5, 2025',
        'read_time'   => 4,
        'image_path'  => 'images/img2.jpg',
        'is_featured' => false,
        'is_active'   => true,
        'display_order' => 10,
    ],
]);

// Create indexes for fast filtering
$db->articles->createIndex(['category' => 1]);
$db->articles->createIndex(['is_featured' => 1]);
$db->articles->createIndex(['title' => 'text', 'excerpt' => 'text']);

$count = $db->articles->countDocuments();
echo "✅ articles — {$count} documents inserted\n";
echo "✅ articles — indexes created (category, featured, full-text search)\n";

// ═══════════════════════════════════════
// NEWSLETTER (ensure index exists)
// ═══════════════════════════════════════
$db->newsletter_subscribers->createIndex(['email' => 1], ['unique' => true]);
echo "✅ newsletter_subscribers — unique email index confirmed\n";

echo "\n🎉 Knowledge Hub MongoDB data is ready.\n";
echo "</pre>";
?>
