<?php
require_once __DIR__ . '/db.php';

// Drop existing collection to ensure fresh start
$db->kb_articles->drop();

// Seed Knowledge Base Articles
$articles = [
    [
        "category" => "Credit Score",
        "category_class" => "cat-blue",
        "bg_class" => "bg-blue",
        "title" => "7 Proven Ways to Boost Your CIBIL Score in 90 Days",
        "excerpt" => "Your credit score is the single most important factor lenders look at. Here's a practical, step-by-step roadmap to go from 650 to 750+.",
        "icon" => "📊",
        "read_time" => "8 min read",
        "featured" => true,
        "author" => "Riya Kapoor",
        "author_initials" => "RK"
    ],
    [
        "category" => "EMI Planning",
        "category_class" => "cat-gold",
        "bg_class" => "bg-gold",
        "title" => "Should You Prepay Your Loan or Invest? A Complete Guide",
        "featured" => true,
        "read_time" => "Vikram Mehta · 6 min",
        "excerpt" => ""
    ],
    [
        "category" => "Tax Benefits",
        "category_class" => "cat-green",
        "bg_class" => "bg-green",
        "title" => "Home Loan Tax Deductions: Save Up to ₹3.5L Every Year",
        "featured" => true,
        "read_time" => "Neha Joshi · 5 min",
        "excerpt" => ""
    ],
    [
        "category" => "Business Finance",
        "category_class" => "cat-navy",
        "bg_class" => "bg-navy",
        "title" => "GST-Based Lending: How Your Returns Determine Eligibility",
        "featured" => true,
        "read_time" => "Arun Pillai · 7 min",
        "excerpt" => ""
    ],
    [
        "category" => "Basics",
        "category_class" => "cat-blue",
        "bg_class" => "bg-blue",
        "title" => "Understanding Loan-to-Value (LTV) Ratio",
        "icon" => "🏦",
        "read_time" => "4 min read",
        "featured" => false
    ],
    [
        "category" => "Credit",
        "category_class" => "cat-gold",
        "bg_class" => "bg-gold",
        "title" => "Hard Inquiry vs Soft Inquiry: What Hits Your Score?",
        "icon" => "💳",
        "read_time" => "3 min read",
        "featured" => false
    ],
    [
        "category" => "Savings",
        "category_class" => "cat-green",
        "bg_class" => "bg-green",
        "title" => "Debt Snowball vs Avalanche: Best Repayment Strategy",
        "icon" => "📈",
        "read_time" => "5 min read",
        "featured" => false
    ],
    [
        "category" => "Home Loan",
        "category_class" => "cat-navy",
        "bg_class" => "bg-navy",
        "title" => "Floating vs Fixed Rate: Which Should You Choose?",
        "icon" => "🏠",
        "read_time" => "6 min read",
        "featured" => false
    ],
    [
        "category" => "Planning",
        "category_class" => "cat-gold",
        "bg_class" => "bg-gold",
        "title" => "How Much EMI Is Too Much? The 40% Rule Explained",
        "icon" => "⚖️",
        "read_time" => "4 min read",
        "featured" => false
    ],
    [
        "category" => "Refinance",
        "category_class" => "cat-blue",
        "bg_class" => "bg-blue",
        "title" => "When to Consider a Balance Transfer on Your Home Loan",
        "icon" => "🔄",
        "read_time" => "5 min read",
        "featured" => false
    ]
];
$db->kb_articles->insertMany($articles);

echo "Knowledge Hub database seeded successfully.\n";