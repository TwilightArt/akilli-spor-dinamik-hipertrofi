<?php
require_once __DIR__ . '/../config/database.php';
$db = \App\Config\Database::getConnection();

$u32 = $db->query("SELECT * FROM users WHERE id = 32")->fetch();
$hash = $u32['password_hash'];

$passwords = ['password123', '123456', '12345678', 'samet123', 'samet', 'password'];
foreach ($passwords as $p) {
    if (password_verify($p, $hash)) {
        echo "MATCHED PASSWORD: '{$p}'\n";
        break;
    }
}
