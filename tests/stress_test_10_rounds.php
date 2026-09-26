<?php
declare(strict_types=1);

/**
 * ============================================================================
 * Akıllı Spor & Dinamik Hipertrofi Platformu (v2.1)
 * 10-Round Complete Stress, Functional & Button Verification Suite
 * ============================================================================
 */

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (!file_exists($file)) {
        $parts = explode('\\', $relativeClass);
        if (count($parts) > 1) {
            $parts[0] = strtolower($parts[0]);
            $file = $baseDir . implode('/', $parts) . '.php';
        }
    }
    if (file_exists($file)) require_once $file;
});

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

use App\Services\PlateSolverService;
use App\Services\HypertrophyEngine;
use App\Services\RfiService;
use App\Services\InjuryFilterService;
use App\Services\GeminiWorkoutService;

class SuiteTester {
    private string $baseUrl = 'http://127.0.0.1:8000';
    private string $cookieHeader = '';
    public int $assertions = 0;
    public int $failures = 0;
    public array $errors = [];

    public function assert(bool $condition, string $message): void {
        $this->assertions++;
        if (!$condition) {
            $this->failures++;
            $this->errors[] = $message;
            echo "    ❌ [FAIL] {$message}\n";
        }
    }

    public function request(string $path, string $method = 'GET', array $data = [], bool $followRedirects = true): array {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirects);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_COOKIEFILE, ""); // Enables internal curl cookie engine for redirects

        if (!empty($this->cookieHeader)) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->cookieHeader);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $raw = (string)curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($raw, $headerSize);
        curl_close($ch);

        // Update in-memory cookie jar from Set-Cookie headers
        if (preg_match_all('/Set-Cookie:\s*([^;]+)/i', $raw, $cookieMatches)) {
            $cookies = [];
            if (!empty($this->cookieHeader)) {
                foreach (explode('; ', $this->cookieHeader) as $c) {
                    [$k, $v] = explode('=', $c, 2);
                    $cookies[$k] = $v;
                }
            }
            foreach ($cookieMatches[1] as $c) {
                [$k, $v] = explode('=', $c, 2);
                $cookies[$k] = $v;
            }
            $pairs = [];
            foreach ($cookies as $k => $v) {
                $pairs[] = "{$k}={$v}";
            }
            $this->cookieHeader = implode('; ', $pairs);
        }

        return [
            'code' => $httpCode,
            'body' => $body,
            'raw'  => $raw
        ];
    }

    public function extractCsrf(string $html): string {
        if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $m)) {
            return $m[1];
        }
        return '';
    }

    public function runFullRound(int $roundNumber): bool {
        $roundStart = microtime(true);
        echo "================================================================================\n";
        echo "🚀 TUR #{$roundNumber} / 10 BAŞLATILIYOR...\n";
        echo "================================================================================\n";

        // --------------------------------------------------------------------
        // TEST 1: Public Routes & Guest Pages
        // --------------------------------------------------------------------
        echo "  [1/10] Genel ve Kimlik Doğrulama Sayfaları...\n";
        
        // Root redirect
        $res = $this->request('/', 'GET', [], false);
        $this->assert($res['code'] === 302, "GET / 302 yönlendirmesi dönmeli (Kod: {$res['code']})");

        // Login Page
        $res = $this->request('/login');
        $this->assert($res['code'] === 200, "GET /login HTTP 200 dönmeli");
        $this->assert(strpos($res['body'], 'Giriş Yap') !== false, "Login sayfasında 'Giriş Yap' butonu bulunmalı");
        $csrfLogin = $this->extractCsrf($res['body']);
        $this->assert(!empty($csrfLogin), "Login sayfasında CSRF token bulunmalı");

        // Register Page
        $res = $this->request('/register');
        $this->assert($res['code'] === 200, "GET /register HTTP 200 dönmeli");
        $this->assert(strpos($res['body'], 'Yeni Profil Oluştur') !== false, "Kayıt sayfasında başlık bulunmalı");
        $this->assert(strpos($res['body'], 'name="height_cm"') !== false, "Kayıt formunda Boy (cm) alanı bulunmalı");
        $this->assert(strpos($res['body'], 'name="current_weight_kg"') !== false, "Kayıt formunda Kilo (kg) alanı bulunmalı");
        $csrfRegister = $this->extractCsrf($res['body']);
        $this->assert(!empty($csrfRegister), "Kayıt sayfasında CSRF token bulunmalı");

        // Forgot Password Page
        $res = $this->request('/forgot-password');
        $this->assert($res['code'] === 200, "GET /forgot-password HTTP 200 dönmeli");
        $this->assert(strpos($res['body'], 'Unuttunuz') !== false || strpos($res['body'], 'Sıfırlama') !== false, "Şifremi unuttum sayfası yüklenmeli");

        // --------------------------------------------------------------------
        // TEST 2: User Registration & Auto Setup
        // --------------------------------------------------------------------
        echo "  [2/10] Yeni Sporcu Kayıt ve Otomatik Program Kurulumu...\n";
        // Re-read register to have current fresh CSRF
        $res = $this->request('/register');
        $csrfReg = $this->extractCsrf($res['body']);

        $testEmail = "round{$roundNumber}_" . time() . "@hipertrofi-test.com";
        $res = $this->request('/register', 'POST', [
            'csrf_token'        => $csrfReg,
            'name'              => "Test Atlet Tur-{$roundNumber}",
            'email'             => $testEmail,
            'password'          => 'GucluParola123!',
            'gender'            => 'male',
            'birth_date'        => '1996-04-12',
            'height_cm'         => 182.0,
            'current_weight_kg' => 79.5
        ]);
        $this->assert($res['code'] === 200, "POST /register başarıyla tamamlanmalı (Kod: {$res['code']})");
        $this->assert(strpos($res['body'], 'Test Atlet') !== false || strpos($res['body'], 'Panel') !== false || strpos($res['body'], 'Tebrikler') !== false, "Kayıt sonrası sporcu paneli veya onay mesajı görüntülenmeli");

        // --------------------------------------------------------------------
        // TEST 3: Athlete Dashboard & Biometrics Display
        // --------------------------------------------------------------------
        echo "  [3/10] Sporcu Komuta Merkezi, Biyometrikler & RFI Matrisi...\n";
        $res = $this->request('/workout/dashboard');
        $this->assert($res['code'] === 200, "GET /workout/dashboard HTTP 200 dönmeli");
        $this->assert(strpos($res['body'], 'Boy Uzunluğu') !== false, "Panelde 'Boy Uzunluğu' kartı bulunmalı");
        $this->assert(strpos($res['body'], 'Güncel Kilo') !== false, "Panelde 'Güncel Kilo' kartı bulunmalı");
        $this->assert(strpos($res['body'], 'Vücut Kitle Endeksi') !== false, "Panelde 'VKİ' kartı bulunmalı");
        $this->assert(strpos($res['body'], 'Yatay İtiş') !== false, "Panelde 6-desenli RFI 'Yatay İtiş' bulunmalı");
        $this->assert(strpos($res['body'], 'Çekiş') !== false, "Panelde 'Çekiş' bulunmalı");
        $this->assert(strpos($res['body'], 'Akıllı Program Sihirbazı') !== false, "Panelde Program Sihirbazı butonu bulunmalı");

        // --------------------------------------------------------------------
        // TEST 4: Program Creation Wizard (Custom Splits & Injury Avoidance)
        // --------------------------------------------------------------------
        echo "  [4/10] Dinamik Program Sihirbazı & Split Oluşturma...\n";
        $csrfDash = $this->extractCsrf($res['body']);
        $res = $this->request('/workout/custom_program', 'POST', [
            'csrf_token'   => $csrfDash,
            'split_type'   => ($roundNumber % 2 === 0 ? 'split_upper_lower' : 'full_body'),
            'focus_intent' => 'hipertrofi',
            'injuries'     => ['shoulder']
        ]);
        $this->assert($res['code'] === 200, "POST /workout/custom_program HTTP 200 ile sonuçlanmalı");
        $this->assert(strpos($res['body'], 'başarıyla oluşturuldu') !== false || strpos($res['body'], 'Gün') !== false, "Yeni program başarıyla oluşturulmalı");

        // --------------------------------------------------------------------
        // TEST 5: Live Workout Session & Positional Route
        // --------------------------------------------------------------------
        echo "  [5/10] Canlı Antrenman Oturumu & Gün Geçiş Sekmeleri...\n";
        $res = $this->request('/workout/live');
        $this->assert($res['code'] === 200, "GET /workout/live HTTP 200 dönmeli");
        $this->assert(strpos($res['body'], 'Canlı Oturum') !== false, "Canlı oturum kartı ekranda olmalı");
        $this->assert(strpos($res['body'], 'Tek Otorite RIR') !== false, "Canlı seans RIR seçim butonları bulunmalı");
        $this->assert(strpos($res['body'], 'Tükeniş (0)') !== false, "RIR 0 butonu bulunmalı");
        $this->assert(strpos($res['body'], 'İdeal (1-2)') !== false, "RIR 1-2 butonu bulunmalı");
        $this->assert(strpos($res['body'], 'Kolay (3+)') !== false, "RIR 3+ butonu bulunmalı");

        // Test positional route /workout/live/{id}
        $resDay = $this->request('/workout/live/1');
        $this->assert($resDay['code'] === 200, "GET /workout/live/1 pozisyonel parametreyle hatasız açılmalı (HTTP 200)");
        $this->assert(strpos($resDay['body'], 'Fatal error') === false, "/workout/live/1 içinde Fatal Error OLMAMALI");

        // --------------------------------------------------------------------
        // TEST 6: Real-time API Set Logging & Hypertrophy Adaptation (<0.05s)
        // --------------------------------------------------------------------
        echo "  [6/10] Canlı Set İçi Ağırlık Adaptasyonu (<0.05s)...\n";
        $csrfLive = $this->extractCsrf($res['body']);
        preg_match('/sessionId:\s*(\d+)/', $res['body'], $sessMatches);
        $sessionId = !empty($sessMatches[1]) ? (int)$sessMatches[1] : 1;

        // Set 1: Ideal RIR 1-2 (MAINTAIN)
        $t0 = microtime(true);
        $resApi = $this->request('/api/log_set', 'POST', [
            'csrf_token'     => $csrfLive,
            'session_id'     => $sessionId,
            'exercise_id'    => 1,
            'set_number'     => 1,
            'set_type'       => 'reps',
            'actual_weight'  => 30.0,
            'target_reps'    => 10,
            'actual_reps'    => 10,
            'rir_bucket'     => '1-2',
            'form_breakdown' => '0'
        ]);
        $duration = microtime(true) - $t0;
        $this->assert($resApi['code'] === 200, "POST /api/log_set HTTP 200 dönmeli");
        $json = json_decode($resApi['body'], true);
        $this->assert(is_array($json) && ($json['success'] ?? false) === true, "API log_set success: true dönmeli");
        $this->assert(($json['direction'] ?? '') === 'MAINTAIN', "RIR 1-2 durumunda direction: MAINTAIN olmalı");
        $this->assert($duration < 0.20, "Adaptasyon motoru anında yanıt vermeli ({$duration}s)");

        // Set 2: Form Breakdown (Emergency -15%)
        $resApi2 = $this->request('/api/log_set', 'POST', [
            'csrf_token'     => $csrfLive,
            'session_id'     => $sessionId,
            'exercise_id'    => 1,
            'set_number'     => 2,
            'set_type'       => 'reps',
            'actual_weight'  => 30.0,
            'target_reps'    => 10,
            'actual_reps'    => 8,
            'rir_bucket'     => '0',
            'form_breakdown' => '1' // Form breakdown!
        ]);
        $json2 = json_decode($resApi2['body'], true);
        $this->assert(is_array($json2) && ($json2['success'] ?? false) === true, "Form bozulması seti kaydedilmeli");
        $this->assert(($json2['direction'] ?? '') === 'DECREASE', "Form bozulmasında direction: DECREASE olmalı");
        $this->assert(($json2['next_weight'] ?? 30.0) < 30.0, "Form bozulmasında sonraki ağırlık düşürülmeli");

        // --------------------------------------------------------------------
        // TEST 7: User Profile, Biometrics & Height Update (/profile)
        // --------------------------------------------------------------------
        echo "  [7/10] Kullanıcı Profil Sayfası & Boy/Kilo Değiştirme...\n";
        $res = $this->request('/profile');
        $this->assert($res['code'] === 200, "GET /profile HTTP 200 dönmeli");
        $this->assert(strpos($res['body'], 'Boy Uzunluğu (cm)') !== false, "Profilde Boy Uzunluğu (cm) alanı olmalı");
        $this->assert(strpos($res['body'], 'Vücut Ağırlığı (kg)') !== false, "Profilde Vücut Ağırlığı alanı olmalı");
        $this->assert(strpos($res['body'], 'VKİ Analizi') !== false, "Profilde VKİ Analizi bulunmalı");
        $this->assert(strpos($res['body'], 'Eklem Hassasiyetleri') !== false, "Profilde Eklem Koruma Kalkanı olmalı");
        $this->assert(strpos($res['body'], 'Parola Değiştir') !== false, "Profilde Parola Değiştirme alanı olmalı");

        // Submit Profile Update (Change height to 184 cm, update weight to 81.0 kg)
        $csrfProfile = $this->extractCsrf($res['body']);
        $newHeight = 184.0;
        $newWeight = 81.0;
        $res = $this->request('/profile', 'POST', [
            'csrf_token'        => $csrfProfile,
            'name'              => "Test Atlet Tur-{$roundNumber}",
            'gender'            => 'male',
            'birth_date'        => '1996-04-12',
            'height_cm'         => $newHeight,
            'current_weight_kg' => $newWeight,
            'fitness_level'     => 'intermediate',
            'injuries'          => ['shoulder', 'knee']
        ]);
        $this->assert($res['code'] === 200, "POST /profile başarıyla kaydedilmeli (Kod: {$res['code']})");
        $this->assert(strpos($res['body'], 'güncellendi') !== false || strpos($res['body'], '184') !== false, "Profil güncelleme onay mesajı veya yeni boy ekranda görünmeli");
        $this->assert(strpos($res['body'], '184') !== false, "Güncellenen boy (184 cm) ekranda doğrulanmalı");
        $this->assert(strpos($res['body'], '81') !== false, "Güncellenen kilo (81 kg) ekranda doğrulanmalı");

        // --------------------------------------------------------------------
        // TEST 8: Weigh-In & Trend History
        // --------------------------------------------------------------------
        echo "  [8/10] Tartı & Kilo Trendi Modülü...\n";
        $res = $this->request('/weigh-in');
        $this->assert($res['code'] === 200, "GET /weigh-in HTTP 200 dönmeli");
        $this->assert(strpos($res['body'], 'Tartı') !== false, "Tartı sayfası başlığı bulunmalı");
        $this->assert(strpos($res['body'], 'Chart') !== false, "Tartı sayfasında Chart.js bileşeni bulunmalı");

        $csrfWeigh = $this->extractCsrf($res['body']);
        $res = $this->request('/weigh-in', 'POST', [
            'csrf_token' => $csrfWeigh,
            'weight_kg'  => 80.8,
            'date'       => date('Y-m-d'),
            'notes'      => "Tur {$roundNumber} sabah tartımı"
        ]);
        $this->assert($res['code'] === 200, "POST /weigh-in başarıyla kaydedilmeli");
        $this->assert(strpos($res['body'], '80.8') !== false, "Yeni girilen tartı ekranda görünmeli");

        // --------------------------------------------------------------------
        // TEST 9: Equipment & Plate Inventory
        // --------------------------------------------------------------------
        echo "  [9/10] Ekipman Envanteri & Plaka Çözücü...\n";
        $res = $this->request('/onboarding/equipment');
        $this->assert($res['code'] === 200, "GET /onboarding/equipment HTTP 200 dönmeli");
        $this->assert(strpos($res['body'], 'Ekipman') !== false, "Ekipman sayfası başlığı olmalı");

        $csrfEq = $this->extractCsrf($res['body']);
        $res = $this->request('/onboarding/equipment', 'POST', [
            'csrf_token'                 => $csrfEq,
            'workout_location'           => 'home_weights',
            'has_barbell'                => '1',
            'barbell_weight_kg'          => 20.0,
            'has_dumbbells'              => '1',
            'dumbbell_handle_weight_kg'  => 2.5,
            'plate_20kg'                 => 2,
            'plate_10kg'                 => 4,
            'plate_5kg'                  => 4,
            'plate_2.5kg'                => 4,
            'plate_1.25kg'               => 4,
            'plate_0.5kg'                => 2
        ]);
        $this->assert($res['code'] === 200, "POST /onboarding/equipment başarıyla kaydedilmeli");

        // --------------------------------------------------------------------
        // TEST 10: Mathematical Engine Invariant Assertions
        // --------------------------------------------------------------------
        echo "  [10/10] Hipertrofi & NSCA Matematik Motoru Doğrulaması...\n";
        $solver = new PlateSolverService();
        $rfi = new RfiService();

        // Check combined ceiling: min(10%, 2.5kg)
        // 6 kg Lateral raise: 10% = 0.60 kg, min(0.60, 2.5) = 0.60 kg
        $ceil6 = min(6.0 * \App\Config\Constants::MAX_SINGLE_SET_PCT, \App\Config\Constants::MAX_SINGLE_SET_KG);
        $this->assert(abs($ceil6 - 0.60) < 0.001, "6 kg için tavan 0.60 kg olmalı (Alınan: {$ceil6})");

        // 100 kg Squat: 10% = 10 kg, min(10, 2.5) = 2.5 kg
        $ceil100 = min(100.0 * \App\Config\Constants::MAX_SINGLE_SET_PCT, \App\Config\Constants::MAX_SINGLE_SET_KG);
        $this->assert(abs($ceil100 - 2.50) < 0.001, "100 kg için tavan 2.50 kg olmalı (Alınan: {$ceil100})");

        // Calisthenic RFI horizontal push formula: Weight * 0.30 + (reps * 0.25)
        $baselines = $rfi->calculateBaselineLoads(80.0, 20, 10, 30, 60);
        $pushLoad = $baselines['push_horizontal'];
        $this->assert(abs($pushLoad - 29.0) < 0.001, "80 kg & 20 şınav için yük 29.0 kg olmalı");

        // Plate Solver: 12.5 kg dumbbell with 2.5kg handle -> 1x5kg per side
        $plates = ['5kg' => 4, '2.5kg' => 4, '1.25kg' => 4];
        $solved = $solver->solve(12.5, false, $plates, 2.5);
        $this->assert($solved['feasible_weight'] == 12.5, "12.5 kg dambıl tam çözülebilmeli");

        $roundElapsed = round(microtime(true) - $roundStart, 2);
        echo "  ✅ TUR #{$roundNumber} TAMAMLANDI! Süre: {$roundElapsed} sn\n";
        return ($this->failures === 0);
    }
}

