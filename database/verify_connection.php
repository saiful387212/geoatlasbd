<?php
declare(strict_types=1);

/**
 * Database health check for local development.
 *
 * Usage (CLI):
 *   php database/verify_connection.php
 *
 * Usage (browser, dev-only):
 *   http://geoatlas.local/../../database/verify_connection.php  ← will NOT work
 *   Copy this file's logic to a temporary public/health.php if you prefer browser testing.
 *
 * This file lives outside public/ on purpose: it prints diagnostics.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this script from the command line only.\n");
}

require __DIR__ . '/../app/bootstrap.php';

use App\Services\Db;

Db::init(require __DIR__ . '/../config/database.php');
$pdo = Db::pdo();

$tests = [];

// 1. Server version
$tests['MySQL version'] = $pdo->query('SELECT VERSION()')->fetchColumn();

// 2. Charset
$charset = $pdo->query("SELECT @@character_set_connection")->fetchColumn();
$collation = $pdo->query("SELECT @@collation_connection")->fetchColumn();
$tests['Connection charset'] = "{$charset} / {$collation}";

// 3. Tables present
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$expected = ['audit_log','categories','contact_messages','project_files','project_images','projects','users'];
$missing = array_diff($expected, $tables);
$tests['Tables found'] = implode(', ', $tables) ?: '(none)';
$tests['Missing tables'] = $missing ? implode(', ', $missing) : 'none';

// 4. Row counts
foreach (['users','categories','projects'] as $t) {
    $tests["Rows in {$t}"] = (int) $pdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
}

// 5. Prepared-statement roundtrip
$stmt = $pdo->prepare('SELECT :a AS a, :b AS b');
$stmt->execute([':a' => 1, ':b' => 'ok']);
$tests['Prepared statement'] = json_encode($stmt->fetch());

// 6. FK enforcement
$fk = $pdo->query("SELECT @@foreign_key_checks")->fetchColumn();
$tests['foreign_key_checks'] = $fk;

echo "=== GeoAtlas Bangladesh — DB health check ===\n\n";
foreach ($tests as $label => $value) {
    printf("%-24s %s\n", $label . ':', $value);
}
echo "\nAll good.\n";