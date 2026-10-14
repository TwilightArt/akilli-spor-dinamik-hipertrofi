<?php
use App\Core\Security;
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl backdrop-blur-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-bold uppercase tracking-wider text-brand-400">Metabolik Adaptasyon</span>
            </div>
            <h1 class="text-2xl font-bold font-display text-white">Günlük Tartı & 7 Günlük Trend</h1>
            <p class="text-xs text-slate-400 mt-1">
                Platformumuz günlük su dalgalanmalarını eler ve 7 günlük hareketli ortalama üzerinden haftalık set hacmini otomatik dengeler.
            </p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- 7-Day Moving Average -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">7 Günlük Hareketli Ortalama</span>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-black font-display text-white">
                    <?= $movingAvg7 !== null ? number_format($movingAvg7, 2, ',', '.') : '—' ?>
                </span>
                <span class="text-sm font-semibold text-slate-400">kg</span>
            </div>
            <span class="text-[11px] text-slate-500 mt-1 block">Gerçek doku trendi</span>
        </div>

        <!-- Weekly Change Rate -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Haftalık Değişim Oranı</span>
            <div class="flex items-baseline gap-2">
                <?php if ($weeklyRate !== null): ?>
                    <span class="text-3xl font-black font-display <?= $weeklyRate < -0.01 ? 'text-amber-400' : ($weeklyRate < 0 ? 'text-emerald-400' : 'text-indigo-400') ?>">
                        <?= ($weeklyRate >= 0 ? '+' : '') . number_format($weeklyRate * 100, 2, ',', '.') ?>%
                    </span>
                <?php else: ?>
                    <span class="text-3xl font-black font-display text-slate-500">—</span>
                <?php endif; ?>
            </div>
            <span class="text-[11px] text-slate-500 mt-1 block">
                <?= ($weeklyRate !== null && $weeklyRate < -0.01) ? 'Hızlı kilo kaybı (>%1): Hacim korunuyor' : 'Normal metabolik pencere' ?>
            </span>
        </div>

        <!-- Last Logged Weight -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Son Tartı Kaydı</span>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-black font-display text-emerald-400">
                    <?= !empty($history[0]) ? number_format((float)$history[0]['weight_kg'], 1, ',', '.') : '—' ?>
                </span>
                <span class="text-sm font-semibold text-slate-400">kg</span>
            </div>
            <span class="text-[11px] text-slate-500 mt-1 block">
                <?= !empty($history[0]) ? date('d.m.Y', strtotime($history[0]['logged_date'])) : 'Kayıt yok' ?>
            </span>
        </div>
    </div>

    <!-- Log Form & Chart Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- New Weigh-In Form -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl backdrop-blur-xl h-fit">
            <h2 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fa-solid fa-plus text-brand-400"></i> Yeni Kilo Girişi
            </h2>

            <form action="/weigh-in" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div>
                    <label for="weight_kg" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Sabah Aç Karnına Kilo (kg)</label>
                    <input type="number" step="0.1" id="weight_kg" name="weight_kg" required value="75.0"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-lg font-black text-white focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label for="logged_date" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Tarih</label>
                    <input type="date" id="logged_date" name="logged_date" required value="<?= date('Y-m-d') ?>"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label for="notes" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Not (İsteğe Bağlı)</label>
                    <input type="text" id="notes" name="notes" placeholder="Örn: Tuzlu yemek sonrası, iyi uyku"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-brand-500">
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm rounded-xl shadow-lg shadow-brand-600/30 transition-all">
                    Tartıyı Kaydet
                </button>
            </form>
        </div>

        <!-- Chart.js Trend Canvas -->
        <div class="lg:col-span-2 bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl backdrop-blur-xl">
            <h2 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fa-solid fa-chart-line text-brand-400"></i> 30 Günlük Kilo & Hareketli Ortalama Trendi
            </h2>

            <div class="h-64 sm:h-72 w-full">
                <canvas id="weightChart"></canvas>
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl backdrop-blur-xl">
        <h2 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-4">Tartı Geçmişi</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider font-semibold">
                        <th class="py-3 px-4">Tarih</th>
                        <th class="py-3 px-4 text-center">Ölçülen Kilo</th>
                        <th class="py-3 px-4">Notlar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php if (empty($history)): ?>
                        <tr><td colspan="3" class="py-6 text-center text-slate-500">Henüz tartı verisi kaydedilmedi.</td></tr>
                    <?php else: ?>
                        <?php foreach ($history as $h): ?>
                        <tr class="hover:bg-slate-800/30">
                            <td class="py-3 px-4 font-medium text-slate-300"><?= date('d.m.Y', strtotime($h['logged_date'])) ?></td>
                            <td class="py-3 px-4 text-center font-bold text-emerald-400"><?= number_format((float)$h['weight_kg'], 1, ',', '.') ?> kg</td>
                            <td class="py-3 px-4 text-slate-400"><?= Security::escape($h['notes'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VÜCUT ÖLÇÜLERİ TAKİPÇİSİ & GELİŞİM DELTALARI -->
    <!-- ========================================================================= -->
    <div x-data="bodyMeasurementsTracker(<?= htmlspecialchars(json_encode($measurements), ENT_QUOTES, 'UTF-8') ?>)" class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl backdrop-blur-xl space-y-6">
        <!-- Section Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-brand-400">Hipertrofi & Antropometri</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-bold font-display text-white flex items-center gap-2.5">
                    <i class="fa-solid fa-ruler-combined text-brand-400 text-lg"></i>
                    <span>Vücut Ölçüleri Takipçisi</span>
                </h2>
                <p class="text-xs text-slate-400 mt-1">
                    Kol, göğüs, bel, omuz ve bacak çevresi ölçümlerinizle kas gelişimi ve incelme trendinizi milimetrik takip edin.
                </p>
            </div>
            <button @click="showForm = !showForm" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-brand-600/30 transition-all flex items-center gap-2">
                <i :class="showForm ? 'fa-solid fa-minus' : 'fa-solid fa-plus'" class="text-xs"></i>
                <span x-text="showForm ? 'Formu Gizle' : 'Yeni Ölçü Ekle'"></span>
            </button>
        </div>

        <!-- Delta Badges: İlk Ölçüm vs Son Ölçüm Gelişim Farkları -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-chart-simple text-indigo-400"></i> Gelişim & Hipertrofi Değişimleri (Delta)
                </h3>
                <?php if (!empty($deltas)): ?>
                    <span class="text-[11px] text-slate-500">İlk Kayıt ile Son Kayıt Karşılaştırması</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($deltas)): ?>
                <?php
                $metricMeta = [
                    'arm_cm'        => ['label' => 'Kol Çevresi', 'unit' => 'cm', 'muscle' => true, 'icon' => 'fa-hand-fist'],
                    'chest_cm'      => ['label' => 'Göğüs', 'unit' => 'cm', 'muscle' => true, 'icon' => 'fa-shield-halved'],
                    'waist_cm'      => ['label' => 'Bel Çevresi', 'unit' => 'cm', 'muscle' => false, 'icon' => 'fa-arrows-left-right-to-line'],
                    'shoulder_cm'   => ['label' => 'Omuz', 'unit' => 'cm', 'muscle' => true, 'icon' => 'fa-arrows-left-right'],
                    'thigh_cm'      => ['label' => 'Uyluk / Bacak', 'unit' => 'cm', 'muscle' => true, 'icon' => 'fa-person-walking'],
                    'hip_cm'        => ['label' => 'Kalça', 'unit' => 'cm', 'muscle' => false, 'icon' => 'fa-cubes'],
                    'neck_cm'       => ['label' => 'Boyun', 'unit' => 'cm', 'muscle' => true, 'icon' => 'fa-head-side-cough'],
                    'calf_cm'       => ['label' => 'Baldır', 'unit' => 'cm', 'muscle' => true, 'icon' => 'fa-shoe-prints'],
                    'body_fat_pct'  => ['label' => 'Yağ Oranı', 'unit' => '%', 'muscle' => false, 'icon' => 'fa-percent'],
                ];
                ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <?php foreach ($deltas as $key => $d): ?>
                        <?php
                        $meta = $metricMeta[$key] ?? ['label' => $key, 'unit' => 'cm', 'muscle' => true, 'icon' => 'fa-ruler'];
                        $diff = (float)$d['diff'];
                        $isGood = $meta['muscle'] ? ($diff > 0) : ($diff < 0);
                        $isNeutral = ($diff == 0.0);
                        $badgeColor = $isNeutral ? 'text-slate-400 bg-slate-800/60 border-slate-700' : ($isGood ? 'text-emerald-400 bg-emerald-950/40 border-emerald-500/30' : 'text-amber-400 bg-amber-950/40 border-amber-500/30');
                        ?>
                        <div class="p-3.5 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 truncate"><?= $meta['label'] ?></span>
                                <i class="fa-solid <?= $meta['icon'] ?> text-slate-600 text-xs"></i>
                            </div>
                            <div class="flex items-baseline justify-between gap-1">
                                <span class="text-lg font-black font-display text-white">
                                    <?= number_format((float)$d['current'], 1, ',', '.') ?> <span class="text-[10px] text-slate-500 font-normal"><?= $meta['unit'] ?></span>
                                </span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold border <?= $badgeColor ?>">
                                    <?= ($diff > 0 ? '+' : '') . number_format($diff, 1, ',', '.') ?> <?= $meta['unit'] ?>
                                </span>
                            </div>
                            <div class="text-[10px] text-slate-500 flex items-center justify-between pt-0.5 border-t border-slate-900">
                                <span>İlk: <?= number_format((float)$d['baseline'], 1, ',', '.') ?> <?= $meta['unit'] ?></span>
                                <span class="<?= $isGood ? 'text-emerald-400' : ($isNeutral ? 'text-slate-500' : 'text-amber-400') ?>">
                                    <?= $meta['muscle'] ? ($diff > 0 ? '▲ Hipertrofi' : ($diff < 0 ? '▼ Azalma' : '— Sabit')) : ($diff < 0 ? '▼ İncelme' : ($diff > 0 ? '▲ Artış' : '— Sabit')) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="p-4 rounded-2xl bg-slate-950/40 border border-slate-800/80 text-center">
                    <p class="text-xs text-slate-400">
                        <i class="fa-solid fa-circle-info text-brand-400 mr-1.5"></i>
                        İlk ve son ölçümünüz arasındaki farkları (hipertrofi ve incelme oranları) görmek için en az 2 ölçüm kaydı ekleyin.
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Add Measurement Collapsible Form -->
        <div x-show="showForm" x-transition.duration.250ms class="bg-slate-950/80 border border-brand-500/30 rounded-2xl p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                <h3 class="text-xs font-bold text-slate-200 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-plus text-brand-400"></i> Yeni Vücut Ölçüsü Kaydet
                </h3>
                <span class="text-[11px] text-slate-500">Santimetre (cm) cinsinden giriniz</span>
            </div>

            <form action="/weigh-in/measurements" method="POST" @submit="onFormSubmit($event)" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label for="m_date" class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Tarih</label>
                        <input type="date" id="m_date" name="measured_date" required value="<?= date('Y-m-d') ?>"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label for="m_chest" class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Göğüs (cm)</label>
                        <input type="number" step="0.1" id="m_chest" name="chest_cm" placeholder="Örn: 102.5"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label for="m_arm" class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Kol (cm)</label>
                        <input type="number" step="0.1" id="m_arm" name="arm_cm" placeholder="Örn: 37.0"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label for="m_waist" class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Bel (cm)</label>
                        <input type="number" step="0.1" id="m_waist" name="waist_cm" placeholder="Örn: 82.0"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label for="m_shoulder" class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Omuz (cm)</label>
                        <input type="number" step="0.1" id="m_shoulder" name="shoulder_cm" placeholder="Örn: 118.0"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label for="m_hip" class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Kalça (cm)</label>
                        <input type="number" step="0.1" id="m_hip" name="hip_cm" placeholder="Örn: 98.0"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label for="m_thigh" class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Uyluk / Bacak (cm)</label>
                        <input type="number" step="0.1" id="m_thigh" name="thigh_cm" placeholder="Örn: 58.5"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label for="m_calf" class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Baldır (cm)</label>
                        <input type="number" step="0.1" id="m_calf" name="calf_cm" placeholder="Örn: 38.0"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label for="m_neck" class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Boyun (cm)</label>
                        <input type="number" step="0.1" id="m_neck" name="neck_cm" placeholder="Örn: 39.0"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label for="m_fat" class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Tahmini Yağ Oranı (%)</label>
                        <input type="number" step="0.1" id="m_fat" name="body_fat_pct" placeholder="Örn: 14.5"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="showForm = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl transition-all">Vazgeç</button>
                    <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold rounded-xl shadow-md transition-all">Ölçüleri Kaydet</button>
                </div>
            </form>
        </div>

        <!-- History Table of Measurements -->
        <div>
            <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-3">Ölçüm Geçmişi</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider font-semibold">
                            <th class="py-3 px-3">Tarih</th>
                            <th class="py-3 px-3 text-center">Göğüs</th>
                            <th class="py-3 px-3 text-center">Kol</th>
                            <th class="py-3 px-3 text-center">Bel</th>
                            <th class="py-3 px-3 text-center">Omuz</th>
                            <th class="py-3 px-3 text-center">Kalça</th>
                            <th class="py-3 px-3 text-center">Bacak</th>
                            <th class="py-3 px-3 text-center">Boyun</th>
                            <th class="py-3 px-3 text-center">Baldır</th>
                            <th class="py-3 px-3 text-center">Yağ %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php if (empty($measurements)): ?>
                            <tr><td colspan="10" class="py-6 text-center text-slate-500">Henüz vücut ölçüsü kaydedilmedi.</td></tr>
                        <?php else: ?>
                            <?php foreach ($measurements as $m): ?>
                            <tr class="hover:bg-slate-800/30">
                                <td class="py-3 px-3 font-medium text-slate-300"><?= date('d.m.Y', strtotime($m['measured_date'])) ?></td>
                                <td class="py-3 px-3 text-center font-bold text-white"><?= $m['chest_cm'] !== null ? number_format((float)$m['chest_cm'], 1, ',', '.') : '—' ?></td>
                                <td class="py-3 px-3 text-center font-bold text-brand-400"><?= $m['arm_cm'] !== null ? number_format((float)$m['arm_cm'], 1, ',', '.') : '—' ?></td>
                                <td class="py-3 px-3 text-center font-bold text-emerald-400"><?= $m['waist_cm'] !== null ? number_format((float)$m['waist_cm'], 1, ',', '.') : '—' ?></td>
                                <td class="py-3 px-3 text-center font-bold text-white"><?= $m['shoulder_cm'] !== null ? number_format((float)$m['shoulder_cm'], 1, ',', '.') : '—' ?></td>
                                <td class="py-3 px-3 text-center font-bold text-white"><?= $m['hip_cm'] !== null ? number_format((float)$m['hip_cm'], 1, ',', '.') : '—' ?></td>
                                <td class="py-3 px-3 text-center font-bold text-white"><?= $m['thigh_cm'] !== null ? number_format((float)$m['thigh_cm'], 1, ',', '.') : '—' ?></td>
                                <td class="py-3 px-3 text-center text-slate-400"><?= $m['neck_cm'] !== null ? number_format((float)$m['neck_cm'], 1, ',', '.') : '—' ?></td>
                                <td class="py-3 px-3 text-center text-slate-400"><?= $m['calf_cm'] !== null ? number_format((float)$m['calf_cm'], 1, ',', '.') : '—' ?></td>
                                <td class="py-3 px-3 text-center font-semibold text-indigo-300"><?= $m['body_fat_pct'] !== null ? number_format((float)$m['body_fat_pct'], 1, ',', '.') . '%' : '—' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('weightChart');
    if (!ctx) return;

    const labels = <?= $chartLabels ?>;
    const rawData = <?= $chartRawWeights ?>;
    const movingAvgData = <?= $chartMovingAvg ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '7 Günlük Hareketli Ort.',
                    data: movingAvgData,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: false,
                    pointRadius: 0
                },
                {
                    label: 'Günlük Tartı',
                    data: rawData,
                    borderColor: 'rgba(148, 163, 184, 0.4)',
                    backgroundColor: '#10b981',
                    borderWidth: 1.5,
                    borderDash: [4, 4],
                    tension: 0.1,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#10b981'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: '#94a3b8', font: { family: 'Inter', size: 11 } }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(51, 65, 85, 0.3)' },
                    ticks: { color: '#64748b', font: { family: 'Inter', size: 10 } }
                },
                y: {
                    grid: { color: 'rgba(51, 65, 85, 0.3)' },
                    ticks: { color: '#64748b', font: { family: 'Inter', size: 10 } }
                }
            }
        }
    });
});

