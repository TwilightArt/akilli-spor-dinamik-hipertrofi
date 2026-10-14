<?php
use App\Core\Security;
$name = Security::escape($user['name']);
$email = Security::escape($user['email']);
$gender = $user['gender'] ?? 'male';
$birthDate = Security::escape($user['birth_date'] ?? '1995-01-01');
$heightCm = (float)($user['height_cm'] ?? 175.0);
$currentWeightKg = (float)($user['current_weight_kg'] ?? 75.0);
$fitnessLevel = $user['fitness_level'] ?? 'beginner';
$fitnessLevels = [
    'absolute_beginner' => 'Temel Adaptasyon',
    'beginner'          => 'Başlangıç Seviyesi',
    'intermediate'      => 'Orta Seviye',
    'advanced'          => 'İleri Seviye'
];
$fitnessLevelTr = $fitnessLevels[$fitnessLevel] ?? 'Başlangıç Seviyesi';

$pushupsCount = (int)($user['pushups_count'] ?? 0);
$chinupsCount = (int)($user['chinups_count'] ?? 0);
$squatsCount = (int)($user['squats_count'] ?? 0);
$plankSeconds = (int)($user['plank_seconds'] ?? 30);
$calisthenicScore = (float)($user['calisthenic_score'] ?? ($tierInfo['composite_score'] ?? 0.0));
?>

