<?php
require_once __DIR__ . '/../config/database.php';
$db = App\Config\Database::getConnection();
$cols = $db->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "Col: {$c['name']} ({$c['type']})\n";
}
