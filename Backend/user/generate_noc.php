<?php
/**
 * generate_noc.php  —  Generates and streams a NOC (No Objection Certificate) PDF
 * Called from user_api.php download_noc action.
 *
 * Requires: composer require dompdf/dompdf
 * If dompdf is not installed, falls back to plain HTML download.
 *
 * GET params:  loan_id, token (user auth token)
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../index/config/db.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

session_start();

$loanId = trim($_GET['loan_id'] ?? '');
$token  = trim($_GET['token']   ?? ($_SERVER['HTTP_X_USER_TOKEN'] ?? $_SESSION['user_token'] ?? ''));

if (!$loanId) { http_response_code(400); die('loan_id required'); }

$db = getDB();

// Auth
$session = $token
    ? $db->selectCollection('user_sessions')->findOne(['token' => $token, 'expires_at' => ['$gt' => new UTCDateTime()]])
    : null;

if ($session) {
    $userId = $session['user_id'];
} else {
    // Dev fallback
    $demoUser = $db->selectCollection('users')->findOne(['email' => 'arjun.sharma@email.com']);
    if (!$demoUser) { http_response_code(401); die('Unauthorised'); }
    $userId = (string)$demoUser['_id'];
}

// Fetch loan
$loan = $db->selectCollection('loan_applications')->findOne(['application_id' => $loanId, 'user_id' => $userId]);
if (!$loan) { http_response_code(404); die('Loan not found'); }
if (!in_array($loan['status'] ?? '', ['closed','completed'])) { http_response_code(403); die('NOC only available for closed loans'); }

// Fetch user
$user = $db->selectCollection('users')->findOne(['_id' => new ObjectId($userId)]);

$userName  = $user['name']   ?? 'User';
$loanType  = ucwords(($loan['loan_type']  ?? 'Loan') . ' Loan');
$amount    = number_format((float)($loan['amount'] ?? 0));
$closedAt  = isset($loan['closed_at']) ? $loan['closed_at']->toDateTime()->format('d M Y') : date('d M Y');
$nocDate   = date('d M Y');
$nocNumber = 'NOC-' . strtoupper(substr(md5($loanId), 0, 8));

$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; font-size: 14px; color: #1c2a3a; margin: 40px; }
  .header { text-align: center; border-bottom: 2px solid #0b1d3a; padding-bottom: 20px; margin-bottom: 30px; }
  .logo   { font-size: 28px; font-weight: 900; color: #0b1d3a; letter-spacing: 2px; }
  .logo span { color: #f5a623; }
  .noc-title { font-size: 20px; font-weight: bold; margin-top: 10px; text-transform: uppercase; letter-spacing: 3px; }
  .noc-no   { color: #6b7a8d; font-size: 12px; margin-top: 4px; }
  .body     { line-height: 2; }
  .field    { font-weight: bold; }
  .seal     { margin-top: 60px; display: flex; justify-content: space-between; }
  .sig-box  { width: 200px; border-top: 1px solid #0b1d3a; padding-top: 8px; font-size: 12px; color: #6b7a8d; }
  .footer   { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e0e0e0; font-size: 11px; color: #6b7a8d; text-align: center; }
</style>
</head>
<body>
  <div class="header">
    <div class="logo">FUND<span>BEE</span></div>
    <div style="font-size:12px;color:#6b7a8d;margin-top:4px">NBFC Registered with Reserve Bank of India</div>
    <div class="noc-title">No Objection Certificate</div>
    <div class="noc-no">Reference: {$nocNumber} &nbsp;|&nbsp; Date: {$nocDate}</div>
  </div>

  <div class="body">
    <p>To Whom It May Concern,</p>
    <br>
    <p>
      This is to certify that <span class="field">{$userName}</span> had availed a
      <span class="field">{$loanType}</span> of <span class="field">₹{$amount}</span>
      (Loan ID: <span class="field">{$loanId}</span>) from FUNDBEE Financial Services Pvt. Ltd.
    </p>
    <br>
    <p>
      We confirm that the borrower has <strong>repaid the entire outstanding loan amount</strong>
      including principal, interest, and applicable charges in full.
      The loan account was <strong>closed on {$closedAt}</strong>.
    </p>
    <br>
    <p>
      FUNDBEE Financial Services Pvt. Ltd. has <strong>no objection</strong> to the above-mentioned
      borrower availing any credit facilities from any financial institution.
      There are no dues, liabilities, or encumbrances pending against this loan account.
    </p>
    <br>
    <p>
      We have updated the credit bureau (CIBIL) records accordingly.
      The loan closure will reflect on the borrower's credit report within 30 days.
    </p>
    <br>
    <p>This certificate is issued at the request of the borrower for their records.</p>
  </div>

  <div class="seal">
    <div class="sig-box">
      <br>
      Authorised Signatory<br>
      FUNDBEE Financial Services Pvt. Ltd.
    </div>
    <div class="sig-box">
      <br>
      Branch Manager / Credit Operations<br>
      Date: {$nocDate}
    </div>
  </div>

  <div class="footer">
    FUNDBEE Financial Services Pvt. Ltd. &nbsp;|&nbsp; CIN: U65999MH2014PTC123456 &nbsp;|&nbsp;
    RBI NBFC Reg No: N-13.02123 &nbsp;|&nbsp; support@fundbee.in &nbsp;|&nbsp; 1800-XXX-XXXX<br>
    This is a computer-generated document and is valid without a physical signature.
  </div>
</body>
</html>
HTML;

// Try dompdf if available, else plain HTML
if (class_exists('Dompdf\Dompdf')) {
    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="NOC_' . $loanId . '.pdf"');
    echo $dompdf->output();
} else {
    // Fallback: serve as HTML for printing
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="NOC_' . $loanId . '.html"');
    echo $html;
}
?>