<div class="max-w-4xl mx-auto space-y-8 pb-16">

    <!-- Profile Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950/60 to-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden backdrop-blur-xl">
        <div class="absolute -right-16 -top-16 w-56 h-56 bg-brand-600/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 relative z-10">
            <div class="flex items-center gap-5">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-500 flex items-center justify-center text-white text-3xl font-black shadow-xl shadow-brand-600/30">
                    <?= mb_strtoupper(mb_substr($name, 0, 1)) ?>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-brand-400">Sporcu Profili</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-brand-500/10 text-brand-300 border border-brand-500/20 whitespace-nowrap">
                            <?= $fitnessLevelTr ?>
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">
                            Skor: <?= number_format($calisthenicScore, 1) ?>/100
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black font-display text-white tracking-tight"><?= $name ?></h1>
                    <p class="text-xs text-slate-400 mt-0.5 flex items-center gap-2">
                        <i class="fa-solid fa-envelope text-slate-500 text-xs"></i>
                        <span><?= $email ?></span>
                    </p>
                </div>
            </div>

            <!-- Fast Quick Stat Badges -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 w-full sm:w-auto">
                <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800 text-center">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Boy</span>
                    <span class="text-lg font-black text-white"><?= number_format($heightCm, 1) ?> <span class="text-xs text-slate-400 font-normal">cm</span></span>
                </div>
                <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800 text-center">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Kilo</span>
                    <span class="text-lg font-black text-emerald-400"><?= number_format($currentWeightKg, 1) ?> <span class="text-xs text-slate-400 font-normal">kg</span></span>
                </div>
                <div class="col-span-2 sm:col-span-1 p-3 rounded-2xl bg-slate-950/60 border border-slate-800 text-center">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">VKİ (BMI)</span>
                    <span class="text-lg font-black text-indigo-300"><?= $bmi ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Profile Edit Form -->
    <form action="/profile" method="POST" class="space-y-8" x-data="conditioningProfile({
        pushups: <?= $pushupsCount ?>,
        chinups: <?= $chinupsCount ?>,
        squats: <?= $squatsCount ?>,
        plank: <?= $plankSeconds ?>,
        evalMode: 'auto',
        level: '<?= $fitnessLevel ?>'
    })">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <!-- Section 1: Biometric & Personal Settings -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl backdrop-blur-xl space-y-6">
            <div class="flex items-center gap-3 border-b border-slate-800 pb-4">
                <div class="w-10 h-10 rounded-xl bg-brand-600/10 border border-brand-500/20 text-brand-400 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-ruler-combined"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold font-display text-white">Biyometrik Ölçümler & Kişisel Bilgiler</h2>
                    <p class="text-xs text-slate-400">Boy ve kilo verileriniz kalistenik RFI katsayıları ve mekanik gerilim hesaplarında kullanılır.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Ad Soyad</label>
                    <input type="text" id="name" name="name" value="<?= $name ?>" required
                        class="w-full bg-slate-950 border border-slate-800 focus:border-brand-500 rounded-xl px-4 py-3 text-sm text-white focus:outline-none transition-all">
                </div>

                <!-- Gender -->
                <div>
                    <label for="gender" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Cinsiyet</label>
                    <select id="gender" name="gender" class="w-full bg-slate-950 border border-slate-800 focus:border-brand-500 rounded-xl px-4 py-3 text-sm text-white focus:outline-none transition-all">
                        <option value="male" <?= $gender === 'male' ? 'selected' : '' ?>>Erkek</option>
                        <option value="female" <?= $gender === 'female' ? 'selected' : '' ?>>Kadın</option>
                        <option value="other" <?= $gender === 'other' ? 'selected' : '' ?>>Diğer / Belirtmek İstemiyorum</option>
                    </select>
                </div>

                <!-- Birth Date -->
                <div>
                    <label for="birth_date" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Doğum Tarihi</label>
                    <input type="date" id="birth_date" name="birth_date" value="<?= $birthDate ?>" required
                        class="w-full bg-slate-950 border border-slate-800 focus:border-brand-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none transition-all">
                </div>

                <!-- Boy (cm) -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="height_cm" class="text-xs font-bold text-brand-300 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-arrows-up-down text-brand-400"></i> Boy Uzunluğu (cm)
                        </label>
                        <span class="text-[10px] text-brand-400 font-bold">Zorunlu</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="number" step="0.5" min="120" max="240" id="height_cm" name="height_cm" value="<?= $heightCm ?>" required
                            class="w-full bg-slate-950 border border-slate-800 focus:border-brand-500 rounded-xl px-4 py-3 text-sm text-white focus:outline-none">
                    </div>
                </div>

                <!-- Kilo (kg) -->
                <div class="sm:col-span-2">
                    <div class="flex items-center justify-between mb-2">
                        <label for="current_weight_kg" class="text-xs font-bold text-emerald-300 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-weight-scale text-emerald-400"></i> Vücut Ağırlığı (kg)
                        </label>
                        <a href="/weigh-in" class="text-[10px] text-emerald-400 hover:underline">Tartı & Trend Grafiği →</a>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="number" step="0.1" min="35" max="250" id="current_weight_kg" name="current_weight_kg" value="<?= $currentWeightKg ?>" required
                            class="w-full bg-slate-950 border border-slate-800 focus:border-emerald-500 rounded-xl px-4 py-3 text-sm font-bold text-emerald-400 focus:outline-none">
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1.5">
                        Güncel kilonuz kalistenik temel yüklerin (Vücut x %30) ve plaka hesaplarının bazını oluşturur.
                    </p>
                </div>
            </div>

            <!-- BMI Analysis Card -->
            <div class="p-4 rounded-2xl bg-indigo-950/30 border border-indigo-500/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600/20 text-indigo-400 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-white block">VKİ Analizi: <strong class="text-indigo-300"><?= $bmi ?> (<?= $bmiCategory ?>)</strong></span>
                        <span class="text-[11px] text-slate-400">
                            <?= $heightCm ?> cm boy için sağlıklı ideal kilo aralığı: <strong class="text-white"><?= $minHealthyWeight ?> kg - <?= $maxHealthyWeight ?> kg</strong>
                        </span>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-xl text-xs font-bold bg-indigo-500/10 text-indigo-300 border border-indigo-500/30 whitespace-nowrap">
                    WHO Standartları
                </span>
            </div>
        </div>

        <!-- Section 2: Multi-Parametric Calisthenic Conditioning & Strength Engine (4 PARAMETERS) -->
        <div class="bg-slate-900/90 border border-brand-500/30 rounded-3xl p-6 sm:p-8 shadow-xl backdrop-blur-xl space-y-6 relative overflow-hidden">
            <div class="absolute -right-20 -bottom-20 w-60 h-60 bg-brand-600/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-500 text-white flex items-center justify-center font-bold shadow-lg shadow-brand-500/20">
                        <i class="fa-solid fa-calculator"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold font-display text-white">4 Parametreli Kalistenik Kondisyon & Kuvvet Testi</h2>
                        <p class="text-xs text-slate-400">Kondisyonunuz tek bir harekete (şınav) bağlanmaz; İtiş (%25), Çekiş (%25), Bacak (%25) ve Kor (%25) bağımsız değerlendirilir.</p>
                    </div>
                </div>
                <span class="hidden sm:inline-flex px-3 py-1 rounded-full text-xs font-bold bg-brand-500/10 text-brand-300 border border-brand-500/20">
                    Bilimsel RFI Matrisi
                </span>
            </div>

            <!-- Algorithm Live Score Banner -->
            <div class="p-5 rounded-2xl bg-gradient-to-r from-slate-950 via-brand-950/30 to-slate-950 border border-brand-500/30 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Bileşik Kalistenik Kondisyon Puanı</span>
                        <div class="flex items-baseline gap-2 mt-0.5">
                            <span class="text-3xl font-black font-display text-white" x-text="computedScore.toFixed(1)"></span>
                            <span class="text-xs text-slate-400 font-bold">/ 100 Puan</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Algoritmik Seviye Teşhisi</span>
                            <span class="text-sm font-black text-brand-400" x-text="computedTitle"></span>
                        </div>
                        <div class="w-11 h-11 rounded-2xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-300 text-lg">
                            <i class="fa-solid fa-award"></i>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="w-full h-2.5 bg-slate-900 rounded-full overflow-hidden p-0.5 border border-slate-800">
                    <div class="h-full bg-gradient-to-r from-brand-500 via-indigo-500 to-emerald-400 rounded-full transition-all duration-300"
                         :style="'width: ' + Math.min(100, Math.max(5, computedScore)) + '%'"></div>
                </div>

                <!-- 4 Sub-Scores Breakdown -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 text-center">
                    <div class="p-2 rounded-xl bg-slate-900/60 border border-slate-800">
                        <span class="text-[10px] text-slate-400 block">İtiş (Şınav %25)</span>
                        <span class="text-xs font-bold text-white" x-text="pushScore.toFixed(0) + ' / 100'"></span>
                    </div>
                    <div class="p-2 rounded-xl bg-slate-900/60 border border-slate-800">
                        <span class="text-[10px] text-slate-400 block">Çekiş (Barfiks %25)</span>
                        <span class="text-xs font-bold text-white" x-text="pullScore.toFixed(0) + ' / 100'"></span>
                    </div>
                    <div class="p-2 rounded-xl bg-slate-900/60 border border-slate-800">
                        <span class="text-[10px] text-slate-400 block">Bacak (Squat %25)</span>
                        <span class="text-xs font-bold text-white" x-text="legScore.toFixed(0) + ' / 100'"></span>
                    </div>
                    <div class="p-2 rounded-xl bg-slate-900/60 border border-slate-800">
                        <span class="text-[10px] text-slate-400 block">Kor (Plank %25)</span>
                        <span class="text-xs font-bold text-white" x-text="coreScore.toFixed(0) + ' / 100'"></span>
                    </div>
                </div>
            </div>

            <!-- 4 Calisthenic Movement Inputs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- 1. Şınav (Push-up) -->
                <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-brand-500/40 transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <label for="pushups_count" class="text-xs font-bold text-slate-200 flex items-center gap-2">
                            <i class="fa-solid fa-person-praying text-brand-400"></i> Şınav (Nizami Tekrar)
                        </label>
                        <span class="text-[10px] text-brand-400 font-bold" x-show="pushups == 0">Regresyon Gerekli</span>
                    </div>
                    <input type="number" min="0" max="200" id="pushups_count" name="pushups_count" x-model.number="pushups"
                        class="w-full bg-slate-900 border border-slate-700 focus:border-brand-500 rounded-xl px-4 py-2.5 text-base font-black text-white focus:outline-none transition-all">
                    <p class="text-[10px] text-slate-400 mt-1.5">
                        0 tekrar durumunda duvara şınav (Wall Push-Up) veya diz üstü şınav atanır.
                    </p>
                </div>

                <!-- 2. Barfiks (Chin-up / Pull-up) -->
                <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-brand-500/40 transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <label for="chinups_count" class="text-xs font-bold text-slate-200 flex items-center gap-2">
                            <i class="fa-solid fa-hands text-brand-400"></i> Barfiks / Çekiş (Tekrar)
                        </label>
                        <span class="text-[10px] text-brand-400 font-bold" x-show="chinups == 0">Regresyon Gerekli</span>
                    </div>
                    <input type="number" min="0" max="100" id="chinups_count" name="chinups_count" x-model.number="chinups"
                        class="w-full bg-slate-900 border border-slate-700 focus:border-brand-500 rounded-xl px-4 py-2.5 text-base font-black text-white focus:outline-none transition-all">
                    <p class="text-[10px] text-slate-400 mt-1.5">
                        0 tekrar durumunda kapı havlu çekişi (Door Towel Row) ve negatif barfiks atanır.
                    </p>
                </div>

                <!-- 3. Squat -->
                <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-brand-500/40 transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <label for="squats_count" class="text-xs font-bold text-slate-200 flex items-center gap-2">
                            <i class="fa-solid fa-person-walking text-brand-400"></i> Vücut Ağırlığı Squat (Tekrar)
                        </label>
                        <span class="text-[10px] text-brand-400 font-bold" x-show="squats < 5">Sandalye Destekli</span>
                    </div>
                    <input type="number" min="0" max="300" id="squats_count" name="squats_count" x-model.number="squats"
                        class="w-full bg-slate-900 border border-slate-700 focus:border-brand-500 rounded-xl px-4 py-2.5 text-base font-black text-white focus:outline-none transition-all">
                    <p class="text-[10px] text-slate-400 mt-1.5">
                        5 tekrar altındaysa eklem korumalı sandalye squat (Chair Assisted Squat) seçilir.
                    </p>
                </div>

                <!-- 4. Plank -->
                <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-brand-500/40 transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <label for="plank_seconds" class="text-xs font-bold text-slate-200 flex items-center gap-2">
                            <i class="fa-solid fa-stopwatch text-brand-400"></i> Ön Plank (Saniye)
                        </label>
                        <span class="text-[10px] text-brand-400 font-bold" x-show="plank < 25">Diz Plank</span>
                    </div>
                    <input type="number" min="0" max="600" id="plank_seconds" name="plank_seconds" x-model.number="plank"
                        class="w-full bg-slate-900 border border-slate-700 focus:border-brand-500 rounded-xl px-4 py-2.5 text-base font-black text-white focus:outline-none transition-all">
                    <p class="text-[10px] text-slate-400 mt-1.5">
                        Omurga stabilitesi ve karın duvarı izometrik dayanıklılık süresi (sn).
                    </p>
                </div>
            </div>

            <!-- Evaluation Mode & Manual Selection Option -->
            <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <span class="text-xs font-bold text-white block">Değerlendirme ve Seviye Seçim Modu</span>
                        <span class="text-[11px] text-slate-400">Kondisyonunuzu formülün otomatik belirlemesini mi yoksa manuel seçmek mi istersiniz?</span>
                    </div>
                    <div class="flex items-center gap-2 bg-slate-900 p-1 rounded-xl border border-slate-800">
                        <button type="button" @click="evalMode = 'auto'"
                            :class="evalMode === 'auto' ? 'bg-brand-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                            class="px-3 py-1.5 rounded-lg text-xs transition-all">
                            Otomatik Formül
                        </button>
                        <button type="button" @click="evalMode = 'manual'"
                            :class="evalMode === 'manual' ? 'bg-brand-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                            class="px-3 py-1.5 rounded-lg text-xs transition-all">
                            Manuel Seçim
                        </button>
                    </div>
                </div>
                <input type="hidden" name="fitness_eval_mode" :value="evalMode">

                <!-- Manual Level Selector (Appears if user picks manual) -->
                <div x-show="evalMode === 'manual'" class="pt-3 border-t border-slate-800/80">
                    <label for="fitness_level" class="block text-xs font-bold text-brand-300 uppercase tracking-wider mb-2">Manuel Kondisyon Seviyesi Belirle</label>
                    <select id="fitness_level" name="fitness_level" x-model="level" class="w-full bg-slate-900 border border-slate-700 focus:border-brand-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none transition-all">
                        <option value="absolute_beginner">Tam Başlangıç (0 Şınav/Barfiks Regresyonu)</option>
                        <option value="beginner">Başlangıç (Temel Kondisyon)</option>
                        <option value="intermediate">Orta Seviye (Hipertrofi Odaklı)</option>
                        <option value="advanced">İleri Seviye (Yüksek Atletik Kapasite)</option>
                    </select>
                </div>

                <!-- Program Regeneration Checkbox -->
                <label class="flex items-center gap-3 pt-2 cursor-pointer">
                    <input type="checkbox" name="regenerate_program" value="1" checked
                        class="rounded bg-slate-900 border-slate-700 text-brand-600 focus:ring-0 w-4 h-4">
                    <span class="text-xs font-bold text-slate-200">
                        Kaydettiğimde haftalık egzersiz programımı yeni kondisyon verilerime ve kilonuza göre baştan optimize et
                    </span>
                </label>
            </div>
        </div>

        <!-- Section 3: Joint Health & Injury Avoidance Filter -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl backdrop-blur-xl space-y-6">
            <div class="flex items-center gap-3 border-b border-slate-800 pb-4">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-shield-heart"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold font-display text-white">Eklem Hassasiyetleri & Sakatlık Koruma Kalkanı</h2>
                    <p class="text-xs text-slate-400">İşaretlediğiniz eklem bölgelerine doğrudan aşırı yük bindiren egzersizler antrenman motoru tarafından otomatik olarak elenir ve güvenli alternatiflerle değiştirilir.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <?php
                $jointOptions = [
                    'shoulder'   => ['name' => 'Omuz Hassasiyeti', 'desc' => 'Overhead Press ve Dips hareketleri regrese edilir.', 'icon' => 'fa-shield'],
                    'knee'       => ['name' => 'Diz / Menisküs Hassasiyeti', 'desc' => 'Derin squatlar regrese edilir, posterior chain odaklanır.', 'icon' => 'fa-person-walking'],
                    'lower_back' => ['name' => 'Bel / Lomber Hassasiyet', 'desc' => 'Omurga aksiyel yükü taşıyan hareketler kısıtlanır.', 'icon' => 'fa-bone'],
                    'elbow'      => ['name' => 'Dirsek / Tendinit Hassasiyeti', 'desc' => 'Skullcrusher vb. izole kollar regrese edilir.', 'icon' => 'fa-hand-fist'],
                    'wrist'      => ['name' => 'Bilek / Karpal Hassasiyet', 'desc' => 'Zeminde düz bilek şınavları dambıl/nötr tutuşa çekilir.', 'icon' => 'fa-hand']
                ];
                foreach ($jointOptions as $key => $opt):
                    $isChecked = in_array($key, $activeInjuries, true);
                ?>
                <label class="p-4 rounded-2xl border cursor-pointer transition-all flex items-start gap-3 <?= $isChecked ? 'bg-amber-950/20 border-amber-500/40 text-amber-200' : 'bg-slate-950/60 border-slate-800 text-slate-300 hover:border-slate-700' ?>">
                    <input type="checkbox" name="injuries[]" value="<?= $key ?>" <?= $isChecked ? 'checked' : '' ?> class="mt-1 rounded bg-slate-900 border-slate-700 text-amber-500 focus:ring-0">
                    <div>
                        <span class="text-xs font-bold block text-white"><?= $opt['name'] ?></span>
                        <span class="text-[10px] text-slate-400 leading-tight block mt-0.5"><?= $opt['desc'] ?></span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Submit Button for Profile -->
        <button type="submit" class="w-full py-4 px-6 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white font-black text-sm rounded-2xl shadow-xl shadow-brand-600/30 active:scale-[0.99] transition-all flex items-center justify-center gap-2">
            <i class="fa-solid fa-check"></i>
            <span>Biyometrik Verileri & Profili Güncelle</span>
        </button>
    </form>

    <!-- Section 3: Password & Security Form -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl backdrop-blur-xl space-y-6">
        <div class="flex items-center gap-3 border-b border-slate-800 pb-4">
            <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-lock"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold font-display text-white">Hesap Güvenliği & Parola Değiştir</h2>
                <p class="text-xs text-slate-400">Hesap güvenliğiniz için parolanızı düzenli olarak güncelleyin (Argon2id şifreleme).</p>
            </div>
        </div>

        <form action="/profile/password" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="current_password" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Mevcut Parola</label>
                    <input type="password" id="current_password" name="current_password" required placeholder="••••••••"
                        class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none transition-all">
                </div>

                <div>
                    <label for="new_password" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Yeni Parola (En az 8)</label>
                    <input type="password" id="new_password" name="new_password" minlength="8" required placeholder="••••••••"
                        class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none transition-all">
                </div>

                <div>
                    <label for="confirm_password" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Yeni Parola (Tekrar)</label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="8" required placeholder="••••••••"
                        class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none transition-all">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-slate-800 hover:bg-rose-600 hover:text-white text-slate-300 border border-slate-700 hover:border-rose-500 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-key text-xs"></i>
                    <span>Parolayı Güncelle</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Section 4: Data Portability (JSON Export & Import) -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl backdrop-blur-xl space-y-6" x-data="{ importTab: 'file' }">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-file-export"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold font-display text-white">Veri Yönetimi & Yedekleme (JSON)</h2>
                    <p class="text-xs text-slate-400">Tartı kayıtları, vücut ölçüleri ve sporcu profil verilerinizi JSON olarak dışa aktarabilir veya yedekten geri yükleyebilirsiniz.</p>
                </div>
            </div>
            <a href="/profile/export" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30 transition-all">
                <i class="fa-solid fa-download text-xs"></i>
                <span>Verilerimi İndir (.json)</span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Export Card -->
            <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-4">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-down text-emerald-400"></i>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Verileri Dışa Aktar</h3>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Sistemdeki tüm biyometrik geçmişinizi, 365 günlük tartılarınızı ve vücut ölçülerinizi içeren standart JSON yedeğinizi anında indirin.
                </p>
                <div class="pt-2">
                    <a href="/profile/export" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-emerald-600 hover:text-white text-slate-200 border border-slate-700 hover:border-emerald-500 rounded-xl text-xs font-bold transition-all shadow-md">
                        <i class="fa-solid fa-file-arrow-down"></i>
                        <span>JSON Yedek İndir</span>
                    </a>
                </div>
            </div>

            <!-- Import Card -->
            <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-4">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-up text-brand-400"></i>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Yedekten İçe Aktar</h3>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Daha önce indirdiğiniz JSON yedek dosyasını yükleyin veya doğrudan JSON metnini yapıştırarak verilerinizi senkronize edin.
                </p>

                <form action="/profile/import" method="POST" enctype="multipart/form-data" class="space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div class="flex items-center gap-2 text-[11px] mb-2">
                        <button type="button" @click="importTab = 'file'" :class="importTab === 'file' ? 'bg-brand-600/20 text-brand-400 border-brand-500/40' : 'text-slate-400 border-slate-800'" class="px-2.5 py-1 rounded-lg border font-semibold transition-all">Dosya Seç</button>
                        <button type="button" @click="importTab = 'raw'" :class="importTab === 'raw' ? 'bg-brand-600/20 text-brand-400 border-brand-500/40' : 'text-slate-400 border-slate-800'" class="px-2.5 py-1 rounded-lg border font-semibold transition-all">Metin Yapıştır</button>
                    </div>

                    <div x-show="importTab === 'file'">
                        <input type="file" name="backup_file" accept=".json,application/json" class="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-slate-300 hover:file:bg-brand-600 hover:file:text-white transition-all">
                    </div>

                    <div x-show="importTab === 'raw'" x-cloak>
                        <textarea name="json_data" rows="3" placeholder='{"daily_weigh_ins": [...], "body_measurements": [...]}' class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-brand-500 font-mono"></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-brand-600/30 transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-upload text-xs"></i>
                        <span>İçe Aktarımı Başlat</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
