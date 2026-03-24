<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $client = new Client('mongodb://localhost:27017');
    $db = $client->selectDatabase('fundbee_db');
    
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // Handle approve/reject actions
        $input = json_decode(file_get_contents('php://input'), true);
        $appId = $input['app_id'] ?? null;
        $status = $input['status'] ?? null;
        
        if ($appId && $status) {
            // In a real application, you would update the MongoDB record like this:
            // $db->loan_applications->updateOne(['app_id' => $appId], ['$set' => ['status' => $status]]);
            
            echo json_encode(['success' => true, 'message' => "Application $appId updated to $status"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        }
    } else {
        // Handle GET request to fetch applications list and KPIs
        // Real implementation: $applications = $db->loan_applications->find(['partner_id' => 1])->toArray();
        
        $data = [
            "success" => true,
            "kpis" => [
                "pending" => 3,
                "approved" => 28,
                "rejected" => 4,
                "disbursed" => "1.24"
            ],
            "applications" => [
                [
                    "id" => "APP-20260321-001",
                    "applicant" => "Pradeep Kumar",
                    "initials" => "PK",
                    "color_class" => "aa-green",
                    "type" => "Business Loan",
                    "date" => "Mar 21, 2026",
                    "cibil" => 748,
                    "cibil_class" => "sr-high",
                    "status" => "pending",
                    "status_label" => "Pending Review",
                    "requested" => "5,00,000",
                    "tenure" => 36,
                    "income" => "85,000",
                    "emi" => "12,000",
                    "risk" => "B+ (Low)",
                    "risk_color" => "var(--green)",
                    "missing_doc" => null
                ],
                [
                    "id" => "APP-20260320-008",
                    "applicant" => "Sneha Nambiar",
                    "initials" => "SN",
                    "color_class" => "aa-blue",
                    "type" => "Personal Loan",
                    "date" => "Mar 20, 2026",
                    "cibil" => 694,
                    "cibil_class" => "sr-mid",
                    "status" => "pending",
                    "status_label" => "Pending Review",
                    "requested" => "1,50,000",
                    "tenure" => 24,
                    "income" => "42,000",
                    "emi" => "8,500",
                    "risk" => "C+ (Medium)",
                    "risk_color" => "var(--gold)",
                    "missing_doc" => null
                ],
                [
                    "id" => "APP-20260315-012",
                    "applicant" => "Arjun Sharma",
                    "initials" => "AM",
                    "color_class" => "aa-gold",
                    "type" => "Business Loan",
                    "date" => "Mar 15, 2026",
                    "cibil" => 745,
                    "cibil_class" => "sr-high",
                    "status" => "pending",
                    "status_label" => "In Credit Review",
                    "requested" => "2,50,000",
                    "tenure" => 36,
                    "income" => "55,000",
                    "emi" => "12,000",
                    "missing_doc" => "IT Returns"
                ],
                [
                    "id" => "APP-20260110-004",
                    "applicant" => "Rahul Verma",
                    "initials" => "RV",
                    "color_class" => "aa-green",
                    "type" => "Personal Loan",
                    "date" => "Jan 10, 2026",
                    "status" => "approved",
                    "status_label" => "Approved & Funded",
                    "approved_amount" => "2,50,000",
                    "tenure" => 24,
                    "yield" => "13.8",
                    "cibil" => 742,
                    "disbursed_date" => "Jan 10, 2026"
                ]
            ]
        ];
        echo json_encode($data);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
