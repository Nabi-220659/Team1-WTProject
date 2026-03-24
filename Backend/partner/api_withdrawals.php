<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Simulate Withdrawals Data
echo json_encode([
    "status" => "success",
    "data" => [
        "available_balance" => "₹8,24,500",
        "stats" => [
            "this_month" => "₹3.20 Cr",
            "last_month" => "₹2.98 Cr",
            "pending" => "₹1.40 Cr",
            "total_withdrawn" => "₹34.26 Cr"
        ],
        "history" => [
            ["title" => "Withdrawal — HDFC Bank ****", "date" => "Mar 15, 2026", "amount" => "₹5,00,000", "status" => "completed"],
            ["title" => "Settlement — Monthly Earnings", "date" => "Expected Mar 23, 2026", "amount" => "₹1,40,00,000", "status" => "pending"],
            ["title" => "Withdrawal — HDFC Bank ****", "date" => "Feb 15, 2026", "amount" => "₹8,00,000", "status" => "completed"]
        ]
    ]
]);
?>
