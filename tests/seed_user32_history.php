<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AnalyticsService.php';

$analytics = new App\Services\AnalyticsService();

$db = App\Config\Database::getConnection();
$user32 = $db->query("SELECT id FROM users WHERE email = 'samet@samet.com'")->fetch(PDO::FETCH_ASSOC);

if ($user32) {
    $uid = (int)$user32['id'];
    $analytics->seedRealisticOverloadHistory($uid);
    echo "SUCCESS: Seeded 4-week realistic progressive overload history for User {$uid} (samet@samet.com)!\n";
} else {
    echo "User 32 not found\n";
}
