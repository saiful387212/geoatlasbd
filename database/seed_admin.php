<?php
declare(strict_types=1);

/**
 * Usage (from project root):
 *   php database/seed_admin.php "admin@geoatlas.local" "YourStrongPassword" "Admin Name"
 *
 * The password is only used to generate an Argon2id hash — it is never stored in cleartext.
 * Safe to re-run: updates the existing admin's hash/name if email already exists.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from the command line.\n");
}

require __DIR__ . '/../app/bootstrap.php';

App\Services\Db::init(require __DIR__ . '/../config/database.php');

$email = $argv[1] ?? null;
$pass  = $argv[2] ?? null;
$name  = $argv[3] ?? 'Site Administrator';

if (!$email || !$pass) {
    fwrite(STDERR, "Usage: php database/seed_admin.php <email> <password> [name]\n");
    exit(1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Invalid email address.\n");
    exit(1);
}
if (strlen($pass) < 12) {
    fwrite(STDERR, "Password must be at least 12 characters.\n");
    exit(1);
}

$hash = password_hash($pass, PASSWORD_ARGON2ID);
if ($hash === false) {
    fwrite(STDERR, "Argon2id not available in this PHP build. Enable it in php.ini.\n");
    exit(1);
}

$pdo = \App\Services\Db::pdo();

$stmt = $pdo->prepare(
    'SELECT id FROM users WHERE email = :email LIMIT 1'
);
$stmt->execute([':email' => $email]);
$existing = $stmt->fetchColumn();

if ($existing !== false) {
    $update = $pdo->prepare(
        'UPDATE users
            SET password_hash = :hash, name = :name, is_active = 1,
                failed_attempts = 0, locked_until = NULL
          WHERE id = :id'
    );
    $update->execute([
        ':hash' => $hash,
        ':name' => $name,
        ':id'   => $existing,
    ]);
    echo "Updated admin user #{$existing} ({$email}).\n";
} else {
    $insert = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role)
         VALUES (:name, :email, :hash, "admin")'
    );
    $insert->execute([
        ':name'  => $name,
        ':email' => $email,
        ':hash'  => $hash,
    ]);
    echo "Created admin user #" . $pdo->lastInsertId() . " ({$email}).\n";
}

echo "Done. You can now log in with the email/password you provided.\n";
echo "IMPORTANT: change this password on first login once the admin UI exists (Phase 6).\n";