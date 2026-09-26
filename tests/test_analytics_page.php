<?php
$ch = curl_init('http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/test_analytics_cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/test_analytics_cookie.txt');

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

// Request /analytics
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/analytics');
curl_setopt($ch, CURLOPT_POST, false);
$html = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
file_put_contents(__DIR__ . '/analytics_output.html', $html);

echo "Analytics Page HTTP Code: {$code}\n";
echo "Page Length: " . strlen($html) . " bytes\n";

if (strpos($html, 'Hareket Hareket Zirve Ağırlıklar') !== false) {
    echo "✓ Found: 'Hareket Hareket Zirve Ağırlıklar' section\n";
} else {
    echo "✗ MISSING: 'Hareket Hareket Zirve Ağırlıklar'\n";
}

if (strpos($html, 'exerciseTimelineChart') !== false) {
    echo "✓ Found: 'exerciseTimelineChart' canvas\n";
} else {
    echo "✗ MISSING: 'exerciseTimelineChart'\n";
}

if (strpos($html, 'allPeaksComparisonChart') !== false) {
    echo "✓ Found: 'allPeaksComparisonChart' canvas\n";
} else {
    echo "✗ MISSING: 'allPeaksComparisonChart'\n";
}

curl_close($ch);
@unlink(__DIR__ . '/test_analytics_cookie.txt');
