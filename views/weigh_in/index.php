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
</script>
