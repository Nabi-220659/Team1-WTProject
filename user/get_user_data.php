<?php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["loggedIn" => false]);
    exit;
}

function format_inr($num) {
    if ($num === 'TBD' || $num === 'Closed') return $num;
    $num = (int) $num; 
    if ($num < 1000) return '₹' . $num;
    $lastThree = $num % 1000;
    $rest = (int)($num / 1000);
    $formatted = str_pad($lastThree, 3, '0', STR_PAD_LEFT);
    while ($rest > 0) {
        $chunk = $rest % 100;
        $rest = (int)($rest / 100);
        $formatted = ($rest > 0 ? str_pad($chunk, 2, '0', STR_PAD_LEFT) : $chunk) . ',' . $formatted;
    }
    return '₹' . $formatted;
}

try {
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db = $client->fundbee_db;
    $collection = $db->users_data;

    $user = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])]);

    if ($user) {
        $loansCollection = $db->user_loans;
        $loansCursor = $loansCollection->find(['user_id' => $_SESSION['user_id']]);

        $loans          = [];
        $activeLoans    = 0;
        $pendingLoans   = 0;
        $totalLoans     = 0;
        $totalBorrowed  = 0;
        $totalRepaid    = 0;
        $emiDue         = 0;
        $outstandingTotal = 0;

        $upcomingEmis   = [];

        foreach ($loansCursor as $loan) {
            $status = $loan['status'] ?? 'pending';
            $amount = (float)($loan['amount'] ?? 0);
            $emi    = (float)($loan['emi_amount'] ?? 0);
            $repaid = (float)($loan['repaid'] ?? 0);
            $rate   = (float)($loan['interest_rate'] ?? 10.5);
            $tenure = $loan['tenure'] ?? '24 Months';

            $totalLoans++;

            if ($status === 'active') {
                $activeLoans++;
                $emiDue += $emi;
            }
            if ($status === 'pending') {
                $pendingLoans++;
            }

            if (in_array($status, ['active', 'pending', 'closed'])) {
                $totalBorrowed += $amount;
            }
            if (in_array($status, ['active', 'closed'])) {
                $totalRepaid += $repaid;
            }

            $outstanding = max(0, $amount - $repaid); // simplified logic without interest component
            if ($status === 'active') {
                $outstandingTotal += $outstanding;
            }

            // Calculate progress %
            $progress = 0;
            if ($status === 'closed') {
                $progress = 100;
            } else if ($status === 'pending') {
                $progress = 60; // Mock 60% progress for application
            } else if ($amount > 0) {
                $progress = round(($repaid / $amount) * 100);
            }

            // Timeline mocked for realism
            $disbursedDate = isset($loan['applied_at']) && $loan['applied_at'] instanceof MongoDB\BSON\UTCDateTime 
                ? $loan['applied_at']->toDateTime()->setTimezone(new DateTimeZone('Asia/Kolkata'))->format('M d, Y') 
                : 'Recent';

            $timeline = [];
            if ($status === 'pending') {
                $timeline[] = ['dot' => 'green', 'title' => 'Application Submitted', 'date' => $disbursedDate, 'note' => 'Documents uploaded and verified'];
                $timeline[] = ['dot' => 'gold', 'title' => 'Under Review', 'date' => 'In Progress', 'note' => 'Risk team assessing profile'];
                $timeline[] = ['dot' => 'gray', 'title' => 'Approval & Disbursement', 'date' => 'Pending', 'note' => 'Waiting for final sign-off'];
            } else if ($status === 'active') {
                $timeline[] = ['dot' => 'green', 'title' => 'Loan Disbursed', 'date' => $disbursedDate, 'note' => format_inr($amount) . ' credited'];
                $timeline[] = ['dot' => 'gold', 'title' => 'Next EMI Due', 'date' => $loan['next_due'] ?? 'Upcoming', 'note' => format_inr($emi) . ' pending'];
                $timeline[] = ['dot' => 'gray', 'title' => 'Loan Closure', 'date' => 'Projected', 'note' => 'After ' . $tenure];
            } else {
                $timeline[] = ['dot' => 'green', 'title' => 'Loan Disbursed', 'date' => 'Past', 'note' => 'Amount credited'];
                $timeline[] = ['dot' => 'green', 'title' => 'Fully Repaid', 'date' => 'Past', 'note' => 'All EMIs cleared'];
                $timeline[] = ['dot' => 'green', 'title' => 'Loan Closed', 'date' => 'Past', 'note' => 'NOC generated'];
            }

            $loanObj = [
                "loan_id"     => $loan['loan_id'] ?? '',
                "loan_type"   => $loan['loan_type'] ?? 'Personal Loan',
                "amount"      => $amount,
                "amount_str"  => format_inr($amount),
                "emi"         => $status === 'closed' ? 'Closed' : ($status === 'pending' ? 'TBD' : $emi),
                "emi_str"     => $status === 'closed' ? 'Closed' : ($status === 'pending' ? 'TBD' : format_inr($emi)),
                "next_due"    => $status === 'closed' ? 'Closed' : ($status === 'pending' ? 'TBD' : ($loan['next_due'] ?? '—')),
                "status"      => $status,
                "tenure"      => $tenure,
                "purpose"     => $loan['purpose'] ?? '',
                "rate"        => $rate . '% p.a.',
                "repaid"      => $repaid,
                "repaid_str"  => format_inr($repaid),
                "outstanding" => $status === 'pending' ? 'Pending' : format_inr($outstanding),
                "progress"    => $progress,
                "timeline"    => $timeline
            ];

            $loans[] = $loanObj;

            if ($status === 'active' && $emi > 0) {
                // Mock days left for UI (1 to 20)
                $days_left = rand(1, 20);
                $upcomingEmis[] = [
                    "loan_id"   => $loanObj['loan_id'],
                    "loan_type" => $loanObj['loan_type'],
                    "emi_str"   => $loanObj['emi_str'],
                    "next_due"  => $loanObj['next_due'],
                    "days_left" => $days_left
                ];
            }
        }

        // Sort upcoming emis by days left
        usort($upcomingEmis, function($a, $b) {
            return $a['days_left'] <=> $b['days_left'];
        });

        // Document Check
        $uploadedTypes = [];
        if (isset($user['documents'])) {
            foreach ($user['documents'] as $doc) {
                $uploadedTypes[] = $doc['type'];
            }
        }
        $canApply = (in_array('aadhaar', $uploadedTypes) && in_array('pan', $uploadedTypes));

        echo json_encode([
            "loggedIn" => true,
            "user" => [
                "firstName" => $user['firstName'] ?? '',
                "lastName"  => $user['lastName'] ?? '',
                "email"     => $user['email'] ?? '',
                "phone"     => $user['phone'] ?? '',
                "dob"       => $user['dob'] ?? '',
                "city"      => $user['city'] ?? '',
                "address"   => $user['address'] ?? ''
            ],
            "stats" => [
                "active_loans"   => $activeLoans,
                "pending_loans"  => $pendingLoans,
                "total_loans"    => $totalLoans,
                "total_borrowed" => format_inr($totalBorrowed),
                "total_repaid"   => format_inr($totalRepaid),
                "emi_due"        => format_inr($emiDue),
                "outstanding"    => format_inr($outstandingTotal)
            ],
            "loans" => $loans,
            "upcoming_emis" => array_slice($upcomingEmis, 0, 3), // Top 3
            "can_apply" => $canApply,
            "missing_kyc" => !$canApply
        ]);
    } else {
        echo json_encode(["loggedIn" => false]);
    }
} catch (Exception $e) {
    echo json_encode(["loggedIn" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