function conditioningProfile(config) {
    return {
        pushups: config.pushups || 0,
        chinups: config.chinups || 0,
        squats: config.squats || 0,
        plank: config.plank || 30,
        evalMode: config.evalMode || 'auto',
        level: config.level || 'beginner',

        get pushScore() {
            const p = Number(this.pushups) || 0;
            if (p >= 30) return 100;
            if (p >= 20) return 75 + ((p - 20) / 10) * 25;
            if (p >= 10) return 50 + ((p - 10) / 10) * 25;
            if (p >= 3) return 20 + ((p - 3) / 7) * 30;
            return (p / 3) * 20;
        },

        get pullScore() {
            const c = Number(this.chinups) || 0;
            if (c >= 15) return 100;
            if (c >= 8) return 75 + ((c - 8) / 7) * 25;
            if (c >= 4) return 50 + ((c - 4) / 4) * 25;
            if (c >= 1) return 20 + ((c - 1) / 3) * 30;
            return 0;
        },

        get legScore() {
            const s = Number(this.squats) || 0;
            if (s >= 50) return 100;
            if (s >= 30) return 75 + ((s - 30) / 20) * 25;
            if (s >= 15) return 50 + ((s - 15) / 15) * 25;
            if (s >= 5) return 20 + ((s - 5) / 10) * 30;
            return (s / 5) * 20;
        },

        get coreScore() {
            const pl = Number(this.plank) || 0;
            if (pl >= 120) return 100;
            if (pl >= 75) return 75 + ((pl - 75) / 45) * 25;
            if (pl >= 40) return 50 + ((pl - 40) / 35) * 25;
            if (pl >= 20) return 20 + ((pl - 20) / 20) * 30;
            return (pl / 20) * 20;
        },

        get computedScore() {
            const score = (this.pushScore * 0.25) + (this.pullScore * 0.25) + (this.legScore * 0.25) + (this.coreScore * 0.25);
            return Math.round(score * 10) / 10;
        },

        get computedLevel() {
            const score = this.computedScore;
            const p = Number(this.pushups) || 0;
            const c = Number(this.chinups) || 0;
            const s = Number(this.squats) || 0;

            if (score < 22 || (p === 0 && c === 0 && s < 5)) return 'absolute_beginner';
            if (score < 48) return 'beginner';
            if (score < 76) return 'intermediate';
            return 'advanced';
        },

        get computedTitle() {
            const lvl = this.evalMode === 'manual' ? this.level : this.computedLevel;
            const titles = {
                'absolute_beginner': 'Mutlak Başlangıç (Regresyon)',
                'beginner': 'Başlangıç Seviyesi (Temel Kondisyon)',
                'intermediate': 'Orta Seviye (Hipertrofi Odaklı)',
                'advanced': 'İleri Seviye (Yüksek Kapasite)'
            };
            return titles[lvl] || 'Belirleniyor';
        }
    };
}
</script>
