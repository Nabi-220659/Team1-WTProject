<?php
require_once __DIR__ . '/db.php';

// Drop existing collection to ensure fresh start
$db->loan_products->drop();

// Seed Loan Products
$products = [
    [
        "category" => "personal",
        "icon" => "👤",
        "icon_bg" => "rgba(26,79,214,0.1)",
        "name" => "Personal Loan",
        "tag" => "Most Popular",
        "tag_class" => "pct-green",
        "rate" => "10.5%",
        "rate_label" => "p.a. onwards",
        "features" => ["Up to ₹5 Lakhs", "Tenure: 12–60 months", "No collateral required", "Disbursal in 24 hours", "Minimal documentation"],
        "max_amount" => "₹5L",
        "max_tenure" => "60",
        "featured" => false
    ],
    [
        "category" => "personal",
        "icon" => "⚡",
        "icon_bg" => "rgba(245,166,35,0.12)",
        "name" => "Instant Loan",
        "tag" => "Pre-Approved",
        "tag_class" => "pct-gold",
        "rate" => "12.0%",
        "rate_label" => "p.a. flat",
        "features" => ["Up to ₹50,000", "Tenure: 3–12 months", "Disbursal in 2 hours", "Zero paperwork", "₹0 processing fee"],
        "max_amount" => "₹50K",
        "max_tenure" => "12",
        "featured" => true
    ],
    [
        "category" => "business",
        "icon" => "💼",
        "icon_bg" => "rgba(11,29,58,0.08)",
        "name" => "Business Loan",
        "tag" => "For SMEs",
        "tag_class" => "pct-blue",
        "rate" => "14.0%",
        "rate_label" => "p.a. onwards",
        "features" => ["Up to ₹50 Lakhs", "Tenure: 12–84 months", "No collateral up to ₹10L", "GST-based eligibility", "Overdraft facility available"],
        "max_amount" => "₹50L",
        "max_tenure" => "84",
        "featured" => false
    ],
    [
        "category" => "secured",
        "icon" => "🏠",
        "icon_bg" => "rgba(16,185,129,0.1)",
        "name" => "Home Loan",
        "tag" => "Lowest Rate",
        "tag_class" => "pct-green",
        "rate" => "8.5%",
        "rate_label" => "p.a. onwards",
        "features" => ["Up to ₹1 Crore", "Tenure: up to 20 years", "Balance transfer allowed", "Tax benefits (Sec 80C)", "Step-up EMI option"],
        "max_amount" => "₹1 Cr",
        "max_tenure" => "240",
        "featured" => false
    ],
    [
        "category" => "personal",
        "icon" => "🎓",
        "icon_bg" => "rgba(16,185,129,0.1)",
        "name" => "Education Loan",
        "tag" => "Study Now",
        "tag_class" => "pct-blue",
        "rate" => "9.0%",
        "rate_label" => "p.a. onwards",
        "features" => ["Up to ₹20 Lakhs", "Moratorium period included", "Covers tuition + living", "Tax benefits (Sec 80E)", "Repay after course ends"],
        "max_amount" => "₹20L",
        "max_tenure" => "120",
        "featured" => false
    ],
    [
        "category" => "secured",
        "icon" => "🚗",
        "icon_bg" => "rgba(26,79,214,0.1)",
        "name" => "Vehicle Loan",
        "tag" => "New & Used",
        "tag_class" => "pct-blue",
        "rate" => "9.5%",
        "rate_label" => "p.a. onwards",
        "features" => ["Up to ₹20 Lakhs", "Tenure: 12–84 months", "Up to 95% on-road funding", "New & used vehicles", "Same-day approval"],
        "max_amount" => "₹20L",
        "max_tenure" => "84",
        "featured" => false
    ]
];
$db->loan_products->insertMany($products);

echo "Loan Products database seeded successfully.\n";