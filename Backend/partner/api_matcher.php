<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

try {
    $client    = new Client('mongodb://localhost:27017');
    $db        = $client->selectDatabase('fundbee_db');
    $partnerId = 1; // read from session/JWT in production
    $now       = date('Y-m-d H:i:s');

    /* =========================================================
       POST — actions
       ========================================================= */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        switch ($action) {

            /* ---- Fund a deal ---- */
            case 'fund_deal':
                $dealId = $input['deal_id'] ?? '';
                if (!$dealId) {
                    echo json_encode(['success' => false, 'error' => 'deal_id required']);
                    exit;
                }

                // Check it's not already funded
                $existing = $db->partner_funded_deals->findOne([
                    'partner_id' => $partnerId,
                    'deal_id'    => $dealId
                ]);
                if ($existing) {
                    echo json_encode(['success' => false, 'error' => 'Deal already funded.']);
                    exit;
                }

                // Fetch match details
                $match = $db->partner_matches->findOne([
                    'partner_id' => $partnerId,
                    'loan_id'    => $dealId
                ]);

                // Record funded deal
                $db->partner_funded_deals->insertOne([
                    'partner_id'  => $partnerId,
                    'deal_id'     => $dealId,
                    'match_data'  => $match ? iterator_to_array($match) : [],
                    'funded_at'   => $now,
                    'status'      => 'funded'
                ]);

                // Mark match as funded in partner_matches
                $db->partner_matches->updateOne(
                    ['partner_id' => $partnerId, 'loan_id' => $dealId],
                    ['$set' => ['funded' => true, 'funded_at' => $now]]
                );

                // Log in deal_actions
                $db->deal_actions->insertOne([
                    'partner_id' => $partnerId,
                    'deal_id'    => $dealId,
                    'action'     => 'funded',
                    'timestamp'  => $now
                ]);

                echo json_encode(['success' => true, 'message' => '⚡ Deal funded! Disbursement initiated.']);
                break;

            /* ---- Pass on a deal ---- */
            case 'pass_deal':
                $dealId = $input['deal_id'] ?? '';
                $reason = $input['reason']  ?? 'No reason given';
                if (!$dealId) {
                    echo json_encode(['success' => false, 'error' => 'deal_id required']);
                    exit;
                }

                $db->deal_actions->insertOne([
                    'partner_id' => $partnerId,
                    'deal_id'    => $dealId,
                    'action'     => 'passed',
                    'reason'     => $reason,
                    'timestamp'  => $now
                ]);

                // Mark match as passed
                $db->partner_matches->updateOne(
                    ['partner_id' => $partnerId, 'loan_id' => $dealId],
                    ['$set' => ['passed' => true, 'passed_at' => $now]]
                );

                echo json_encode(['success' => true, 'message' => 'Deal passed and logged.']);
                break;

            /* ---- Save Preferences ---- */
            case 'save_prefs':
                $prefs = [
                    'partner_id'   => $partnerId,
                    'min_yield'    => $input['min_yield']    ?? '10%',
                    'min_cibil'    => (int)($input['min_cibil']    ?? 700),
                    'max_exposure' => $input['max_exposure'] ?? '₹20L',
                    'segments'     => $input['segments']     ?? ['Personal', 'Business', 'Home'],
                    'auto_fund'    => (bool)($input['auto_fund']   ?? false),
                    'daily_digest' => (bool)($input['daily_digest'] ?? true),
                    'updated_at'   => $now
                ];

                $db->partner_preferences->updateOne(
                    ['partner_id' => $partnerId],
                    ['$set' => $prefs],
                    ['upsert' => true]
                );

                // Re-rank matches based on new prefs (simple: update match scores)
                $matches = $db->partner_matches->find(['partner_id' => $partnerId])->toArray();
                $minCibil = $prefs['min_cibil'];
                foreach ($matches as $m) {
                    $cibil = $m['cibil'] ?? 0;
                    // Boost score if above min CIBIL
                    if ($cibil >= $minCibil) {
                        $db->partner_matches->updateOne(
                            ['_id' => $m['_id']],
                            ['$set' => ['pref_aligned' => true]]
                        );
                    }
                }

                echo json_encode(['success' => true, 'message' => '✅ Preferences saved! AI has re-ranked your matches.']);
                break;

            /* ---- Save Auto-Fund / Daily Digest toggles ---- */
            case 'save_toggle':
                $field = $input['field'] ?? '';
                $val   = (bool)($input['value'] ?? false);
                if (!in_array($field, ['auto_fund', 'daily_digest'])) {
                    echo json_encode(['success' => false, 'error' => 'Invalid toggle field']);
                    exit;
                }
                $db->partner_preferences->updateOne(
                    ['partner_id' => $partnerId],
                    ['$set' => [$field => $val, 'updated_at' => $now]],
                    ['upsert' => true]
                );
                echo json_encode(['success' => true, 'message' => "Setting updated."]);
                break;

            /* ---- Get deal action history ---- */
            case 'get_history':
                $dealId = $input['deal_id'] ?? '';
                $filter = $dealId
                    ? ['partner_id' => $partnerId, 'deal_id' => $dealId]
                    : ['partner_id' => $partnerId];

                $actions = $db->deal_actions->find(
                    $filter,
                    ['sort' => ['timestamp' => -1], 'limit' => 30]
                )->toArray();

                $result = [];
                foreach ($actions as $a) {
                    $arr = iterator_to_array($a);
                    unset($arr['_id']);
                    $result[] = $arr;
                }
                echo json_encode(['success' => true, 'history' => $result]);
                break;

            default:
                echo json_encode(['success' => false, 'error' => "Unknown action: $action"]);
        }
        exit;
    }

    /* =========================================================
       GET — load matches + KPIs + preferences
       ========================================================= */
    $matchesData = $db->partner_matches->find(
        ['partner_id' => $partnerId, 'passed' => ['$ne' => true], 'funded' => ['$ne' => true]]
    )->toArray();

    $matchesList = [];
    foreach ($matchesData as $m) {
        $match = iterator_to_array($m);
        unset($match['_id']);
        $matchesList[] = $match;
    }

    $kpiData   = $db->partner_matcher_kpis->findOne(['partner_id' => $partnerId]);
    $prefsData = $db->partner_preferences->findOne(['partner_id' => $partnerId]);

    if (!$kpiData && empty($matchesList)) {
        echo json_encode(['success' => false, 'error' => 'No matches found. Please run seed_db.php.']);
        exit;
    }

    $kpis  = iterator_to_array($kpiData);  unset($kpis['_id']);
    $prefs = $prefsData ? iterator_to_array($prefsData) : [
        'min_yield'    => '10%',
        'min_cibil'    => 700,
        'max_exposure' => '₹20L',
        'segments'     => ['Personal', 'Business', 'Home'],
        'auto_fund'    => false,
        'daily_digest' => true
    ];
    unset($prefs['_id']);

    // Count funded deals this session
    $fundedCount = $db->partner_funded_deals->countDocuments(['partner_id' => $partnerId]);

    echo json_encode([
        'success'      => true,
        'kpis'         => $kpis,
        'preferences'  => $prefs,
        'matches'      => $matchesList,
        'funded_count' => $fundedCount
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
