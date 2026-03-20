<?php
/**
 * seed_company.php — Run this ONCE to seed company data in MongoDB
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;

$client = new Client('mongodb://localhost:27017');
$db     = $client->selectDatabase('fundbee_db');

echo "<pre style='font-family:monospace; background:#111; color:#0f0; padding:20px;'>";
echo "🌱 FUNDBEE Company Data Seeder\n";
echo str_repeat('─', 40) . "\n\n";

// 1. COMPANY STATS
$db->company_stats->drop();
$db->company_stats->insertMany([
    ['stat_key' => 'experience', 'stat_value' => '10+', 'stat_label' => 'Years in Operation', 'display_order' => 1],
    ['stat_key' => 'customers', 'stat_value' => '25K+', 'stat_label' => 'Happy Customers', 'display_order' => 2],
    ['stat_key' => 'disbursed', 'stat_value' => '₹500Cr+', 'stat_label' => 'Total Disbursed', 'display_order' => 3],
    ['stat_key' => 'satisfaction', 'stat_value' => '98%', 'stat_label' => 'Satisfaction Rate', 'display_order' => 4],
]);
echo "✅ company_stats — " . $db->company_stats->countDocuments() . " documents inserted\n";

// 2. MILESTONES
$db->milestones->drop();
$db->milestones->insertMany([
    ['year' => '2014', 'title' => 'FUNDBEE Founded', 'description' => 'Incorporated in Mumbai as an NBFC with a vision to democratise lending for urban and semi-urban India.', 'display_order' => 1],
    ['year' => '2016', 'title' => 'RBI Registration', 'description' => 'Received official NBFC licence from the Reserve Bank of India, cementing our regulatory standing.', 'display_order' => 2],
    ['year' => '2018', 'title' => 'Digital Platform Launch', 'description' => 'Launched India\'s first fully digital personal loan app with end-to-end paperless processing and same-day approvals.', 'display_order' => 3],
    ['year' => '2020', 'title' => '₹100 Crore Milestone', 'description' => 'Crossed ₹100 Crore in total loan disbursals, serving customers across 12 Indian states.', 'display_order' => 4],
    ['year' => '2022', 'title' => 'Business Loans Launched', 'description' => 'Expanded our product suite to include SME business loans, helping thousands of entrepreneurs grow their ventures.', 'display_order' => 5],
    ['year' => '2024', 'title' => '₹500 Crore & 25,000 Customers', 'description' => 'Reached landmark milestones with over ₹500 Crore disbursed and 25,000 satisfied customers across India.', 'display_order' => 6],
]);
echo "✅ milestones — " . $db->milestones->countDocuments() . " documents inserted\n";

// 3. TEAM MEMBERS
$db->team_members->drop();
$db->team_members->insertMany([
    ['name' => 'Yo Soy Nabi', 'role' => 'Founder & CEO', 'bio' => '20+ years in financial services. Former VP at HDFC Bank. IIM Ahmedabad alumnus with a passion for financial inclusion.', 'avatar' => '👨‍💼', 'bg_class' => 'bg1', 'display_order' => 1],
    ['name' => 'Madhuri', 'role' => 'Co-Founder & COO', 'bio' => 'Built lending operations at Bajaj Finance. Expert in credit risk and process automation. IIT Delhi graduate.', 'avatar' => '👩‍💼', 'bg_class' => 'bg2', 'display_order' => 2],
    ['name' => 'Sowmya', 'role' => 'Chief Technology Officer', 'bio' => 'Ex-Flipkart senior engineer. Built the ML-powered underwriting engine that enables our sub-2-hour approvals.', 'avatar' => '👨‍💻', 'bg_class' => 'bg3', 'display_order' => 3],
    ['name' => 'Abhinaya', 'role' => 'Chief Risk Officer', 'bio' => '15 years in credit risk management across ICICI and Kotak. Architect of FUNDBEE\'s zero-NPA strategy.', 'avatar' => '👩‍⚖️', 'bg_class' => 'bg4', 'display_order' => 4],
    ['name' => 'Pushpa', 'role' => 'Assistant Tecnology Officer', 'bio' => 'Ex-Flipkart senior engineer. Built the ML-powered underwriting engine that enables our sub-2-hour approvals.', 'avatar' => '👨‍💻', 'bg_class' => 'bg3', 'display_order' => 3],
    ['name' => 'Vyshnavi', 'role' => 'Task Manager', 'bio' => '15 years in credit risk management across ICICI and Kotak. Architect of FUNDBEE\'s zero-NPA strategy.', 'avatar' => '👩‍⚖️', 'bg_class' => 'bg4', 'display_order' => 4]
]);
echo "✅ team_members — " . $db->team_members->countDocuments() . " documents inserted\n";

// 4. AWARDS
$db->awards->drop();
$db->awards->insertMany([
    ['title' => 'Best Digital Lender', 'organization' => 'FICCI Financial Services Awards', 'year' => '2023', 'icon' => '🏆', 'display_order' => 1],
    ['title' => 'Top NBFC of the Year', 'organization' => 'Economic Times BFSI Summit', 'year' => '2022', 'icon' => '⭐', 'display_order' => 2],
    ['title' => 'ISO 27001 Certified', 'organization' => 'Information Security Management', 'year' => '2021', 'icon' => '🔐', 'display_order' => 3],
    ['title' => 'Fintech Innovator Award', 'organization' => 'NASSCOM India Leadership Forum', 'year' => '2024', 'icon' => '🌱', 'display_order' => 4],
]);
echo "✅ awards — " . $db->awards->countDocuments() . " documents inserted\n";

echo "\n🎉 All done! MongoDB company collections are ready.\n";
echo "</pre>";
?>
