<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// MongoDB Connection
try {
    require_once __DIR__ . '/../../vendor/autoload.php';
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->fundbee_db;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database Connection Failed: " . $e->getMessage()]);
    exit;
}

$partnerId = 1; // Authenticated Partner

// Handle Requests
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $profile = $db->partner_profile->findOne(["partner_id" => $partnerId]);
        $banks = $db->partner_banks->find(["partner_id" => $partnerId])->toArray();
        $notifs = $db->partner_notifications->findOne(["partner_id" => $partnerId]);
        $kyc = $db->partner_kyc->findOne(["partner_id" => $partnerId]);
        $prefs = $db->partner_preferences->findOne(["partner_id" => $partnerId]);

        unset($profile['_id'], $notifs['_id'], $kyc['_id'], $prefs['_id']);
        foreach($banks as &$b) unset($b['_id']);

        echo json_encode([
            "status" => "success",
            "data" => [
                "profile" => $profile,
                "banks" => $banks,
                "notifications" => $notifs,
                "kyc" => $kyc,
                "preferences" => $prefs
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    try {
        if ($action === 'update_profile') {
            $db->partner_profile->updateOne(
                ["partner_id" => $partnerId],
                ['$set' => $input['data']]
            );
            echo json_encode(["status" => "success", "message" => "Profile updated successfully"]);
            
        } elseif ($action === 'update_notifications') {
            $db->partner_notifications->updateOne(
                ["partner_id" => $partnerId],
                ['$set' => $input['data']]
            );
            echo json_encode(["status" => "success", "message" => "Notification settings saved"]);
            
        } elseif ($action === 'update_preferences') {
            $db->partner_preferences->updateOne(
                ["partner_id" => $partnerId],
                ['$set' => $input['data']]
            );
            echo json_encode(["status" => "success", "message" => "Investment preferences saved"]);
            
        } elseif (isset($_FILES['photo'])) {
            $file = $_FILES['photo'];
            $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newName = "partner_" . $partnerId . "_" . time() . "." . $ext;
            $target  = __DIR__ . "/uploads/" . $newName;
            
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $db->partner_profile->updateOne(
                    ["partner_id" => $partnerId],
                    ['$set' => ['photo_url' => "Backend/partner/uploads/" . $newName]]
                );
                echo json_encode(["status" => "success", "message" => "Photo uploaded!", "url" => "uploads/" . $newName]);
            } else {
                echo json_encode(["status" => "error", "message" => "Upload failed on server"]);
            }
            
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Unknown action"]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to update: " . $e->getMessage()]);
    }
}
?>
