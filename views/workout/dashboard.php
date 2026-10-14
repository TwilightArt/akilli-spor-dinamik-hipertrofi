<?php
use App\Core\Security;

$daysOfWeekTr = [
    1 => 'Pazartesi',
    2 => 'Salı',
    3 => 'Çarşamba',
    4 => 'Perşembe',
    5 => 'Cuma',
    6 => 'Cumartesi',
    7 => 'Pazar'
];

$bmiCategory = 'Normal';
if ($bmi < 18.5) $bmiCategory = 'Zayıf';
elseif ($bmi >= 25.0 && $bmi < 30.0) $bmiCategory = 'Hafif Kilolu';
elseif ($bmi >= 30.0) $bmiCategory = 'Yüksek';
?>
<div class="space-y-6" x-data="{ showProgramWizard: false, showWeightModal: false }">

    <!-- Active Session Warning Bar -->
    <?php if (!empty($activeSession)): ?>
    <div class="p-5 rounded-2xl bg-gradient-to-r from-emerald-950/80 via-slate-900 to-slate-900 border border-emerald-500/40 shadow-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="relative flex h-3.5 w-3.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"></span>
            </span>
            <div>
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    Tamamlanmamış Canlı Antrenmanınız Var!
                </h3>
                <p class="text-xs text-slate-400">
                    Oturum başlatıldı: <?= date('H:i', strtotime($activeSession['started_at'])) ?> • Canlı adaptasyon ekranına geri dönün.
                </p>
            </div>
        </div>
        <a href="/workout/live" class="w-full sm:w-auto px-5 py-2.5 btn-sporty text-xs flex items-center justify-center gap-2">
            <i class="fa-solid fa-play text-xs"></i>
            <span>Antrenmana Devam Et</span>
        </a>
    </div>
    <?php endif; ?>

    <!-- Deload Week Banner -->
    <?php if (!empty($program['is_deload_week'])): ?>
    <div class="p-5 rounded-2xl bg-amber-950/60 border border-amber-500/30 flex items-start gap-3">
        <i class="fa-solid fa-shield-heart text-amber-400 text-xl mt-0.5"></i>
        <div>
            <h3 class="text-sm font-bold text-amber-200">Deload Haftası Devrede</h3>
            <p class="text-xs text-amber-300/80 mt-0.5">
                Merkezi sinir sistemi ve eklem toparlanması için ağırlıklar %35 düşürüldü, setler yarıya indirildi. Bu hafta tükeniş (RIR 0) yasaktır, minimum RIR 3 ile hareket kalitesine odaklanın.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ATHLETE PROFILE & BIOMETRICS COMMAND CENTER (MÜŞTERİ PANELİ) -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl relative overflow-hidden">
        <div class="absolute -top-24 -right-24 w-60 h-60 bg-brand-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-slate-800">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-500 flex items-center justify-center text-white text-2xl font-black shadow-lg shadow-brand-500/20 shrink-0">
                    <?= strtoupper(substr($user['name'] ?? 'S', 0, 1)) ?>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h1 class="text-2xl font-bold font-display text-gradient tracking-tight"><?= Security::escape($user['name']) ?></h1>
                        <span class="px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider rounded-full bg-brand-500/10 text-brand-400 border border-brand-500/20">
                            <?= Security::escape($user['fitness_level'] ?? 'beginner') ?>
                        </span>
                    </div>
                    <p class="text-xs text-slate-400"><?= Security::escape($user['email']) ?> • Üyelik Tarihi: <?= date('d.m.Y', strtotime($user['created_at'])) ?></p>
                </div>
            </div>

            <!-- Quick Action Buttons -->
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="/analytics" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-emerald-400 hover:text-white border border-slate-700 hover:border-emerald-500/40 font-bold text-xs rounded-xl shadow-lg transition-all flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-xs"></i>
                    <span>Ağırlık Grafikleri & PR</span>
                </a>
                <button type="button" @click="showProgramWizard = !showProgramWizard" 
                    class="px-4 py-2.5 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-brand-600/30 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
                    <span>Programı Yeniden Kur</span>
                </button>
                <a href="/workout/live" class="px-4 py-2.5 btn-sporty text-xs flex items-center gap-2">
                    <i class="fa-solid fa-play text-xs"></i>
                    <span>Bugünkü Antrenmana Başla</span>
                </a>
            </div>
        </div>

        <!-- ANTRENMAN STREAK & SÜREKLİLİK PANELİ -->
        <div class="mt-6 p-4 rounded-2xl bg-gradient-to-r from-orange-950/60 via-slate-950 to-slate-950 border border-orange-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-orange-600 to-amber-500 flex items-center justify-center text-white text-xl shadow-lg shadow-orange-500/30 shrink-0">
                    <i class="fa-solid fa-fire animate-pulse text-yellow-200"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-orange-400">Antrenman Serisi (Streak)</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-orange-500/20 text-orange-300 border border-orange-500/30">
                            <?= ($streakStats['streak_weeks'] ?? 0) > 0 ? ($streakStats['streak_weeks'] . ' Hafta Aralıksız!') : 'Yeni Başlangıç' ?>
                        </span>
                    </div>
                    <p class="text-xs text-slate-300 mt-0.5">
                        Bu hafta <b class="text-orange-400 font-mono"><?= (int)($streakStats['this_week_count'] ?? 0) ?></b> seans tamamlandı. Toplam <b class="text-emerald-400 font-mono"><?= (int)($streakStats['total_completed'] ?? 0) ?></b> tamamlanmış oturum kaydınız var.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 self-stretch sm:self-auto justify-end border-t sm:border-t-0 border-slate-800/80 pt-2 sm:pt-0">
                <div class="text-center px-3 py-1.5 bg-slate-900 rounded-xl border border-slate-800">
                    <span class="text-[9px] uppercase font-bold text-slate-500 block">Bu Hafta</span>
                    <span class="text-sm font-black font-mono text-orange-400"><?= (int)($streakStats['this_week_count'] ?? 0) ?></span>
                </div>
                <div class="text-center px-3 py-1.5 bg-slate-900 rounded-xl border border-slate-800">
                    <span class="text-[9px] uppercase font-bold text-slate-500 block">Hafta Serisi</span>
                    <span class="text-sm font-black font-mono text-amber-400"><?= (int)($streakStats['streak_weeks'] ?? 0) ?></span>
                </div>
                <div class="text-center px-3 py-1.5 bg-slate-900 rounded-xl border border-slate-800">
                    <span class="text-[9px] uppercase font-bold text-slate-500 block">Toplam İdman</span>
                    <span class="text-sm font-black font-mono text-emerald-400"><?= (int)($streakStats['total_completed'] ?? 0) ?></span>
                </div>
            </div>
        </div>

        <!-- Biometrics & Metrics 4-Box Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-6">
            <!-- Height -->
            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80 relative group">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Boy Uzunluğu</span>
                    <a href="/profile" class="text-[10px] text-brand-400 hover:text-brand-300 font-semibold">Değiştir →</a>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black font-display text-white"><?= (float)($user['height_cm'] ?? 175) ?></span>
                    <span class="text-xs text-slate-500">cm</span>
                </div>
            </div>

            <!-- Weight -->
            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80 relative group">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Güncel Kilo</span>
                    <a href="/weigh-in" class="text-[10px] text-brand-400 hover:text-brand-300 font-semibold">Tartı Gir →</a>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black font-display text-emerald-400"><?= number_format($latestWeight, 1) ?></span>
                    <span class="text-xs text-slate-500">kg</span>
                </div>
            </div>

            <!-- BMI -->
            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Vücut Kitle Endeksi</span>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black font-display text-indigo-400"><?= $bmi ?></span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 font-bold"><?= $bmiCategory ?></span>
                </div>
            </div>

            <!-- Program Model -->
            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Aktif Program Modeli</span>
                <div class="text-sm font-bold text-white truncate">
                    <?= ($program['program_type'] ?? '') === 'split_upper_lower' ? '4 Gün Üst/Alt Split' : '3 Gün Tüm Vücut' ?>
                </div>
                <span class="text-[10px] text-slate-500 block mt-0.5"><?= ($program['generation_source'] ?? '') === 'ai_generated' ? 'Gemini AI Destekli' : 'Bilimsel Standart Şablon' ?></span>
            </div>
        </div>
    </div>

    <!-- PROGRAM CREATION & CUSTOMIZATION WIZARD (PROGRAM OLUŞTURUCU SİHİRBAZ) -->
    <div x-show="showProgramWizard" x-transition.duration.300ms class="bg-gradient-to-b from-slate-900 to-slate-950 border border-brand-500/40 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600/20 text-brand-400 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold font-display text-white">Akıllı Program Sihirbazı & Özelleştirme</h2>
                    <p class="text-xs text-slate-400">Hedefinize, antrenman ortamınıza ve olası eklem hassasiyetlerinize göre programınızı anında sıfırdan kurun.</p>
                </div>
            </div>
            <button type="button" @click="showProgramWizard = false" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="/workout/custom_program" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <!-- Program Type Selector -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2.5">1. Antrenman Düzeni & Bölünmesi (Split Türü)</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="p-4 rounded-2xl border border-slate-800 hover:border-brand-500 bg-slate-950/60 cursor-pointer transition-all flex flex-col justify-between gap-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-white">Tüm Vücut (Full Body)</span>
                            <input type="radio" name="program_type" value="full_body" <?= ($program['program_type'] ?? '') === 'full_body' ? 'checked' : '' ?> class="text-brand-600 focus:ring-0">
                        </div>
                        <p class="text-[11px] text-slate-400">Haftada 3 gün, her antrenmanda tüm ana kas gruplarını uyarır. Toparlanma süresi yüksektir.</p>
                    </label>

                    <label class="p-4 rounded-2xl border border-slate-800 hover:border-brand-500 bg-slate-950/60 cursor-pointer transition-all flex flex-col justify-between gap-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-white">Üst / Alt Vücut (Split)</span>
                            <input type="radio" name="program_type" value="split_upper_lower" <?= ($program['program_type'] ?? '') === 'split_upper_lower' ? 'checked' : '' ?> class="text-brand-600 focus:ring-0">
                        </div>
                        <p class="text-[11px] text-slate-400">Haftada 4 gün (2 gün Üst, 2 gün Alt). Hipertrofi ve hacim için altın standarttır.</p>
                    </label>

                    <label class="p-4 rounded-2xl border border-slate-800 hover:border-brand-500 bg-slate-950/60 cursor-pointer transition-all flex flex-col justify-between gap-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-white">İtiş / Çekiş / Bacak (PPL)</span>
                            <input type="radio" name="program_type" value="split_ppl" <?= ($program['program_type'] ?? '') === 'split_ppl' ? 'checked' : '' ?> class="text-brand-600 focus:ring-0">
                        </div>
                        <p class="text-[11px] text-slate-400">Haftada 5-6 gün yüksek sıklıklı izole kas hipertrofisi için tasarlanmıştır.</p>
                    </label>
                </div>
            </div>

            <!-- Workout Location Selector -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2.5">2. Antrenman Konumu & Ekipmanlar</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="p-3.5 rounded-xl border border-slate-800 bg-slate-950/60 cursor-pointer flex items-center justify-between">
                        <span class="text-xs font-bold text-white">Evde Ağırlık / Plaka</span>
                        <input type="radio" name="workout_location" value="home_weights" checked class="text-brand-600 focus:ring-0">
                    </label>
                    <label class="p-3.5 rounded-xl border border-slate-800 bg-slate-950/60 cursor-pointer flex items-center justify-between">
                        <span class="text-xs font-bold text-white">Spor Salonu (Tüm Ekipmanlar)</span>
                        <input type="radio" name="workout_location" value="commercial_gym" class="text-brand-600 focus:ring-0">
                    </label>
                    <label class="p-3.5 rounded-xl border border-slate-800 bg-slate-950/60 cursor-pointer flex items-center justify-between">
                        <span class="text-xs font-bold text-white">Vücut Ağırlığı (Kalistenik)</span>
                        <input type="radio" name="workout_location" value="bodyweight" class="text-brand-600 focus:ring-0">
                    </label>
                </div>
            </div>

            <!-- Injury / Pain Exclusion Selector -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">3. Hassasiyet / Ağrı Bildirimi (Otomatik Egzersiz Dışlama)</label>
                <p class="text-[11px] text-slate-400 mb-3">Seçilen ekleme stres bindiren egzersizler programdan çıkarılır ve omurga/eklem dostu alternatifleri atanır.</p>
                <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                    <?php 
                    $jointsList = [
                        'shoulder'   => 'Omuz',
                        'elbow'      => 'Dirsek',
                        'lower_back' => 'Bel / Omurga',
                        'knee'       => 'Diz',
                        'wrist'      => 'El Bileği',
                        'neck'       => 'Boyun'
                    ];
                    foreach ($jointsList as $jKey => $jTitle):
                    ?>
                    <label class="p-2.5 rounded-xl border border-slate-800 hover:border-amber-500/50 bg-slate-950/70 text-center cursor-pointer flex flex-col items-center justify-center gap-1.5">
                        <input type="checkbox" name="injuries[]" value="<?= $jKey ?>" class="w-4 h-4 rounded text-amber-500 bg-slate-800 border-slate-700 focus:ring-0">
                        <span class="text-[11px] font-bold text-slate-300"><?= $jTitle ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn-sporty w-full py-4 px-6 flex items-center justify-center gap-2">
                <i class="fa-solid fa-check-double"></i>
                <span>Programı Sıfırdan Oluştur & Aktif Et</span>
            </button>
        </form>
    </div>

    <!-- 6-PATTERN RFI BIOMECHANICAL STRENGTH MATRIX (BAŞLANGIÇ ÇALIŞMA AĞIRLIKLARI) -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl backdrop-blur-xl space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold font-display text-white flex items-center gap-2">
                    <i class="fa-solid fa-chart-pie text-brand-400"></i>
                    6 Paternli Göreceli Kuvvet Endeksi (RFI) ve Çalışma Ağırlıkları
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Kilonuz ve kalistenik kuvvet testinize göre hesaplanan başlangıç taban yükleri.</p>
            </div>
            <a href="/onboarding/assessment" class="text-xs text-brand-400 hover:text-brand-300 font-semibold">Testi Yenile →</a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <!-- Push Horizontal -->
            <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-slate-700 transition-all">
                <span class="text-[10px] font-bold uppercase text-slate-500 block mb-0.5">Yatay İtiş (Göğüs)</span>
                <span class="text-xl font-black font-display text-white"><?= $baselines['push_horizontal'] ?> <span class="text-xs font-normal text-slate-400">kg</span></span>
                <span class="text-[9px] text-slate-500 block mt-1">Floor/Bench Press</span>
            </div>

            <!-- Push Vertical -->
            <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-slate-700 transition-all">
                <span class="text-[10px] font-bold uppercase text-slate-500 block mb-0.5">Dikey İtiş (Omuz)</span>
                <span class="text-xl font-black font-display text-white"><?= $baselines['push_vertical'] ?> <span class="text-xs font-normal text-slate-400">kg</span></span>
                <span class="text-[9px] text-slate-500 block mt-1">OHP / Dumbbell Press</span>
            </div>

            <!-- Pull -->
            <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-slate-700 transition-all">
                <span class="text-[10px] font-bold uppercase text-slate-500 block mb-0.5">Çekiş (Sırt / Lat)</span>
                <span class="text-xl font-black font-display text-white"><?= $baselines['pull_vertical'] ?> <span class="text-xs font-normal text-slate-400">kg</span></span>
                <span class="text-[9px] text-slate-500 block mt-1">Row / Chin-Up Yükü</span>
            </div>

            <!-- Squat -->
            <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-slate-700 transition-all">
                <span class="text-[10px] font-bold uppercase text-slate-500 block mb-0.5">Bacak (Squat)</span>
                <span class="text-xl font-black font-display text-white"><?= $baselines['squat'] ?> <span class="text-xs font-normal text-slate-400">kg</span></span>
                <span class="text-[9px] text-slate-500 block mt-1">Goblet / Barbell Squat</span>
            </div>

            <!-- Hinge -->
            <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-slate-700 transition-all">
                <span class="text-[10px] font-bold uppercase text-slate-500 block mb-0.5">Kalça / Hinge</span>
                <span class="text-xl font-black font-display text-white"><?= $baselines['hinge'] ?> <span class="text-xs font-normal text-slate-400">kg</span></span>
                <span class="text-[9px] text-slate-500 block mt-1">Romanian Deadlift</span>
            </div>

            <!-- Arms -->
            <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-slate-700 transition-all">
                <span class="text-[10px] font-bold uppercase text-slate-500 block mb-0.5">Kol İzolasyon</span>
                <span class="text-xl font-black font-display text-white"><?= $baselines['isolation_arm'] ?> <span class="text-xs font-normal text-slate-400">kg</span></span>
                <span class="text-[9px] text-slate-500 block mt-1">Bicep / Tricep Curl</span>
            </div>
        </div>
    </div>

    <!-- PROGRAM ARCHITECTURE & SCIENTIFIC ALGORITHM SHOWCASE -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-brand-500/30 rounded-3xl p-6 shadow-2xl backdrop-blur-xl space-y-4" x-data="{ expanded: false }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-500 flex items-center justify-center text-white text-xl font-black shadow-lg shadow-brand-500/20 shrink-0">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-brand-400">Bilimsel Hipertrofi Motoru</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-500/10 text-brand-300 border border-brand-500/20">Teknik Dokümantasyon</span>
                    </div>
                    <h3 class="text-base font-bold font-display text-white">Bu Program Nasıl Oluşturuldu & Canlı Adaptasyon Nasıl Çalışır?</h3>
                </div>
            </div>
            <button type="button" @click="expanded = !expanded"
                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 text-xs font-bold transition-all flex items-center gap-2 shrink-0">
                <span x-text="expanded ? 'Detayları Gizle' : 'Algoritmayı İncele'"></span>
                <i :class="expanded ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down'" class="text-[11px]"></i>
            </button>
        </div>

        <!-- 4 Core Pillars Overview Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2">
            <div class="p-3.5 rounded-2xl bg-slate-950/70 border border-slate-800/80">
                <div class="w-7 h-7 rounded-lg bg-brand-500/10 text-brand-400 flex items-center justify-center text-xs font-bold mb-2">1</div>
                <h4 class="text-xs font-bold text-white mb-1">4 Parametreli Kondisyon</h4>
                <p class="text-[11px] text-slate-400 leading-relaxed">Şınav, Barfiks, Squat ve Plank testleriniz birleştirilerek seviyeniz (<?= Security::escape($user['fitness_level'] ?? 'beginner') ?>) ve güvenli regresyonlar belirlendi.</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-950/70 border border-slate-800/80">
                <div class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center text-xs font-bold mb-2">2</div>
                <h4 class="text-xs font-bold text-white mb-1">Eklem Koruma Kalkanı</h4>
                <p class="text-[11px] text-slate-400 leading-relaxed">Profilinizde bildirdiğiniz eklem hassasiyetleri taranarak omurga ve omuz dostu hareket varyasyonları atandı.</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-950/70 border border-slate-800/80">
                <div class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-xs font-bold mb-2">3</div>
                <h4 class="text-xs font-bold text-white mb-1">Akıllı Plaka Çözücü</h4>
                <p class="text-[11px] text-slate-400 leading-relaxed">Elinizdeki halter, dambıl ve mevcut plakalarınıza göre takılabilir en optimize çalışma ağırlıkları hesaplandı.</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-950/70 border border-slate-800/80">
                <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xs font-bold mb-2">4</div>
                <h4 class="text-xs font-bold text-white mb-1">Canlı RIR Adaptasyonu</h4>
                <p class="text-[11px] text-slate-400 leading-relaxed">Her set sonunda girdiğiniz RIR (Cepte Kalan Tekrar) ile sonraki set için anında <strong class="text-emerald-300">+2.5 kg artış</strong> veya düşüş önerilir.</p>
            </div>
        </div>

        <!-- Expanded In-Depth Mathematical Breakdown -->
        <div x-show="expanded" x-transition.duration.300ms class="pt-4 border-t border-slate-800 space-y-4">
            <div class="p-4 rounded-2xl bg-slate-950/90 border border-slate-800 text-xs text-slate-300 space-y-3">
                <h4 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-square-root-variable text-brand-400"></i>
                    Matematiksel Model: Epley & Brzycki Tabanlı Canlı Set İçi Adaptasyon
                </h4>
                <p class="leading-relaxed">
                    Canlı antrenman ekranında (<a href="/workout/live" class="text-brand-400 underline font-semibold">Canlı Antrenman Başlat</a>) her setin ardından tekrar sayınızı ve 3 butonlu RIR değerinizi girersiniz. Sistem bir sonraki setin ağırlığını standart bir şablondan okumaz; anlık olarak şu formülle hesaplar:
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-1">
                    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
                        <span class="text-brand-400 font-bold block mb-1">RIR 3+ (Hafif Geldi):</span>
                        <p class="text-[11px] text-slate-400 mb-1.5">Kullanıcının gerçek tükeniş kapasitesi ($R_{\text{fail}} = \text{Tekrar} + 3$) hesaplanır. Epley formülü ile 1RM çıkarılır:</p>
                        <code class="text-[11px] text-emerald-400 font-mono block bg-slate-950 p-1.5 rounded">1RM = W × (1 + R_fail / 30)</code>
                        <p class="text-[11px] text-emerald-300 mt-1 font-semibold">→ Bir sonraki sete derhal +2.5 kg artış önerisi canlı rozetle yansıtılır.</p>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
                        <span class="text-indigo-400 font-bold block mb-1">RIR 1-2 (İdeal Hipertrofi):</span>
                        <p class="text-[11px] text-slate-400 mb-1.5">Mekanik gerilimin ve motor ünite aktivasyonunun en yüksek olduğu altın hipertrofi penceresidir.</p>
                        <code class="text-[11px] text-indigo-300 font-mono block bg-slate-950 p-1.5 rounded">Ağırlık Değişimi = 0 kg (Korundu)</code>
                        <p class="text-[11px] text-slate-300 mt-1">→ Kilo korunur, 90 saniye dinlenme sayacı başlatılır.</p>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
                        <span class="text-rose-400 font-bold block mb-1">RIR 0 / Form Bozulması:</span>
                        <p class="text-[11px] text-slate-400 mb-1.5">Aşırı merkezi sinir sistemi (MSS) tükenişi ve sakatlık riski algılanır.</p>
                        <code class="text-[11px] text-rose-300 font-mono block bg-slate-950 p-1.5 rounded">W_next = W × 0.90 (-%10) || +30s Mola</code>
                        <p class="text-[11px] text-rose-300 mt-1 font-semibold">→ Yük regrese edilir ve dinlenme süresine +30 sn eklenir.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- WEEKLY WORKOUT DAYS & DETAILED EXERCISE LISTS -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-calendar-check text-brand-400"></i>
                Haftalık Antrenman Takvimi & Egzersiz Planı
            </h2>
            <span class="text-xs text-slate-500">Tüm setlerde tek dokunuşla canlı RIR ağırlık adaptasyonu</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <?php foreach ($programDays as $day): ?>
            <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl hover:border-slate-700 transition-all flex flex-col justify-between space-y-4">
                <div>
                    <!-- Day Header -->
                    <div class="flex items-center justify-between mb-3 border-b border-slate-800/80 pb-3">
                        <div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-brand-500/10 text-brand-400 border border-brand-500/20">
                                <?= $daysOfWeekTr[(int)$day['day_of_week']] ?? 'Gün' ?>
                            </span>
                            <h3 class="text-lg font-bold font-display text-white mt-1"><?= Security::escape($day['focus_area']) ?></h3>
                        </div>
                        <span class="text-xs text-slate-400 font-bold bg-slate-950 px-2.5 py-1 rounded-lg border border-slate-800">
                            <?= (int)$day['exercise_count'] ?> Egzersiz
                        </span>
                    </div>

                    <!-- Exercises List in this Session -->
                    <div class="space-y-2.5 pt-1">
                        <?php if (!empty($day['exercises'])): ?>
                            <?php foreach ($day['exercises'] as $ex): ?>
                            <div class="p-3 rounded-xl bg-slate-950/70 border border-slate-800/80 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 rounded-lg bg-slate-900 border border-slate-800 text-[11px] font-bold text-slate-400 flex items-center justify-center shrink-0">
                                        <?= (int)$ex['order_in_session'] ?>
                                    </span>
                                    <div>
                                        <h4 class="text-xs font-bold text-white leading-tight"><?= Security::escape($ex['exercise_name']) ?></h4>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[10px] text-brand-400 capitalize"><?= Security::escape($ex['primary_muscle_group']) ?></span>
                                            <span class="text-[10px] text-slate-500">• <?= Security::escape($ex['required_equipment']) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-xs font-black text-white block">
                                        <?= (int)$ex['target_sets'] ?> Set
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-medium">
                                        <?= (int)$ex['target_reps_min'] ?>-<?= (int)$ex['target_reps_max'] ?> Tekrar
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-xs text-slate-500 py-2">Egzersizler hazırlanıyor...</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Launch Button -->
                <div class="pt-2">
                    <a href="/workout/live/<?= $day['id'] ?>" class="btn-sporty w-full py-3.5 px-4 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-play text-xs"></i>
                        <span>Bu Antrenmanı Canlı Başlat</span>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- RECENT COMPLETED SESSIONS HISTORY -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl backdrop-blur-xl">
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-brand-400"></i> Son Tamamlanan Antrenman Seansları
        </h2>

        <?php if (empty($recentWorkouts)): ?>
            <div class="text-center py-8 text-slate-500 text-xs">
                Henüz tamamlanmış bir antrenman bulunmuyor. İlk oturumunuzu başlatmak için yukarıdaki günlerden birini seçin!
            </div>
        <?php else: ?>
                        <div class="space-y-3">
                <?php foreach ($recentWorkouts as $rw): ?>
                <div class="flex items-center justify-between p-4 rounded-2xl bg-zinc-950/50 border border-zinc-800">
                    <div>
                        <div class="text-white font-bold mb-1"><?= Security::escape($rw['focus_area'] ?? 'Genel Antrenman') ?></div>
                        <div class="text-[11px] text-slate-500 font-medium">
                            <i class="fa-regular fa-calendar mr-1"></i> <?= date('d.m.Y H:i', strtotime($rw['started_at'])) ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-right">
                        <div>
                            <div class="text-[10px] text-slate-500 uppercase tracking-wider font-bold mb-0.5">Set</div>
                            <div class="text-brand-400 font-black text-sm"><?= (int)$rw['total_sets'] ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-500 uppercase tracking-wider font-bold mb-0.5">Hacim</div>
                            <div class="text-emerald-400 font-black text-sm"><?= number_format((float)$rw['total_volume'], 1, ',', '.') ?> <span class="text-[10px]">kg</span></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- GÜNLÜK ANTRENMAN DEFTERİ (OFFLINE-FIRST LOCALSTORAGE JOURNAL) -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl space-y-6" x-data="workoutJournal()">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center font-bold border border-purple-500/30">
                    <i class="fa-solid fa-book-journal-whills text-lg"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold font-display text-white">Günlük Antrenman Defteri & Seans Notları</h2>
                    <p class="text-xs text-slate-400">Çevrimdışı öncelikli (Offline-First) antrenman hissiyatı, enerji, uyku ve pump takibi.</p>
                </div>
            </div>
            <button 
                type="button" 
                @click="showForm = !showForm" 
                class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/20 flex items-center gap-2 transition-all w-fit">
                <i :class="showForm ? 'fa-solid fa-chevron-up' : 'fa-solid fa-plus'"></i>
                <span x-text="showForm ? 'Formu Kapat' : 'Yeni Not Ekle'"></span>
            </button>
        </div>

        <!-- Add Journal Note Form -->
        <div x-show="showForm" x-transition.duration.200ms class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <!-- Tarih -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Tarih</label>
                    <input type="date" x-model="form.date" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-purple-500 outline-none">
                </div>

                <!-- Enerji Düzeyi (1-5) -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Enerji Düzeyi (1-5)</label>
                    <div class="flex items-center gap-1.5 pt-1">
                        <template x-for="n in 5" :key="n">
                            <button type="button" @click="form.energy = n" class="text-sm transition-transform hover:scale-125 focus:outline-none">
                                <i class="fa-solid fa-bolt" :class="n <= form.energy ? 'text-amber-400' : 'text-slate-700'"></i>
                            </button>
                        </template>
                        <span class="text-xs font-mono font-bold text-slate-400 ml-1" x-text="form.energy + '/5'"></span>
                    </div>
                </div>

                <!-- Uyku Süresi & Kalite -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Uyku (Saat / Kalite)</label>
                    <div class="flex gap-2">
                        <input type="number" x-model.number="form.sleepHours" min="3" max="14" step="0.5" placeholder="Saat" class="w-20 bg-slate-900 border border-slate-800 rounded-xl px-2 py-2 text-xs text-white text-center font-mono">
                        <select x-model="form.sleepQuality" class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-2 py-2 text-xs text-white">
                            <option value="harika">Harika</option>
                            <option value="iyi">İyi</option>
                            <option value="orta">Orta</option>
                            <option value="kötü">Yetersiz</option>
                        </select>
                    </div>
                </div>

                <!-- Kas Pump Hissi (1-5) -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Pump / Dolgunluk (1-5)</label>
                    <div class="flex items-center gap-1.5 pt-1">
                        <template x-for="p in 5" :key="p">
                            <button type="button" @click="form.pump = p" class="text-sm transition-transform hover:scale-125 focus:outline-none">
                                <i class="fa-solid fa-fire" :class="p <= form.pump ? 'text-rose-500' : 'text-slate-700'"></i>
                            </button>
                        </template>
                        <span class="text-xs font-mono font-bold text-slate-400 ml-1" x-text="form.pump + '/5'"></span>
                    </div>
                </div>
            </div>

            <!-- Note Textarea -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Antrenman Notu & Hissiyat</label>
                <textarea 
                    x-model="form.note" 
                    rows="3" 
                    placeholder="Bugünkü antrenmanda ağırlıklar nasıl hissettirdi? Odaklanma nasıldı? Formda zorlandığın yerler oldu mu?"
                    class="w-full bg-slate-900 border border-slate-800 rounded-xl p-3 text-xs text-white placeholder-slate-500 focus:border-purple-500 outline-none"></textarea>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="showForm = false" class="px-4 py-2 rounded-xl bg-slate-900 text-slate-400 text-xs font-bold hover:text-white">İptal</button>
                <button type="button" @click="saveEntry()" class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-md shadow-purple-600/30">
                    Notu Kaydet
                </button>
            </div>
        </div>

        <!-- Journal Entries List -->
        <div class="space-y-3">
            <template x-for="(entry, idx) in entries" :key="entry.id || idx">
                <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800/80 hover:border-slate-700 transition-all space-y-2">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 font-mono text-xs font-bold text-white flex items-center gap-1.5">
                                <i class="fa-regular fa-calendar text-slate-500 text-[10px]"></i>
                                <span x-text="entry.date"></span>
                            </span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20 flex items-center gap-1">
                                <i class="fa-solid fa-bolt text-[9px]"></i> Enerji: <span x-text="entry.energy + '/5'"></span>
                            </span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 flex items-center gap-1">
                                <i class="fa-solid fa-moon text-[9px]"></i> Uyku: <span x-text="entry.sleepHours + 's (' + entry.sleepQuality + ')'"></span>
                            </span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-500/10 text-rose-300 border border-rose-500/20 flex items-center gap-1">
                                <i class="fa-solid fa-fire text-[9px]"></i> Pump: <span x-text="entry.pump + '/5'"></span>
                            </span>
                        </div>
                        <button type="button" @click="deleteEntry(idx)" class="text-slate-500 hover:text-rose-400 text-xs transition-colors p-1" title="Notu Sil">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                    <p class="text-xs text-slate-300 whitespace-pre-line leading-relaxed" x-text="entry.note"></p>
                </div>
            </template>

            <div x-show="entries.length === 0" class="text-center py-8 text-slate-500 text-xs border border-dashed border-slate-800 rounded-2xl">
                <i class="fa-solid fa-pen-nib text-xl text-slate-600 mb-2 block"></i>
                Henüz bir antrenman notu kaydedilmemiş. İlk notunuzu eklemek için yukarıdaki butona tıklayın.
            </div>
        </div>
    </div>
