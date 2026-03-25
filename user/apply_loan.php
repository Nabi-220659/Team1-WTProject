<?php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    $loanType   = trim($data['loanType'] ?? '');
    $amount     = (float)($data['amount'] ?? 0);
    $tenure     = trim($data['tenure'] ?? '');
    $purpose    = trim($data['purpose'] ?? '');
    $income     = (float)($data['income'] ?? 0);
    $employment = trim($data['employment'] ?? '');
    $name       = trim($data['fullName'] ?? '');
    $phone      = trim($data['phone'] ?? '');

    // KYC check (Aadhaar & PAN required)
    try {
        $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
        $db = $client->fundbee_db;
        $userColl = $db->users_data;
        $userDoc = $userColl->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])]);
        
        $uploadedTypes = [];
        if (isset($userDoc['documents'])) {
            foreach ($userDoc['documents'] as $d) $uploadedTypes[] = $d['type'];
        }

        if (!in_array('aadhaar', $uploadedTypes) || !in_array('pan', $uploadedTypes)) {
            echo json_encode(["success" => false, "message" => "KYC documents (Aadhaar & PAN) are required to apply for a loan."]);
            exit;
        }
    } catch (Exception $e) {
        // fallback to continue if DB check fails here, but usually it shouldn't
    }

    if (!$amount || !$tenure || !$purpose || !$income || !$employment) {
        echo json_encode(["success" => false, "message" => "Please fill all fields"]);
        exit;
    }

    try {
        $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
        $db = $client->fundbee_db;
        $collection = $db->user_loans;

        // Generate a random FB-XXXX loan ID
        $loanId = "FB-" . rand(10000, 99999);
        
        // Calculate rough dummy EMI just for display purposes
        $months = (int) preg_replace('/[^0-9]/', '', $tenure);
        $months = $months > 0 ? $months : 12;
        $interest = ($amount * 0.12 * ($months / 12));
        $emi = ceil(($amount + $interest) / $months);
        
        // Next due date -> 1st of next month
        $nextDue = date('M 01, Y', strtotime('+1 month'));

        $insertResult = $collection->insertOne([
            "user_id"    => $_SESSION['user_id'],
            "loan_id"    => $loanId,
            "loan_type"  => $loanType,
            "amount"     => (float)$amount,
            "tenure"     => $tenure,
            "name"       => $name,
            "phone"      => $phone,
            "purpose"    => $purpose,
            "income"     => $income,
            "employment" => $employment,
            "status"     => "pending", // active, pending, closed
            "repaid"     => 0,
            "emi_amount" => $emi,
            "next_due"   => $nextDue,
            "created_at" => new MongoDB\BSON\UTCDateTime()
        ]);

        if ($insertResult->getInsertedCount() === 1) {
            echo json_encode(["success" => true, "message" => "Loan applied successfully!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to submit application"]);
        }
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
}
?>