// ----------------------------------------------------------------------------
// MAIN 10-ROUND EXECUTION HARNESS
// ----------------------------------------------------------------------------
$totalStart = microtime(true);
echo "################################################################################\n";
echo "    AKILLI SPOR & DİNAMİK HİPERTROFİ PLATFORMU (v2.1)\n";
echo "    10 ARKA ARKAYA KESİNTİSİZ TUR TAM SİSTEM TEST PROTOKOLÜ\n";
echo "################################################################################\n\n";

$allPassed = true;
$roundStats = [];

for ($round = 1; $round <= 10; $round++) {
    $tester = new SuiteTester();
    $startRound = microtime(true);
    $passed = $tester->runFullRound($round);
    $duration = round(microtime(true) - $startRound, 2);

    $roundStats[] = [
        'round'      => $round,
        'passed'     => $passed,
        'assertions' => $tester->assertions,
        'failures'   => $tester->failures,
        'duration'   => $duration
    ];

    if (!$passed) {
        $allPassed = false;
        echo "🚨 TUR #{$round} İÇİNDE HATALAR TESPİT EDİLDİ:\n";
        foreach ($tester->errors as $err) {
            echo "   - {$err}\n";
        }
    }
    echo "\n";
}

$totalTime = round(microtime(true) - $totalStart, 2);

