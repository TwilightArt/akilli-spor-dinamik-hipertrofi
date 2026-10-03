<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../models/User.php';

use App\Models\User;
use App\Config\Database;

$passed = 0;
$failed = 0;

function assertTest(string $name, bool $condition, string $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$name}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$name} - Details: {$details}\n";
        $failed++;
    }
}

echo "================================================================\n";
echo "User Model - updatePassword Tests\n";
echo "================================================================\n\n";

$db = Database::getConnection();
$db->beginTransaction();

try {
    $userModel = new User();

    // 1. Create a dummy user
    $email = 'test_pwd_update_' . time() . '@example.com';
    $userId = $userModel->register(
        'Test User',
        $email,
        'OldPassword123',
        'male',
        '1990-01-01',
        180.0,
        75.0,
        'beginner'
    );

    $userBefore = $userModel->findById($userId);
    assertTest("User created successfully", $userId > 0, "ID: $userId");

    // 2. Update password
    $newPassword = 'NewStrongPassword456!';
    $updateResult = $userModel->updatePassword($userId, $newPassword);

    assertTest("updatePassword returns true", $updateResult === true, "Result: " . var_export($updateResult, true));

    // 3. Verify changes in DB
    $userAfter = $userModel->findById($userId);

    assertTest(
        "Password hash has changed",
        $userBefore['password_hash'] !== $userAfter['password_hash'],
        "Old: {$userBefore['password_hash']}, New: {$userAfter['password_hash']}"
    );

    assertTest(
        "New password verifies correctly against DB hash",
        password_verify($newPassword, $userAfter['password_hash']),
        "Hash: {$userAfter['password_hash']}"
    );

    assertTest(
        "Old password no longer verifies",
        !password_verify('OldPassword123', $userAfter['password_hash']),
        "Hash: {$userAfter['password_hash']}"
    );

    // Check Argon2id specifics
    $hashInfo = password_get_info($userAfter['password_hash']);
    assertTest(
        "Password was hashed using ARGON2ID",
        $hashInfo['algoName'] === 'argon2id',
        "Algo: " . ($hashInfo['algoName'] ?? 'unknown')
    );

} catch (\Exception $e) {
    echo "Exception occurred: " . $e->getMessage() . "\n";
} finally {
    $db->rollBack();
}

echo "\n================================================================\n";
echo "SUMMARY: Passed: {$passed} | Failed: {$failed}\n";
echo "================================================================\n";

// Optional: Exit with non-zero status if tests failed
if ($failed > 0) {
    exit(1);
}
exit(0);
