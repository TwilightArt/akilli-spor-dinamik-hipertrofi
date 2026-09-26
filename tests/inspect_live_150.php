<?php
$ch = curl_init('http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/samet_cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/samet_cookie.txt');

$loginPage = curl_exec($ch);
preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage, $m);
$csrf = $m[1] ?? '';

$postData = http_build_query([
    'csrf_token' => $csrf,
    'email'      => 'sametltnz@gmail.com',
    'password'   => 'password123'
]);

curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

// Now GET /workout/live/150
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/workout/live/150');
curl_setopt($ch, CURLOPT_POST, false);
$liveHtml = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP CODE: {$code}\n";
echo "HTML LENGTH: " . strlen($liveHtml) . "\n";

// Save full HTML to inspect
file_put_contents(__DIR__ . '/live_150_output.html', $liveHtml);
echo "Saved to live_150_output.html\n";

curl_close($ch);
@unlink(__DIR__ . '/samet_cookie.txt');