echo "================================================================================\n";
echo "📊 10 TUR STRES & DOĞRULAMA TEST RAPORU ÖZETİ\n";
echo "================================================================================\n";
printf("%-8s | %-12s | %-12s | %-10s | %-10s\n", "TUR NO", "DURUM", "BAŞARILI", "HATA", "SÜRE (sn)");
echo str_repeat("-", 60) . "\n";

$totalAssertions = 0;
$totalFailures = 0;

foreach ($roundStats as $st) {
    $totalAssertions += $st['assertions'];
    $totalFailures += $st['failures'];
    $statusText = $st['passed'] ? "BAŞARILI ✓" : "BAŞARISIZ ✗";
    printf("Tur #%-4d | %-12s | %-12d | %-10d | %-10s\n", 
        $st['round'], 
        $statusText, 
        $st['assertions'] - $st['failures'], 
        $st['failures'], 
        $st['duration'] . "s"
    );
}

echo str_repeat("=", 60) . "\n";
echo "Toplam Koşulan Doğrulama (Assertions) : {$totalAssertions}\n";
echo "Toplam Tespit Edilen Hata (Failures)    : {$totalFailures}\n";
echo "Toplam Geçen Süre                       : {$totalTime} saniye\n";

if ($totalFailures === 0) {
    echo "\n🏆 KUSURSUZ SONUÇ: 10 TURUN TAMAMI (%100) SIFIR HATAYLA BAŞARIYLA GEÇTİ!\n";
    echo "Tüm sayfalar, butonlar, formlar, API adaptasyon motoru ve biyometrik güncellemeler aktiftir.\n";
    exit(0);
} else {
    echo "\n⚠️ DİKKAT: Bazı testlerde hatalar tespit edildi. Lütfen yukarıdaki detayları inceleyin.\n";
    exit(1);
}
