<?php
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../config/database.php';

use App\Config\Database;

$db = Database::getConnection();

// Add a test user if needed
$userId = 9999;
$db->exec("DELETE FROM users WHERE id = $userId");
$db->exec("INSERT INTO users (id, name, email, password_hash, gender, birth_date, height_cm, current_weight_kg) VALUES ($userId, 'Test', 'test9999@test.com', 'hash', 'male', '1990-01-01', 180, 80)");
$db->exec("DELETE FROM injury_reports WHERE user_id = $userId");

$joints = [];
for ($i = 0; $i < 5; $i++) {
    $joints[] = 'joint_' . $i;
}

$today = date('Y-m-d');

// Benchmark N+1
$startN1 = microtime(true);

$insertStmt = $db->prepare("
    INSERT INTO injury_reports (user_id, joint_area, severity, reported_date, notes)
    VALUES (:uid, :joint, 'moderate', :rdate, 'Kullanıcı profilinden bildirildi')
");
foreach ($joints as $joint) {
    $insertStmt->execute([
        'uid'   => $userId,
        'joint' => $joint,
        'rdate' => $today
    ]);
}
$endN1 = microtime(true);
$timeN1 = $endN1 - $startN1;

// Benchmark Bulk
$db->exec("DELETE FROM injury_reports WHERE user_id = $userId");
$startBulk = microtime(true);

if (!empty($joints)) {
    $values = [];
    $params = [
        'uid' => $userId,
        'rdate' => $today
    ];

    $i = 0;
    foreach ($joints as $joint) {
        $values[] = "(:uid, :joint_$i, 'moderate', :rdate, 'Kullanıcı profilinden bildirildi')";
        $params["joint_$i"] = $joint;
        $i++;
    }

    $sql = "INSERT INTO injury_reports (user_id, joint_area, severity, reported_date, notes) VALUES " . implode(", ", $values);
    $db->prepare($sql)->execute($params);
}
$endBulk = microtime(true);
$timeBulk = $endBulk - $startBulk;

echo "N+1 Inserts: " . number_format($timeN1, 5) . " seconds\n";
echo "Bulk Insert: " . number_format($timeBulk, 5) . " seconds\n";

$db->exec("DELETE FROM users WHERE id = $userId");
$db->exec("DELETE FROM injury_reports WHERE user_id = $userId");
