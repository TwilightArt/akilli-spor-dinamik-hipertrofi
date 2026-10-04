<?php
use App\Core\Security;

$activeExName = $activeExercise ? Security::escape($activeExercise['name']) : 'Egzersiz SeÃ§ilmedi';
$activeExGroup = $activeExercise ? Security::escape($activeExercise['primary_muscle_group'] ?? '') : '';
$activeExPattern = $activeExercise ? Security::escape($activeExercise['movement_pattern'] ?? '') : '';
$activeExEquipment = $activeExercise ? Security::escape($activeExercise['required_equipment'] ?? '') : '';

// Prepare JSON data for Chart.js
$timelineLabels = [];
$timelineWeights = [];
$timeline1rms = [];
$timelineVolumes = [];

foreach ($timeline as $t) {
    $timelineLabels[] = date('d M', strtotime($t['session_date']));
    $timelineWeights[] = (float)$t['peak_weight'];
    $timeline1rms[] = (float)$t['peak_estimated_1rm'];
    $timelineVolumes[] = (float)$t['session_volume'];
}

// Prepare Peak Comparison Data across all exercises
$peakLabels = [];
$peakWeights = [];
$peakColors = [];
$groupColorMap = [
    'chest'     => 'rgba(99, 102, 241, 0.85)',
    'back'      => 'rgba(6, 182, 212, 0.85)',
    'legs'      => 'rgba(16, 185, 129, 0.85)',
    'shoulders' => 'rgba(245, 158, 11, 0.85)',
    'arms'      => 'rgba(168, 85, 247, 0.85)',
    'core'      => 'rgba(236, 72, 153, 0.85)'
];

foreach ($allPeaks as $p) {
    $peakLabels[] = $p['name'];
    $peakWeights[] = (float)$p['peak_weight'];
    $grp = $p['primary_muscle_group'] ?? 'chest';
    $peakColors[] = $groupColorMap[$grp] ?? 'rgba(99, 102, 241, 0.85)';
}
?>

