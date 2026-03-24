<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Simulate Statements Data
echo json_encode([
    "status" => "success",
    "data" => [
        "monthly" => [
            ["name" => "March 2026 — Earnings Statement", "period" => "01 Mar – 21 Mar 2026", "amount" => "₹3.20 Cr", "size" => "1.2 MB"],
            ["name" => "February 2026 — Earnings Statement", "period" => "01 Feb – 28 Feb 2026", "amount" => "₹2.98 Cr", "size" => "1.1 MB"],
            ["name" => "January 2026 — Earnings Statement", "period" => "01 Jan – 31 Jan 2026", "amount" => "₹2.89 Cr", "size" => "1.0 MB"]
        ],
        "tax" => [
            ["name" => "Form 26AS — TDS Certificate FY 2025–26", "period" => "April 2025 – March 2026", "amount" => "TDS: ₹4.28L", "size" => "0.8 MB"],
            ["name" => "Annual Earnings Report FY 2025–26", "period" => "Full year summary", "amount" => "₹33.8 Cr", "size" => "2.4 MB"]
        ]
    ]
]);
?>
