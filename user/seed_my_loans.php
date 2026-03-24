<?php
require_once __DIR__ . '/db.php';

// Drop existing collection to ensure fresh start
$db->loans->drop();

// Seed My Loans User specific data
$loans = [
    [
        "user_id" => "1",
        "type" => "personal",
        "name" => "Personal Loan",
        "loan_id" => "#FB-20241",
        "date_info" => "Disbursed Jan 10, 2026",
        "amount" => "₹1,00,000",
        "emi" => "₹5,200",
        "rate" => "10.5%",
        "status" => "active",
        "icon" => "👤",
        "icon_class" => "lt-blue",
        "progress_label_1" => "Repaid ₹18,200",
        "progress_label_2" => "of ₹1,00,000",
        "progress_val" => "18%",
        "progress_bg" => "var(--green)",
        "button_action" => "View Details",
        "details" => [
            "title" => "Personal Loan",
            "id" => "#FB-20241",
            "amt" => "₹1,00,000",
            "emi" => "₹5,200",
            "rate" => "10.5% p.a.",
            "tenure" => "24 Months",
            "outstanding" => "₹81,800",
            "next" => "Apr 05, 2026",
            "progress" => "18%",
            "status" => "active",
            "action" => "Pay EMI Now",
            "timeline" => [
                ["dot" => "green", "title" => "Loan Disbursed", "date" => "Jan 10, 2026", "note" => "₹1,00,000 credited to your account"],
                ["dot" => "green", "title" => "EMI 1 Paid", "date" => "Feb 05, 2026", "note" => "₹5,200 deducted"],
                ["dot" => "green", "title" => "EMI 2 Paid", "date" => "Mar 05, 2026", "note" => "₹5,200 deducted"],
                ["dot" => "gold", "title" => "EMI 3 Due", "date" => "Apr 05, 2026", "note" => "₹5,200 pending"],
                ["dot" => "gray", "title" => "Loan Closure", "date" => "Jan 2028", "note" => "Projected closure date"]
            ]
        ]
    ],
    [
        "user_id" => "1",
        "type" => "home",
        "name" => "Home Loan",
        "loan_id" => "#FB-20189",
        "date_info" => "Disbursed Nov 1, 2025",
        "amount" => "₹8,00,000",
        "emi" => "₹6,800",
        "rate" => "8.5%",
        "status" => "active",
        "icon" => "🏠",
        "icon_class" => "lt-gold",
        "progress_label_1" => "Repaid ₹82,000",
        "progress_label_2" => "of ₹8,00,000",
        "progress_val" => "10%",
        "progress_bg" => "var(--green)",
        "button_action" => "View Details",
        "details" => [
            "title" => "Home Loan",
            "id" => "#FB-20189",
            "amt" => "₹8,00,000",
            "emi" => "₹6,800",
            "rate" => "8.5% p.a.",
            "tenure" => "120 Months",
            "outstanding" => "₹7,18,000",
            "next" => "Apr 01, 2026",
            "progress" => "10%",
            "status" => "active",
            "action" => "Pay EMI Now",
            "timeline" => [
                ["dot" => "green", "title" => "Loan Sanctioned", "date" => "Oct 20, 2025", "note" => "Property verification complete"],
                ["dot" => "green", "title" => "Disbursement", "date" => "Nov 01, 2025", "note" => "₹8,00,000 transferred to seller"],
                ["dot" => "green", "title" => "EMIs 1–4 Paid", "date" => "Feb 2026", "note" => "₹82,000 total repaid"],
                ["dot" => "gold", "title" => "EMI 5 Due", "date" => "Apr 01, 2026", "note" => "₹6,800 pending"],
                ["dot" => "gray", "title" => "Loan Closure", "date" => "Nov 2035", "note" => "Projected closure date"]
            ]
        ]
    ],
    [
        "user_id" => "1",
        "type" => "business",
        "name" => "Business Loan",
        "loan_id" => "#FB-20312",
        "date_info" => "Applied Mar 15, 2026",
        "amount" => "₹2,50,000",
        "emi" => "TBD",
        "rate" => "12.0%",
        "status" => "pending",
        "icon" => "💼",
        "icon_class" => "lt-navy",
        "progress_label_1" => "Application Progress",
        "progress_label_2" => "60%",
        "progress_val" => "60%",
        "progress_bg" => "var(--gold)",
        "button_action" => "Track Status",
        "details" => [
            "title" => "Business Loan",
            "id" => "#FB-20312",
            "amt" => "₹2,50,000",
            "emi" => "TBD",
            "rate" => "12.0% p.a.",
            "tenure" => "36 Months",
            "outstanding" => "Pending",
            "next" => "TBD",
            "progress" => "60%",
            "status" => "pending",
            "action" => "Track Status",
            "timeline" => [
                ["dot" => "green", "title" => "Application Submitted", "date" => "Mar 15, 2026", "note" => "All basic documents uploaded"],
                ["dot" => "green", "title" => "Initial KYC Passed", "date" => "Mar 17, 2026", "note" => "Identity verification complete"],
                ["dot" => "gold", "title" => "Credit Assessment", "date" => "In Progress", "note" => "Risk team reviewing income proof"],
                ["dot" => "gray", "title" => "Final Approval", "date" => "Expected Apr 1", "note" => "Pending credit team review"],
                ["dot" => "gray", "title" => "Disbursement", "date" => "Expected Apr 3", "note" => "Upon approval"]
            ]
        ]
    ],
    [
        "user_id" => "1",
        "type" => "education",
        "name" => "Education Loan",
        "loan_id" => "#FB-19874",
        "date_info" => "Closed Dec 20, 2025",
        "amount" => "₹75,000",
        "emi" => "Closed",
        "rate" => "9.0%",
        "status" => "closed",
        "icon" => "🎓",
        "icon_class" => "lt-green",
        "progress_label_1" => "Fully Repaid",
        "progress_label_2" => "100%",
        "progress_val" => "100%",
        "progress_bg" => "var(--muted)",
        "button_action" => "Download NOC",
        "details" => [
            "title" => "Education Loan",
            "id" => "#FB-19874",
            "amt" => "₹75,000",
            "emi" => "Closed",
            "rate" => "9.0% p.a.",
            "tenure" => "18 Months",
            "outstanding" => "₹0",
            "next" => "Closed",
            "progress" => "100%",
            "status" => "closed",
            "action" => "Download NOC",
            "timeline" => [
                ["dot" => "green", "title" => "Loan Disbursed", "date" => "Jun 05, 2024", "note" => "₹75,000 to institution"],
                ["dot" => "green", "title" => "All EMIs Paid", "date" => "Dec 2025", "note" => "18 EMIs completed on time"],
                ["dot" => "green", "title" => "Loan Closed", "date" => "Dec 20, 2025", "note" => "NOC issued, CIBIL updated"]
            ]
        ]
    ]
];
$db->loans->insertMany($loans);

echo "My Loans database seeded successfully.\n";