function bodyMeasurementsTracker(serverData) {
    return {
        showForm: false,
        measurements: serverData || [],

        init() {
            try {
                // If we have server data, sync to localStorage for offline access
                if (this.measurements && this.measurements.length > 0) {
                    localStorage.setItem('hipertrofi_body_measurements', JSON.stringify(this.measurements));
                } else {
                    // Check if there are cached offline measurements
                    const cached = localStorage.getItem('hipertrofi_body_measurements');
                    if (cached) {
                        this.measurements = JSON.parse(cached) || [];
                    }
                }
            } catch (e) {
                console.warn('LocalStorage error:', e);
            }
        },

        onFormSubmit(e) {
            // Save optimistic copy to localStorage
            const form = e.target;
            const entry = {
                measured_date: form.querySelector('[name="measured_date"]')?.value || new Date().toISOString().slice(0, 10),
                chest_cm: form.querySelector('[name="chest_cm"]')?.value || null,
                arm_cm: form.querySelector('[name="arm_cm"]')?.value || null,
                waist_cm: form.querySelector('[name="waist_cm"]')?.value || null,
                shoulder_cm: form.querySelector('[name="shoulder_cm"]')?.value || null,
                hip_cm: form.querySelector('[name="hip_cm"]')?.value || null,
                thigh_cm: form.querySelector('[name="thigh_cm"]')?.value || null,
                neck_cm: form.querySelector('[name="neck_cm"]')?.value || null,
                calf_cm: form.querySelector('[name="calf_cm"]')?.value || null,
                body_fat_pct: form.querySelector('[name="body_fat_pct"]')?.value || null,
            };
            try {
                const current = JSON.parse(localStorage.getItem('hipertrofi_body_measurements') || '[]');
                current.unshift(entry);
                localStorage.setItem('hipertrofi_body_measurements', JSON.stringify(current));
            } catch (err) {}
            // Form submits naturally to POST /weigh-in/measurements
        }
    };
}
</script>
