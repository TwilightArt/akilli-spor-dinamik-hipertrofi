<?php
// 1. GET /register to get session cookie and CSRF token
$ch = curl_init('http://127.0.0.1:8000/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/test_cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/test_cookie.txt');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "GET /register HTTP: {$httpCode}\n";

// Extract CSRF token
preg_match('/name="csrf_token" value="([^"]+)"/', $response, $matches);
$csrfToken = $matches[1] ?? '';
echo "CSRF Token: {$csrfToken}\n";

if (!$csrfToken) {
    echo "FAILED: CSRF token not found in register page!\n";
    echo substr($response, 0, 1000) . "\n";
    exit(1);
}

// 2. POST /register
$testEmail = 'newuser_' . time() . '@example.com';
$postData = http_build_query([
    'csrf_token'        => $csrfToken,
    'name'              => 'Yeni Test Sporcu',
    'email'             => $testEmail,
    'password'          => 'gucluSifre123!',
    'gender'            => 'male',
    'birth_date'        => '1998-07-22',
    'height_cm'         => '183.0',
    'current_weight_kg' => '82.5'
]);

curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/register');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // see redirect header

$postResponse = curl_exec($ch);
$postHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "POST /register HTTP: {$postHttpCode}\n";

// Check redirect location
preg_match('/Location: ([^\r\n]+)/i', $postResponse, $locMatches);
echo "Redirect Location: " . ($locMatches[1] ?? 'None') . "\n";

// Follow redirect if 302
if ($postHttpCode === 302 && isset($locMatches[1])) {
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000' . $locMatches[1]);
    curl_setopt($ch, CURLOPT_POST, false);
    $finalResponse = curl_exec($ch);
    $finalCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "Followed redirect to {$locMatches[1]} - HTTP: {$finalCode}\n";
    echo "Contains Assessment: " . (strpos($finalResponse, 'Aşama 1') !== false ? 'YES' : 'NO') . "\n";
}
curl_close($ch);
@unlink(__DIR__ . '/test_cookie.txt');
