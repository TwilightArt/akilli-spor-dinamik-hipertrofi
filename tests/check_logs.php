<?php
require_once __DIR__ . '/../config/database.php';
$db = App\Config\Database::getConnection();

$u32Logs = $db->query("SELECT * FROM workout_logs WHERE user_id = 32")->fetchAll(PDO::FETCH_ASSOC);
echo "User 32 workout logs count: " . count($u32Logs) . "\n";