<div class="space-y-8 pb-20">

    <!-- Top Hero Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950/60 to-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden backdrop-blur-xl">
        <div class="absolute -right-20 -top-20 w-64 h-64 bg-brand-600/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-brand-500/10 text-brand-300 border border-brand-500/20">
                        <i class="fa-solid fa-chart-line mr-1"></i> AÄŸÄ±rlÄ±k & GÃ¼Ã§ AnalitiÄŸi
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        Progressive Overload Takibi
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black font-display text-white tracking-tight">
                    Hareket BazlÄ± AÄŸÄ±rlÄ±k GeliÅŸimi & Zirve Metrikleri
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1 max-w-2xl">
                    Her egzersizde tarihten bugÃ¼ne kaÃ§ kiloya kadar Ã§Ä±kÄ±ldÄ±ÄŸÄ±nÄ±, tek tekrar maksimumu (1RM) kapasitenizi ve antrenman seanslarÄ± arasÄ±ndaki aÅŸamalÄ± aÅŸÄ±rÄ± yÃ¼klenme eÄŸrisini canlÄ± grafiklerle inceleyin.
                </p>
            </div>

            <!-- Quick Action: Demo Data Seeder (Local dev only) -->
            <div class="flex items-center gap-3 shrink-0">
                <?php if (\App\Core\Security::isLocalEnv()): ?>
                <form action="/analytics/sample_data" method="POST" onsubmit="return confirm('Hesabınıza 4 haftalık gerçekçi kademeli ağırlık artış geçmişi eklensin mi?');">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white border border-slate-700 text-xs font-bold transition-all flex items-center gap-2 shadow-lg">
                        <i class="fa-solid fa-wand-magic-sparkles text-brand-400"></i>
                        <span>Örnek Gelişim Verisi Yükle (4 Hafta)</span>
                    </button>
                </form>
                <?php endif; ?>
                <a href="/workout/live" class="px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold transition-all flex items-center gap-2 shadow-lg shadow-brand-600/30">
                    <i class="fa-solid fa-play text-xs"></i>
                    <span>CanlÄ± Antrenman</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4 Peak Metric Cards for Currently Active Exercise -->
    <?php if ($activeExercise): ?>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <!-- Metric 1: Peak Weight Reached -->
        <div class="p-5 rounded-3xl bg-slate-900/90 border border-emerald-500/30 shadow-xl backdrop-blur-xl relative overflow-hidden group">
            <div class="absolute -right-6 -bottom-6 w-20 h-20 bg-emerald-500/10 rounded-full blur-xl pointer-events-none"></div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">En YÃ¼ksek AÄŸÄ±rlÄ±k Zirvesi</span>
                <span class="w-7 h-7 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-trophy"></i>
                </span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-3xl font-black font-display text-emerald-400"><?= number_format($metrics['peak_weight'], 1) ?></span>
                <span class="text-sm font-bold text-slate-400">kg</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Bu harekette Ã§Ä±kÄ±lan maksimum yÃ¼k</p>
        </div>

        <!-- Metric 2: Estimated 1RM Peak -->
        <div class="p-5 rounded-3xl bg-slate-900/90 border border-brand-500/30 shadow-xl backdrop-blur-xl relative overflow-hidden group">
            <div class="absolute -right-6 -bottom-6 w-20 h-20 bg-brand-500/10 rounded-full blur-xl pointer-events-none"></div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tahmini 1RM Kapasitesi</span>
                <span class="w-7 h-7 rounded-xl bg-brand-500/10 text-brand-400 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-gauge-high"></i>
                </span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-3xl font-black font-display text-indigo-300"><?= number_format($metrics['peak_1rm'], 1) ?></span>
                <span class="text-sm font-bold text-slate-400">kg</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Epley FormÃ¼lÃ¼ tek tekrar gÃ¼cÃ¼</p>
        </div>

        <!-- Metric 3: Progressive Overload Gain -->
        <div class="p-5 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-xl backdrop-blur-xl relative overflow-hidden group">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">GeliÅŸim & ArtÄ±ÅŸ OranÄ±</span>
                <span class="w-7 h-7 rounded-xl bg-brand-600/10 text-brand-400 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-3xl font-black font-display <?= $metrics['progression_kg'] >= 0 ? 'text-emerald-400' : 'text-rose-400' ?>">
                    <?= ($metrics['progression_kg'] >= 0 ? '+' : '') . number_format($metrics['progression_kg'], 1) ?>
                </span>
                <span class="text-sm font-bold text-slate-400">kg</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full <?= $metrics['progression_pct'] >= 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' ?>">
                    <?= ($metrics['progression_pct'] >= 0 ? 'â†‘ +' : 'â†“ ') . $metrics['progression_pct'] ?>%
                </span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Ä°lk seans: <?= number_format($metrics['starting_weight'], 1) ?> kg</p>
        </div>

        <!-- Metric 4: Total Volume & Sets -->
        <div class="p-5 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-xl backdrop-blur-xl relative overflow-hidden group">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">KaldÄ±rÄ±lan Toplam Hacim</span>
                <span class="w-7 h-7 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-layer-group"></i>
                </span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-3xl font-black font-display text-white"><?= number_format($metrics['total_volume'], 0, ',', '.') ?></span>
                <span class="text-sm font-bold text-slate-400">kg</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Toplam <?= $metrics['total_sets'] ?> tamamlanan sette</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Interactive Exercise Switcher Chips -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl backdrop-blur-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-white flex items-center gap-2">
                    <i class="fa-solid fa-dumbbell text-brand-400"></i>
                    Analiz Edilecek Egzersizi SeÃ§in
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Ãœzerine tÄ±klayarak ilgili hareketin seans bazlÄ± geliÅŸim grafiÄŸine ve set geÃ§miÅŸine geÃ§iÅŸ yapÄ±n.</p>
            </div>
            <span class="text-xs text-slate-500 font-bold">Toplam <?= count($exerciseList) ?> Egzersiz Takipte</span>
        </div>

        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
            <?php foreach ($exerciseList as $exItem): ?>
                <?php 
                $isSelected = ($activeExercise && (int)$exItem['id'] === (int)$activeExercise['id']);
                $hasLogs = ((int)$exItem['total_sets_logged'] > 0);
                ?>
                <a href="/analytics?exercise_id=<?= (int)$exItem['id'] ?>"
                   class="px-4 py-2.5 rounded-2xl text-xs font-bold whitespace-nowrap transition-all flex items-center gap-2.5 border <?= $isSelected ? 'bg-gradient-to-r from-brand-600 to-indigo-600 text-white border-brand-400 shadow-lg shadow-brand-600/30' : ($hasLogs ? 'bg-slate-950/80 hover:bg-slate-800 text-slate-200 border-slate-800 hover:border-slate-700' : 'bg-slate-950/40 text-slate-500 border-slate-900 hover:border-slate-800') ?>">
                    <span class="w-2 h-2 rounded-full <?= $isSelected ? 'bg-white' : ($hasLogs ? 'bg-emerald-400' : 'bg-slate-600') ?>"></span>
                    <span><?= Security::escape($exItem['name']) ?></span>
                    <?php if ($hasLogs): ?>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black <?= $isSelected ? 'bg-white/20 text-white' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' ?>">
                            <?= number_format((float)$exItem['max_weight_ever'], 1) ?> kg
                        </span>
                    <?php else: ?>
                        <span class="text-[10px] text-slate-600">HenÃ¼z Veri Yok</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    
    <!-- Muscle Fatigue Heatmap -->
    <div class="mb-6 p-6 rounded-3xl card-sporty relative overflow-hidden">
        <h2 class="text-xl font-bold font-display text-gradient mb-4">Son 7 Günlük Kas Yorgunluğu (Isı Haritası)</h2>
        <p class="text-sm text-slate-400 mb-6">Kas gruplarınızın maruz kaldığı iş hacmi (aktif set sayısı). İdeal büyüme için dinlenme ve yüklenme dengesine dikkat edin.</p>
        <div class="flex overflow-x-auto snap-x snap-mandatory gap-4 pb-4 hide-scrollbar">
            <?php 
            $muscleNames = [
                'chest' => 'Göğüs', 'back' => 'Sırt', 'legs' => 'Bacak', 
                'shoulders' => 'Omuz', 'arms' => 'Kollar', 'core' => 'Karın'
            ];
            foreach ($heatmap as $muscle => $sets): 
                $percentage = min(100, ($sets / 15) * 100);
                $colorClass = 'text-emerald-500';
                if ($percentage > 80) $colorClass = 'text-rose-500';
                elseif ($percentage > 50) $colorClass = 'text-orange-500';
                elseif ($percentage > 25) $colorClass = 'text-brand-400';
            ?>
            <div class="flex flex-col items-center bg-zinc-950/50 p-4 rounded-2xl border border-zinc-800 min-w-[120px] snap-center shrink-0">
                <div class="relative w-16 h-16 flex items-center justify-center mb-3">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-zinc-800" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="<?= $colorClass ?> transition-all duration-1000 ease-out" stroke-dasharray="<?= $percentage ?>, 100" stroke-width="3" stroke="currentColor" fill="none" stroke-linecap="round" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <div class="absolute flex flex-col items-center justify-center text-white">
                        <span class="text-lg font-bold font-display"><?= $sets ?></span>
                    </div>
                </div>
                <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider"><?= $muscleNames[$muscle] ?? $muscle ?></span>
                <span class="text-[10px] text-slate-500 mt-1">Set</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Main Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Chart 1: Progressive Overload Timeline for Active Movement (2 Columns) -->
        <div class="lg:col-span-2 bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl backdrop-blur-xl space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-brand-400"><?= $activeExGroup ?></span>
                        <span class="text-xs text-slate-500">â€¢</span>
                        <span class="text-xs text-slate-400"><?= $activeExEquipment ?></span>
                    </div>
                    <h3 class="text-lg font-bold font-display text-white">
                        <?= $activeExName ?> â€” Seans BazlÄ± AÄŸÄ±rlÄ±k & 1RM GeliÅŸimi
                    </h3>
                </div>
                <div class="flex items-center gap-4 text-xs">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                        <span class="text-slate-300 font-semibold">Ã‡Ä±kÄ±lan AÄŸÄ±rlÄ±k (kg)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-indigo-400"></span>
                        <span class="text-slate-300 font-semibold">Tahmini 1RM (kg)</span>
                    </div>
                </div>
            </div>

            <!-- Canvas Container -->
            <div class="relative h-72 sm:h-80 w-full">
                <?php if (!empty($timeline)): ?>
                    <canvas id="exerciseTimelineChart"></canvas>
                <?php else: ?>
                    <div class="h-full flex flex-col items-center justify-center text-center p-6 space-y-3">
                        <div class="w-14 h-14 rounded-2xl bg-brand-600/10 border border-brand-500/20 text-brand-400 flex items-center justify-center text-2xl">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <h4 class="text-sm font-bold text-white">Bu Egzersiz Ä°Ã§in HenÃ¼z KayÄ±tlÄ± Antrenman Yok</h4>
                        <p class="text-xs text-slate-400 max-w-sm">
                            Bu egzersizi canlÄ± antrenmanda uyguladÄ±ÄŸÄ±nÄ±zda veya yukarÄ±daki "Ã–rnek GeliÅŸim Verisi YÃ¼kle" butonuna tÄ±kladÄ±ÄŸÄ±nÄ±zda aÄŸÄ±rlÄ±k deÄŸiÅŸim eÄŸrisi burada oluÅŸacaktÄ±r.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chart 2: All Exercises Peak Weight Comparison Bar Chart (1 Column) -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl backdrop-blur-xl space-y-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-4">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-brand-400 block mb-0.5">KÄ±yaslama AnalitiÄŸi</span>
                        <h3 class="text-base font-bold font-display text-white">Hareket Hareket Zirve AÄŸÄ±rlÄ±klar</h3>
                    </div>
                    <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 px-2.5 py-1 rounded-lg border border-emerald-500/20">
                        KaÃ§ kg'a Ã‡Ä±kÄ±ldÄ±?
                    </span>
                </div>

                <div class="relative h-64 sm:h-72 w-full">
                    <?php if (!empty($allPeaks)): ?>
                        <canvas id="allPeaksComparisonChart"></canvas>
                    <?php else: ?>
                        <div class="h-full flex flex-col items-center justify-center text-center p-4 text-xs text-slate-500">
                            HenÃ¼z tamamlanmÄ±ÅŸ set bulunmuyor.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-800/80 text-[11px] text-slate-400 flex items-center justify-between">
                <span>Kas GruplarÄ±: Renk Kodlu</span>
                <span class="font-bold text-indigo-300">NSCA Epley NormlarÄ±</span>
            </div>
        </div>

    </div>

    <!-- Detailed Set-by-Set Historical Log Table for Active Exercise -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl backdrop-blur-xl space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-4">
            <div>
                <h3 class="text-base font-bold font-display text-white flex items-center gap-2">
                    <i class="fa-solid fa-list-ol text-brand-400"></i>
                    <?= $activeExName ?> â€” DetaylÄ± Set ve Seans KayÄ±tlarÄ±
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Her sette basÄ±lan kilo, tekrar, girilen RIR deÄŸeri ve hesaplanan 1RM zirveleri.</p>
            </div>
            <span class="text-xs font-bold px-3 py-1 rounded-xl bg-slate-950 border border-slate-800 text-slate-300">
                Toplam <?= count($detailedSets) ?> Set KaydÄ±
            </span>
        </div>

        <?php if (!empty($detailedSets)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider font-semibold">
                            <th class="py-3 px-4">Tarih</th>
                            <th class="py-3 px-4 text-center">Set No</th>
                            <th class="py-3 px-4">KaldÄ±rÄ±lan AÄŸÄ±rlÄ±k</th>
                            <th class="py-3 px-4">Tekrar / SÃ¼re</th>
                            <th class="py-3 px-4 text-center">RIR DeÄŸeri</th>
                            <th class="py-3 px-4 text-right">Tahmini 1RM</th>
                            <th class="py-3 px-4 text-center">Durum / Rekor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php foreach ($detailedSets as $set): ?>
                        <tr class="hover:bg-slate-800/30 transition-colors <?= !empty($set['is_pr']) ? 'bg-emerald-950/20' : '' ?>">
                            <td class="py-3.5 px-4 font-medium text-slate-300">
                                <?= date('d.m.Y H:i', strtotime($set['created_at'])) ?>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-lg bg-slate-950 border border-slate-800 text-slate-300 font-bold">
                                    Set <?= (int)$set['set_number'] ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="text-base font-black font-display text-white">
                                    <?= number_format((float)$set['actual_weight'], 1) ?> <span class="text-xs font-normal text-slate-400">kg</span>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-200">
                                <?= $set['set_type'] === 'duration' ? ((int)$set['duration_seconds'] . ' sn') : ((int)$set['actual_reps'] . ' tekrar') ?>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <?php if ($set['rir_bucket'] === '0'): ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-500/10 text-rose-400 border border-rose-500/30">
                                        RIR 0 (TÃ¼keniÅŸ)
                                    </span>
                                <?php elseif ($set['rir_bucket'] === '1-2'): ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                        RIR 1-2 (Ä°deal)
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-indigo-500/10 text-indigo-300 border border-indigo-500/30">
                                        RIR 3+ (Hafif)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-right font-black text-indigo-300 font-mono">
                                <?= number_format((float)$set['estimated_1rm'], 1) ?> kg
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <?php if (!empty($set['is_pr'])): ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 animate-pulse flex items-center gap-1 justify-center mx-auto w-fit">
                                        <i class="fa-solid fa-crown text-[9px]"></i> ZÄ°RVE PR
                                    </span>
                                <?php elseif (!empty($set['form_breakdown'])): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        Form Bozuldu
                                    </span>
                                <?php else: ?>
                                    <span class="text-[11px] text-slate-500">Nizami Form âœ“</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-10 text-slate-500 text-xs">
                Bu egzersiz iÃ§in henÃ¼z kaydedilmiÅŸ set bulunmamaktadÄ±r.
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Chart.js Initialization Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($timeline)): ?>
    // 1. Exercise Timeline Line Chart
    const ctxTimeline = document.getElementById('exerciseTimelineChart');
    if (ctxTimeline) {
        new Chart(ctxTimeline, {
            type: 'line',
            data: {
                labels: <?= json_encode($timelineLabels) ?>,
                datasets: [
                    {
                        label: 'KaldÄ±rÄ±lan Maksimum AÄŸÄ±rlÄ±k (kg)',
                        data: <?= json_encode($timelineWeights) ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.12)',
                        borderWidth: 3,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'Tahmini 1RM Kapasitesi (kg)',
                        data: <?= json_encode($timeline1rms) ?>,
                        borderColor: '#818cf8',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointBackgroundColor: '#818cf8',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.35
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        borderColor: 'rgba(99, 102, 241, 0.3)',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6,
                        usePointStyle: true
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(51, 65, 85, 0.3)'
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 11, weight: 'bold' }
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(51, 65, 85, 0.3)'
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 11, weight: 'bold' },
                            callback: function(val) { return val + ' kg'; }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    <?php if (!empty($allPeaks)): ?>
    // 2. All Exercises Peak Comparison Bar Chart
    const ctxPeaks = document.getElementById('allPeaksComparisonChart');
    if (ctxPeaks) {
        new Chart(ctxPeaks, {
            type: 'bar',
            data: {
                labels: <?= json_encode($peakLabels) ?>,
                datasets: [{
                    label: 'Ã‡Ä±kÄ±lan Zirve AÄŸÄ±rlÄ±k (kg)',
                    data: <?= json_encode($peakWeights) ?>,
                    backgroundColor: <?= json_encode($peakColors) ?>,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y', // Horizontal bars for clean reading of exercise names
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        borderColor: 'rgba(16, 185, 129, 0.3)',
                        borderWidth: 1,
                        padding: 10,
                        callbacks: {
                            label: function(context) {
                                return 'Zirve: ' + context.raw + ' kg';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(51, 65, 85, 0.3)'
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 10 },
                            callback: function(val) { return val + ' kg'; }
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#e2e8f0',
                            font: { size: 10, weight: '600' }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>