</div>

<script>
function workoutJournal() {
    return {
        showForm: false,
        entries: [],
        form: {
            date: new Date().toISOString().slice(0, 10),
            energy: 4,
            sleepHours: 8,
            sleepQuality: 'iyi',
            pump: 4,
            note: ''
        },

        init() {
            try {
                const stored = localStorage.getItem('hipertrofi_workout_journal');
                if (stored) {
                    this.entries = JSON.parse(stored) || [];
                }
            } catch (e) {
                this.entries = [];
            }
        },

        saveEntry() {
            if (!this.form.note.trim()) {
                alert('Lütfen antrenman notunuzu yazın.');
                return;
            }

            const newEntry = {
                id: Date.now(),
                date: this.form.date,
                energy: this.form.energy,
                sleepHours: this.form.sleepHours,
                sleepQuality: this.form.sleepQuality,
                pump: this.form.pump,
                note: this.form.note.trim(),
                createdAt: new Date().toISOString()
            };

            this.entries.unshift(newEntry);
            localStorage.setItem('hipertrofi_workout_journal', JSON.stringify(this.entries));

            // Reset form
            this.form.note = '';
            this.showForm = false;
        },

        deleteEntry(idx) {
            if (confirm('Bu antrenman notunu silmek istediğinizden emin misiniz?')) {
                this.entries.splice(idx, 1);
                localStorage.setItem('hipertrofi_workout_journal', JSON.stringify(this.entries));
            }
        }
    };
}
</script>





