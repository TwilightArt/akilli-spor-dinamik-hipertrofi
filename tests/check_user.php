<?php
require_once __DIR__ . '/../config/database.php';
$db = App\Config\Database::getConnection();

$users = $db->query('SELECT * FROM users')->fetchAll();
echo "--- USERS ---\n";
foreach ($users as $u) {
    echo "ID: {$u['id']}, Name: {$u['name']}, Email: {$u['email']}, Level: {$u['fitness_level']}\n";
}

echo "\n--- PROGRAMS ---\n";
$progs = $db->query('SELECT * FROM user_programs')->fetchAll();
foreach ($progs as $p) {
    echo "Program ID: {$p['id']}, User ID: {$p['user_id']}, Type: {$p['program_type']}, Source: {$p['generation_source']}\n";
}

echo "\n--- SESSIONS ---\n";
$sess = $db->query('SELECT * FROM workout_sessions')->fetchAll();
foreach ($sess as $s) {
    echo "Session ID: {$s['id']}, User: {$s['user_id']}, ProgramDayId: " . var_export($s['program_day_id'], true) . ", Ended: " . var_export($s['ended_at'], true) . "\n";
}
