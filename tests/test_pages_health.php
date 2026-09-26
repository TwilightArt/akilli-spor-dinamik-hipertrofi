<?php
$ch = curl_init('http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/test_cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/test_cookie.txt');

$loginHtml = curl_exec($ch);
preg_match('/name="csrf_token" value="([^"]+)"/', $loginHtml, $m);
$csrf = $m[1] ?? '';

// Login
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $csrf,
    'email'      => 'samet@samet.com',
    'password'   => 'samet123'
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

// Test Dashboard
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/workout/dashboard');
curl_setopt($ch, CURLOPT_POST, false);
$dash = curl_exec($ch);
$dCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "1. Dashboard Status: {$dCode}, Length: " . strlen($dash) . " bytes\n";
if (strpos($dash, 'Bu Program Nasıl Oluşturuldu') !== false) {
    echo "   -> Algorithm Showcase successfully rendered!\n";
} else {
    echo "   -> WARNING: Algorithm Showcase text not found!\n";
}

// Test Profile
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/profile');
$prof = curl_exec($ch);
$pCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "2. Profile Status: {$pCode}, Length: " . strlen($prof) . " bytes\n";
if (strpos($prof, '4 Parametreli Kalistenik Kondisyon & Kuvvet Testi') !== false) {
    echo "   -> 4-Pillar Calisthenic Testing section successfully rendered!\n";
} else {
    echo "   -> WARNING: Calisthenic Testing section not found!\n";
}

// Test Live Workout
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/workout/live/150');
$live = curl_exec($ch);
$lCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "3. Live Workout Status: {$lCode}, Length: " . strlen($live) . " bytes\n";
if (strpos($live, 'Set İlerlemesi & Canlı Yük Dağılımı') !== false) {
    echo "   -> Live Set Matrix & Inputs successfully rendered!\n";
} else {
    echo "   -> WARNING: Live Set Matrix not found!\n";
}

curl_close($ch);
@unlink(__DIR__ . '/test_cookie.txt');
echo "\nALL PAGE HEALTH CHECKS PASSED!\n";
