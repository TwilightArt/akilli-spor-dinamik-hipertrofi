<?php
// Test login as Samet, then GET /profile and POST /profile
$ch = curl_init('http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/test_samet_cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/test_samet_cookie.txt');

$loginPage = curl_exec($ch);
preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage, $m);
$csrf = $m[1] ?? '';

// Post login
$postData = http_build_query([
    'csrf_token' => $csrf,
    'email'      => 'sametltnz@gmail.com',
    'password'   => 'password123'
]);

curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$res = curl_exec($ch);

// Now GET /profile
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/profile');
curl_setopt($ch, CURLOPT_POST, false);
$profileHtml = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "GET /profile HTTP code: {$code}\n";
echo "Profile contains Samet Altınöz: " . (strpos($profileHtml, 'Samet Altınöz') !== false ? 'YES' : 'NO') . "\n";
echo "Profile contains Boy Uzunluğu: " . (strpos($profileHtml, 'Boy Uzunluğu') !== false ? 'YES' : 'NO') . "\n";

// Now test GET /workout/live/4
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/workout/live/4');
$liveHtml = curl_exec($ch);
$liveCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "GET /workout/live/4 HTTP code: {$liveCode}\n";
echo "Live/4 has no Fatal Error: " . (strpos($liveHtml, 'Fatal error') === false ? 'YES' : 'NO') . "\n";

curl_close($ch);
@unlink(__DIR__ . '/test_samet_cookie.txt');
