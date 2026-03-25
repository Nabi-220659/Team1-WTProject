<?php
require 'vendor/autoload.php';
require_once __DIR__ . '/Backend/index/config/db.php';

try {
    $db = getDB();
    $email = 'partner@demo.com';
    $refId = 'P-999';

    // 1. Remove from partner_applications
    $db->selectCollection('partner_applications')->deleteMany(['email' => $email]);
    echo "✅ Removed partner@demo.com from partner_applications." . PHP_EOL;

    // 2. Remove from users
    $db->selectCollection('users')->deleteMany(['email' => $email]);
    echo "✅ Removed partner@demo.com from users." . PHP_EOL;

    // 3. Remove from user_agent_assignments
    $db->selectCollection('user_agent_assignments')->deleteMany(['partner_reference_id' => $refId]);
    echo "✅ Removed P-999 assignments from user_agent_assignments." . PHP_EOL;

    // 4. Clear local_agent_ref from any users who were assigned to this demo partner
    $db->selectCollection('users')->updateMany(
        ['local_agent_ref' => $refId],
        ['$unset' => [
            'local_agent_ref'   => "",
            'local_agent_name'  => "",
            'local_agent_email' => "",
            'agent_assigned_at' => ""
        ]]
    );
    echo "✅ Unset P-999 from any assigned users." . PHP_EOL;

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
?>
