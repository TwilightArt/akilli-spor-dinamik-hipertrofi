<?php
require_once __DIR__ . '/../config/database.php';
$db = \App\Config\Database::getConnection();
$row = $db->query('SELECT pd.id as day_id, up.id as prog_id, up.user_id, u.email, u.name 
    FROM program_days pd 
    JOIN user_programs up ON pd.program_id = up.id 
    JOIN users u ON up.user_id = u.id 
    WHERE pd.id = 150')->fetch();
print_r($row);

// Also check active session of this user
if ($row) {
    $uid = $row['user_id'];
    $sess = $db->query("SELECT * FROM workout_sessions WHERE user_id = {$uid} ORDER BY id DESC LIMIT 5")->fetchAll();
    echo "SESSIONS OF USER {$uid}:\n";
    print_r($sess);
}
