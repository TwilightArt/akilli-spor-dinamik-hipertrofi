<?php
require_once __DIR__ . '/../config/database.php';
$pdo = App\Config\Database::getConnection();

echo "--- SESSION #2 ---\n";
$stmt = $pdo->query("SELECT * FROM workout_sessions WHERE id = 2");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "--- ALL SESSIONS FOR USER 1 ---\n";
$stmt = $pdo->query("SELECT * FROM workout_sessions WHERE user_id = 1");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "--- USER PROGRAMS ---\n";
$stmt = $pdo->query("SELECT * FROM user_programs WHERE user_id = 1");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "--- PROGRAM DAYS FOR PROGRAM 4 ---\n";
$stmt = $pdo->query("SELECT * FROM program_days WHERE program_id = 4");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "--- EXERCISES FOR DAY 11 ---\n";
$stmt = $pdo->query("SELECT pe.*, e.name FROM program_exercises pe JOIN exercises e ON pe.exercise_id = e.id WHERE pe.program_day_id = 11");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
