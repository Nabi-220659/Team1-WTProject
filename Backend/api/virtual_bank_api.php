<?php
/**
 * virtual_bank_api.php — Virtual Banking System Backend
 *
 * Endpoints (via ?action=...):
 *   GET  ?action=get_account&user_id=...        → Account details + balance
 *   POST ?action=deposit        body: { user_id, amount, method }
 *   POST ?action=withdraw       body: { user_id, amount, destination }
 *   POST ?action=transfer       body: { from_user_id, to_account_no, amount, note }
 *   POST ?action=loan_repayment body: { user_id, loan_ref, amount }
 *   GET  ?action=transactions&user_id=...       → Transaction history
 *   POST ?action=create_account body: { user_id, user_name }
 *
 * Collection: vbank_accounts  (one doc per user)
 * Collection: vbank_transactions
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../index/config/db.php';

use MongoDB\BSON\UTCDateTime;

$action = $_GET['action'] ?? '';

try {
    $db           = getDB();
    $accounts     = $db->selectCollection('vbank_accounts');
    $transactions = $db->selectCollection('vbank_transactions');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? [];
    }

    switch ($action) {

        // ── CREATE ACCOUNT ──
        case 'create_account': {
            $userId   = trim($data['user_id'] ?? '');
            $userName = trim($data['user_name'] ?? '');
            if (!$userId || !$userName) { respond(false, 'user_id and user_name required'); break; }

            $existing = $accounts->findOne(['user_id' => $userId]);
            if ($existing) { respond(false, 'Account already exists for this user'); break; }

            $accNo = 'FBVB-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT) . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $accounts->insertOne([
                'user_id'         => $userId,
                'user_name'       => $userName,
                'account_no'      => $accNo,
                'balance'         => 0.0,
                'total_deposited' => 0.0,
                'status'          => 'active',
                'created_at'      => new UTCDateTime(),
            ]);
            respond(true, 'Virtual bank account created', ['account_no' => $accNo]);
            break;
        }

        // ── GET ACCOUNT ──
        case 'get_account': {
            $userId = trim($_GET['user_id'] ?? '');
            if (!$userId) { respond(false, 'user_id required'); break; }
            $acc = $accounts->findOne(['user_id' => $userId]);
            if (!$acc) { respond(false, 'No account found'); break; }
            respond(true, 'Account fetched', [
                'account_no'      => $acc['account_no'],
                'user_name'       => $acc['user_name'],
                'balance'         => (float)$acc['balance'],
                'total_deposited' => (float)$acc['total_deposited'],
                'status'          => $acc['status'],
            ]);
            break;
        }

        // ── DEPOSIT ──
        case 'deposit': {
            $userId = trim($data['user_id'] ?? '');
            $amount = floatval($data['amount'] ?? 0);
            $method = trim($data['method'] ?? 'upi');

            if (!$userId) { respond(false, 'user_id required'); break; }
            if ($amount <= 0 || $amount > 1000000) { respond(false, 'Invalid amount (1 – 10,00,000)'); break; }

            $acc = $accounts->findOne(['user_id' => $userId]);
            if (!$acc) { respond(false, 'Account not found'); break; }
            if ($acc['status'] !== 'active') { respond(false, 'Account is not active'); break; }

            $newBal = (float)$acc['balance'] + $amount;
            $newDep = (float)$acc['total_deposited'] + $amount;
            $accounts->updateOne(['user_id' => $userId], ['$set' => ['balance' => $newBal, 'total_deposited' => $newDep, 'updated_at' => new UTCDateTime()]]);

            $txnId = recordTxn($transactions, $userId, $acc['account_no'], 'credit', $amount, 'Deposit via ' . strtoupper($method), 'Deposited by user', $method);
            respond(true, 'Deposit successful', ['transaction_id' => $txnId, 'new_balance' => $newBal]);
            break;
        }

        // ── WITHDRAW ──
        case 'withdraw': {
            $userId = trim($data['user_id'] ?? '');
            $amount = floatval($data['amount'] ?? 0);
            $dest   = trim($data['destination'] ?? '');

            if (!$userId || !$dest) { respond(false, 'user_id and destination required'); break; }
            if ($amount <= 0) { respond(false, 'Invalid amount'); break; }

            $acc = $accounts->findOne(['user_id' => $userId]);
            if (!$acc) { respond(false, 'Account not found'); break; }
            if ((float)$acc['balance'] < $amount) { respond(false, 'Insufficient balance'); break; }

            $newBal = (float)$acc['balance'] - $amount;
            $accounts->updateOne(['user_id' => $userId], ['$set' => ['balance' => $newBal, 'updated_at' => new UTCDateTime()]]);

            $txnId = recordTxn($transactions, $userId, $acc['account_no'], 'debit', -$amount, 'Withdrawal to ' . $dest, 'Withdrawal request', 'bank_transfer');
            respond(true, 'Withdrawal initiated', ['transaction_id' => $txnId, 'new_balance' => $newBal]);
            break;
        }

        // ── TRANSFER ──
        case 'transfer': {
            $fromUserId  = trim($data['from_user_id'] ?? '');
            $toAccountNo = trim($data['to_account_no'] ?? '');
            $amount      = floatval($data['amount'] ?? 0);
            $note        = trim($data['note'] ?? '');

            if (!$fromUserId || !$toAccountNo) { respond(false, 'from_user_id and to_account_no required'); break; }
            if ($amount <= 0) { respond(false, 'Invalid amount'); break; }

            $fromAcc = $accounts->findOne(['user_id' => $fromUserId]);
            $toAcc   = $accounts->findOne(['account_no' => $toAccountNo]);
            if (!$fromAcc) { respond(false, 'Sender account not found'); break; }
            if (!$toAcc)   { respond(false, 'Recipient account not found. Check account number.'); break; }
            if ((float)$fromAcc['balance'] < $amount) { respond(false, 'Insufficient balance'); break; }

            // Debit sender
            $accounts->updateOne(['user_id' => $fromUserId], ['$set' => ['balance' => (float)$fromAcc['balance'] - $amount, 'updated_at' => new UTCDateTime()]]);
            recordTxn($transactions, $fromUserId, $fromAcc['account_no'], 'debit', -$amount, 'Transfer to ' . $toAccountNo, $note, 'internal_transfer');

            // Credit receiver
            $accounts->updateOne(['account_no' => $toAccountNo], ['$set' => ['balance' => (float)$toAcc['balance'] + $amount, 'updated_at' => new UTCDateTime()]]);
            recordTxn($transactions, (string)$toAcc['user_id'], $toAccountNo, 'credit', $amount, 'Transfer from ' . $fromAcc['account_no'], $note, 'internal_transfer');

            respond(true, 'Transfer successful', ['transferred' => $amount, 'to' => $toAccountNo]);
            break;
        }

        // ── LOAN REPAYMENT ──
        case 'loan_repayment': {
            $userId   = trim($data['user_id'] ?? '');
            $loanRef  = trim($data['loan_ref'] ?? '');
            $amount   = floatval($data['amount'] ?? 0);

            if (!$userId || !$loanRef) { respond(false, 'user_id and loan_ref required'); break; }
            if ($amount <= 0) { respond(false, 'Invalid amount'); break; }

            $acc = $accounts->findOne(['user_id' => $userId]);
            if (!$acc) { respond(false, 'Account not found'); break; }
            if ((float)$acc['balance'] < $amount) { respond(false, 'Insufficient balance for repayment'); break; }

            $newBal = (float)$acc['balance'] - $amount;
            $accounts->updateOne(['user_id' => $userId], ['$set' => ['balance' => $newBal, 'updated_at' => new UTCDateTime()]]);

            // Update the loan's outstanding in loan_applications collection
            $loans = $db->selectCollection('loan_applications');
            $loans->updateOne(['reference_id' => $loanRef], ['$inc' => ['outstanding' => -$amount]]);

            $txnId = recordTxn($transactions, $userId, $acc['account_no'], 'loan', -$amount, 'Loan Repayment — ' . $loanRef, $loanRef, 'loan_repayment');
            respond(true, 'Repayment successful', ['transaction_id' => $txnId, 'new_balance' => $newBal]);
            break;
        }

        // ── TRANSACTIONS ──
        case 'transactions': {
            $userId = trim($_GET['user_id'] ?? '');
            if (!$userId) { respond(false, 'user_id required'); break; }

            $limit = min(intval($_GET['limit'] ?? 50), 200);
            $cursor = $transactions->find(
                ['user_id' => $userId],
                ['sort' => ['created_at' => -1], 'limit' => $limit]
            );

            $txns = [];
            foreach ($cursor as $t) {
                $txns[] = [
                    'id'       => (string)$t['_id'],
                    'type'     => $t['type'],
                    'title'    => $t['title'],
                    'amount'   => (float)$t['amount'],
                    'note'     => $t['note'] ?? '',
                    'method'   => $t['method'] ?? '',
                    'date'     => date('Y-m-d', (int)($t['created_at']->toDateTime()->format('U'))),
                ];
            }
            respond(true, 'Transactions fetched', ['transactions' => $txns, 'count' => count($txns)]);
            break;
        }

        default:
            respond(false, 'Unknown action: ' . $action);
    }

} catch (Exception $e) {
    respond(false, 'Server error: ' . $e->getMessage());
}


// ── HELPERS ──

function recordTxn($col, string $userId, string $accNo, string $type, float $amount, string $title, string $note, string $method): string {
    $r = $col->insertOne([
        'user_id'    => $userId,
        'account_no' => $accNo,
        'type'       => $type,   // credit | debit | loan
        'amount'     => $amount,
        'title'      => $title,
        'note'       => $note,
        'method'     => $method,
        'created_at' => new UTCDateTime(),
    ]);
    return (string)$r->getInsertedId();
}

function respond(bool $success, string $message, array $data = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}
?>
