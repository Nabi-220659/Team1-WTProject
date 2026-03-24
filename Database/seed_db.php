<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;

try {
    echo "Connecting to MongoDB 'fundbee_db'...\n";
    $client = new Client('mongodb://localhost:27017');
    $db = $client->selectDatabase('fundbee_db');
    
    // --- 1. SEED ANALYTICS ---
    $db->partner_analytics->deleteMany([]);
    $db->partner_analytics->insertOne([
        "partner_id" => 1,
        "partner" => [
            "name" => "Priya Nair",
            "tag" => "Premium Partner",
            "initials" => "PN"
        ],
        "kpis" => [
            "avg_yield" => "14.2",
            "avg_yield_trend" => "+1.1",
            "repayment_rate" => "97.8",
            "npa_rate" => "2.1",
            "aum" => "237"
        ],
        "portfolio_mix" => [
            "personal" => 43,
            "business" => 30,
            "home" => 20,
            "other" => 7
        ],
        "performance_metrics" => [
            "disbursement_rate" => "2.1",
            "avg_loan_size" => "4.39",
            "avg_borrower_age" => "33",
            "avg_cibil" => "714",
            "on_time_payment_rate" => "97.8",
            "early_closure_rate" => "8.4",
            "write_off_rate" => "2.2"
        ],
        "cohorts" => [
            [
                "quarter" => "Q1 2026",
                "loans" => 412,
                "disbursed" => "18.4",
                "repayment" => "98.4",
                "yield" => "14.8",
                "npa" => "1.6"
            ],
            [
                "quarter" => "Q4 2025",
                "loans" => 389,
                "disbursed" => "16.9",
                "repayment" => "97.1",
                "yield" => "13.9",
                "npa" => "2.9"
            ],
            [
                "quarter" => "Q3 2025",
                "loans" => 341,
                "disbursed" => "14.2",
                "repayment" => "96.8",
                "yield" => "13.5",
                "npa" => "3.2"
            ]
        ]
    ]);
    
    // --- 2. SEED BORROWERS ---
    $db->partner_borrowers->deleteMany([]);
    $db->partner_borrowers_kpis->deleteMany([]);
    
    $db->partner_borrowers_kpis->insertOne([
        "partner_id" => 1,
        "total_borrowers" => "5,408",
        "total_borrowers_trend" => "+196 this month",
        "on_time_payers" => "5,293",
        "on_time_rate" => "97.8%",
        "at_risk" => 87,
        "onboarded_month" => 196
    ]);
    
    $borrowers = [
        [
            "partner_id" => 1,
            "name" => "Rahul Verma",
            "initials" => "RV",
            "status" => "healthy",
            "color_class" => "ba-green",
            "type" => "Personal Loan",
            "loan_id" => "FB-L-48291",
            "location" => "Bengaluru",
            "cibil" => 742,
            "disbursed_date" => "Jan 2026",
            "progress_percent" => 34,
            "progress_color" => "var(--green)",
            "exposure" => "2.50L",
            "yield" => "Earning: 13.8%",
            "yield_color" => "var(--green)",
            "repay_text" => "3 of 24 EMIs paid",
            "overdue_text" => "Repayment progress"
        ],
        [
            "partner_id" => 1,
            "name" => "Smita Kulkarni",
            "initials" => "SK",
            "status" => "healthy",
            "color_class" => "ba-gold",
            "type" => "Business Loan",
            "loan_id" => "FB-L-48290",
            "location" => "Pune",
            "cibil" => 728,
            "disbursed_date" => "Dec 2025",
            "progress_percent" => 22,
            "progress_color" => "var(--gold)",
            "exposure" => "8.00L",
            "yield" => "Earning: 15.2%",
            "yield_color" => "var(--green)",
            "repay_text" => "4 of 36 EMIs paid",
            "overdue_text" => "Repayment progress"
        ],
        [
            "partner_id" => 1,
            "name" => "Dev Anand Patel",
            "initials" => "DP",
            "status" => "healthy",
            "color_class" => "ba-blue",
            "type" => "Home Loan",
            "loan_id" => "FB-L-48288",
            "location" => "Mumbai",
            "cibil" => 768,
            "disbursed_date" => "Sep 2025",
            "progress_percent" => 8,
            "progress_color" => "#60a5fa",
            "exposure" => "25.00L",
            "yield" => "Earning: 9.4%",
            "yield_color" => "var(--green)",
            "repay_text" => "6 of 120 EMIs paid",
            "overdue_text" => "Repayment progress"
        ],
        [
            "partner_id" => 1,
            "name" => "Meena Tripathi",
            "initials" => "MT",
            "status" => "risk",
            "color_class" => "", 
            "type" => "Personal Loan",
            "loan_id" => "FB-L-47944",
            "location" => "Hyderabad",
            "cibil" => 688,
            "disbursed_date" => "Oct 2025",
            "progress_percent" => 50,
            "progress_color" => "var(--gold)",
            "exposure" => "1.80L",
            "yield" => "At Risk",
            "yield_color" => "var(--gold)",
            "repay_text" => "9 of 18 EMIs paid",
            "overdue_text" => "Overdue: 14 days"
        ],
        [
            "partner_id" => 1,
            "name" => "Kiran Mathur",
            "initials" => "KM",
            "status" => "npa",
            "color_class" => "", 
            "type" => "Personal Loan",
            "loan_id" => "FB-L-47901",
            "location" => "Delhi",
            "cibil" => 612,
            "disbursed_date" => "Aug 2025",
            "progress_percent" => 42,
            "progress_color" => "#ef4444",
            "exposure" => "1.50L",
            "yield" => "NPA",
            "yield_color" => "#fc8181",
            "repay_text" => "5 of 12 EMIs paid",
            "overdue_text" => "NPA — 60+ days overdue"
        ]
    ];
    
    $db->partner_borrowers->insertMany($borrowers);

    // --- 3. SEED MATCHES ---
    $db->partner_matches->deleteMany([]);
    $db->partner_matcher_kpis->deleteMany([]);
    $db->partner_preferences->deleteMany([]);

    $db->partner_matcher_kpis->insertOne([
        "partner_id" => 1,
        "new_matches" => 12,
        "avg_yield" => "11.2%",
        "avg_cibil" => 752,
        "total_pool" => "₹4.8Cr"
    ]);

    $db->partner_preferences->insertOne([
        "partner_id" => 1,
        "min_yield" => "11%",
        "min_cibil" => "700",
        "max_exposure" => "₹20 Lakhs",
        "segments" => ["Personal", "Business", "Home"],
        "auto_fund" => true,
        "daily_digest" => true
    ]);

    $matches = [
        [
            "partner_id" => 1,
            "loan_id" => "FB-L-M1",
            "name" => "Pradeep Kumar",
            "initials" => "PK",
            "match_tier" => "high",
            "match_percent" => 97,
            "color_class" => "da-green",
            "ring_class" => "ms-high",
            "type" => "Business Loan · Pune · 8 yrs in business",
            "ai_reason" => "High CIBIL (748), strong business cash flow, low DBR (32%), and loan type aligns with your Business segment target (30% allocation). Projected yield of 15.2% matches your portfolio strategy.",
            "amount" => "₹5,00,000",
            "tenure" => "36 mo",
            "cibil" => 748,
            "yield" => "15.2%",
            "risk" => "B+",
            "income" => "₹85K/mo"
        ],
        [
            "partner_id" => 1,
            "loan_id" => "FB-L-M2",
            "name" => "Aishwarya Singh",
            "initials" => "AS",
            "match_tier" => "high",
            "match_percent" => 94,
            "color_class" => "da-gold",
            "ring_class" => "ms-high",
            "type" => "Personal Loan · Mumbai · Salaried — Tech Sector",
            "ai_reason" => "CIBIL 761, zero existing EMIs, stable tech sector employment (4 yrs). Personal loan fills your 43% segment target. Clean repayment history on 1 prior loan. Excellent default risk score.",
            "amount" => "₹2,50,000",
            "tenure" => "24 mo",
            "cibil" => 761,
            "yield" => "13.5%",
            "risk" => "A-",
            "income" => "₹72K/mo"
        ],
        [
            "partner_id" => 1,
            "loan_id" => "FB-L-M3",
            "name" => "Nikhil Mehra",
            "initials" => "NM",
            "match_tier" => "medium",
            "match_percent" => 82,
            "color_class" => "da-blue",
            "ring_class" => "ms-mid",
            "type" => "Home Loan · Bengaluru · Property Under ₹80L",
            "ai_reason" => "CIBIL 735, moderate match — home segment is below your 20% target (currently 19%). Lower yield at 9.4% but offers strong collateral security and very low default probability.",
            "amount" => "₹18,00,000",
            "tenure" => "120 mo",
            "cibil" => 735,
            "yield" => "9.4%",
            "risk" => "A",
            "income" => "₹95K/mo"
        ]
    ];
    // --- 4. SEED NEW COMPONENTS (Earnings, Settings, Dashboard, Portfolio) ---
    
    // Earnings
    $db->partner_earnings->deleteMany([]);
    $db->partner_earnings->insertOne([
        "partner_id" => 1,
        "total_earnings" => "₹42.50 Cr",
        "roi" => "14.2%",
        "available_withdrawal" => "₹8.24L",
        "monthly_history" => [
            ["month" => "Oct", "amount" => "2.1Cr"],
            ["month" => "Nov", "amount" => "2.4Cr"],
            ["month" => "Dec", "amount" => "2.3Cr"],
            ["month" => "Jan", "amount" => "2.8Cr"],
            ["month" => "Feb", "amount" => "2.9Cr"],
            ["month" => "Mar", "amount" => "3.2Cr"]
        ],
        "history_table" => [
            ["month" => "March 2026", "earnings" => "₹3.20 Cr", "active_loans" => "5,408", "roi" => "14.2%", "change" => "↑ 0.1%"],
            ["month" => "February 2026", "earnings" => "₹2.98 Cr", "active_loans" => "5,312", "roi" => "14.1%", "change" => "↑ 0.2%"],
            ["month" => "January 2026", "earnings" => "₹2.81 Cr", "active_loans" => "5,190", "roi" => "13.9%", "change" => "↓ 0.1%"]
        ]
    ]);

    // Profile Settings
    $db->partner_profile->deleteMany([]);
    $db->partner_profile->insertOne([
        "partner_id" => 1,
        "full_name" => "Priya Nair",
        "partner_ref" => "FBPRT-20240882",
        "email" => "priya.nair@finance.in",
        "mobile" => "+91 99887 76655",
        "organization" => "Nair Capital Partners LLP",
        "city" => "Mumbai",
        "business_reg" => "U65929MH2018PTC308821"
    ]);

    // Bank Accounts
    $db->partner_banks->deleteMany([]);
    $db->partner_banks->insertMany([
        ["partner_id" => 1, "bank_name" => "HDFC Bank", "account" => "****4821", "type" => "Current", "is_primary" => true],
        ["partner_id" => 1, "bank_name" => "ICICI Bank", "account" => "****9234", "type" => "Current", "is_primary" => false]
    ]);

    // Notifications
    $db->partner_notifications->deleteMany([]);
    $db->partner_notifications->insertOne([
        "partner_id" => 1,
        "settlement_alerts" => true,
        "new_matches" => true,
        "npa_alerts" => true,
        "portfolio_reports" => true,
        "regulatory_updates" => true
    ]);

    // KYC
    $db->partner_kyc->deleteMany([]);
    $db->partner_kyc->insertOne([
        "partner_id" => 1,
        "partner_kyc" => true,
        "business_verification" => true,
        "bank_verified" => true,
        "aml_cleared" => true,
        "premium_status" => true
    ]);

    // Dashboard KPIs
    $db->partner_dashboard_kpis->deleteMany([]);
    $db->partner_dashboard_kpis->insertOne([
        "partner_id" => 1,
        "total_earnings" => "₹42.50 Cr",
        "earnings_change" => "↑ 5.2% this month",
        "yield" => "14.2%",
        "yield_change" => "↑ 1.1% vs last month",
        "active_portfolio" => "₹237.53 Cr",
        "active_portfolio_count" => "2,072 securities",
        "repayment_received" => "₹169.31 Cr",
        "repayment_change" => "↑ 1% this quarter",
        "active_loans" => "5,408",
        "closed_loans" => "264",
        "open_applications" => "3",
        "write_offs" => "₹62.11 Cr",
        "available_withdraw" => "₹8,24,500",
        "withdraw_this_month" => "₹3.2 Cr",
        "withdraw_last_month" => "₹2.98 Cr",
        "withdraw_q1" => "₹9.1 Cr",
        "pending_settle" => "₹1.4 Cr"
    ]);

    // Portfolio
    $db->partner_portfolio->deleteMany([]);
    $db->partner_portfolio->insertOne([
        "partner_id" => 1,
        "segments" => [
            ["name" => "Personal Loans", "count" => "2,104", "value" => "₹98.4 Cr", "pct" => "62%", "change" => "+4.2%"],
            ["name" => "Business Loans", "count" => "841", "value" => "₹71.2 Cr", "pct" => "45%", "change" => "+6.1%"],
            ["name" => "Home Loans", "count" => "463", "value" => "₹48.3 Cr", "pct" => "30%", "change" => "+2.8%"],
            ["name" => "Education Loans", "count" => "310", "value" => "₹19.6 Cr", "pct" => "18%", "change" => "-0.5%"],
            ["name" => "Vehicle / Others", "count" => "354", "value" => "₹13.1 Cr", "pct" => "10%", "change" => "-1.2%"]
        ]
    ]);

    echo "Successfully seeded databases for all Partner components!\n";
} catch (Exception $e) {
    echo "Error seeding database: " . $e->getMessage() . "\n";
}
