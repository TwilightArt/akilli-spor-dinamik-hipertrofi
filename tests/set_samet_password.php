<?php
require_once __DIR__ . '/../config/database.php';
$pdo = App\Config\Database::getConnection();
$hash = password_hash('password123', PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password_hash = :p WHERE id = 1")->execute(['p' => $hash]);
echo "Updated password for user 1 to 'password123'\n";
