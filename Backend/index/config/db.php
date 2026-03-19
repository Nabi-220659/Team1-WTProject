<?php
ini_set('pcre.jit', '0');
/**
 * db.php — MongoDB Connection Configuration
 *
 * Requires: MongoDB PHP Driver (ext-mongodb) + MongoDB PHP Library
 * Install library: composer require mongodb/mongodb
 *
 * MongoDB runs on: mongodb://localhost:27017
 * Database name  : fundbee_db
 */

require_once __DIR__ . '/../../../vendor/autoload.php';  // Composer autoloader (vendor/ is at project root)

use MongoDB\Client;

define('MONGO_URI',  'mongodb://localhost:27017');
define('MONGO_DB',   'fundbee_db');

/**
 * Returns the MongoDB database instance.
 * Usage in other files:  $db = getDB();
 *                        $collection = $db->selectCollection('loan_products');
 */
function getDB(): MongoDB\Database {
    try {
        $client = new Client(MONGO_URI);
        return $client->selectDatabase(MONGO_DB);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'MongoDB connection failed: ' . $e->getMessage()
        ]);
        exit;
    }
}
?>
