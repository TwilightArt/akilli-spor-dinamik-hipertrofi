<?php
$ch = curl_init('http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/test_prof_cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/test_prof_cookie.txt');

$loginHtml = curl_exec($ch);
preg_match('/name="csrf_token" value="([^"]+)"/', $loginHtml, $m);
$csrf = $m[1] ?? '';

// Login as samet
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $csrf,
    'email'      => 'samet@samet.com',
    'password'   => 'samet123'
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

// Get Profile page to grab CSRF
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/profile');
curl_setopt($ch, CURLOPT_POST, false);
$profHtml = curl_exec($ch);
preg_match('/name="csrf_token" value="([^"]+)"/', $profHtml, $m);
$profCsrf = $m[1] ?? '';

// Update Profile with 4 calisthenic test parameters
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/profile');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token'         => $profCsrf,
    'name'               => 'Samet Kılıç',
    'gender'             => 'male',
    'birth_date'         => '1996-05-12',
    'height_cm'          => 180.0,
    'current_weight_kg'  => 78.5,
    'pushups_count'      => 15,
    'chinups_count'      => 5,
    'squats_count'       => 25,
    'plank_seconds'      => 60,
    'fitness_eval_mode'  => 'auto',
    'regenerate_program' => '1',
    'injuries'           => ['shoulder']
]));
$updateResult = curl_exec($ch);
$upCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Profile Update HTTP Code: {$upCode}\n";

// Verify database values
require_once __DIR__ . '/../config/database.php';
$db = App\Config\Database::getConnection();
$user = $db->query("SELECT * FROM users WHERE email = 'samet@samet.com'")->fetch(PDO::FETCH_ASSOC);

echo "\nUpdated User Database Record:\n";
echo "Name: {$user['name']}\n";
echo "Weight: {$user['current_weight_kg']} kg\n";
echo "Height: {$user['height_cm']} cm\n";
echo "Level: {$user['fitness_level']}\n";
echo "Pushups: {$user['pushups_count']}\n";
echo "Chinups: {$user['chinups_count']}\n";
echo "Squats: {$user['squats_count']}\n";
echo "Plank: {$user['plank_seconds']}s\n";
echo "Calisthenic Score: {$user['calisthenic_score']} / 100\n";

// Check new program exercises
$progs = $db->query("SELECT id FROM user_programs WHERE user_id = {$user['id']} ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($progs) {
    $progId = $progs['id'];
    $day = $db->query("SELECT id, focus_area FROM program_days WHERE program_id = {$progId} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($day) {
        $exs = $db->query("SELECT e.name, pe.target_sets, pe.target_reps_min, pe.target_reps_max FROM program_exercises pe JOIN exercises e ON pe.exercise_id = e.id WHERE pe.program_day_id = {$day['id']}")->fetchAll(PDO::FETCH_ASSOC);
        echo "\nRegenerated Program Day 1 ({$day['focus_area']}) Exercises:\n";
        foreach ($exs as $e) {
            echo "- {$e['name']} ({$e['target_sets']} set x {$e['target_reps_min']}-{$e['target_reps_max']})\n";
        }
    }
}

curl_close($ch);
@unlink(__DIR__ . '/test_prof_cookie.txt');
