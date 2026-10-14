<?php
declare(strict_types=1);
require_once __DIR__ . '/../layouts/header.php';
use App\Config\Constants;

$nscaTable = Constants::NSCA_PERCENTAGE_TABLE;
?>

<div class="max-w-5xl mx-auto space-y-6" x-data="rpeCalculator(<?= htmlspecialchars(json_encode($nscaTable), ENT_QUOTES, 'UTF-8') ?>)">
    <?php $activeTab = 'rpe'; require __DIR__ . '/_tabs.php'; ?>

    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 bg-slate-900 p-6 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center border border-indigo-500/30">
                    <i class="fa-solid fa-gauge-high text-lg"></i>
                </div>
                <h1 class="text-2xl font-black font-display text-white tracking-tight">RPE & RIR Çalışma Ağırlığı Simülatörü</h1>
            </div>
            <p class="text-sm text-slate-400 max-w-2xl">NSCA Epley normları ve RIR (Reps in Reserve) rezerv tekrar metodolojisiyle hedef setleriniz için en uygun çalışma ağırlığını hesaplayın.</p>
        </div>
        <div class="relative z-10 flex items-center gap-2">
            <span class="text-xs font-bold px-3 py-1.5 rounded-xl bg-indigo-500/10 border border-indigo-500/30 text-indigo-300">
                <i class="fa-solid fa-graduation-cap text-[11px] mr-1"></i> RTS & Mike Tuchscherer Modeli
            </span>
        </div>
    </div>

    <!-- Simulator & Live Result Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Interactive Controls -->
        <div class="lg:col-span-5 bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-5">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3 flex items-center gap-2">
                <i class="fa-solid fa-sliders text-indigo-400 text-sm"></i> Hedefinizi Seçin
            </h2>

            <!-- 1RM Ağırlığı -->
            <div>
                <div class="flex justify-between items-center mb-1.5">
                    <label for="one_rm_input" class="text-xs font-bold text-slate-300 uppercase tracking-wider">Mevcut 1RM Ağırlığı</label>
                    <span class="text-xs font-mono font-bold text-indigo-400" x-text="oneRm + ' kg'"></span>
                </div>
                <div class="relative">
                    <input 
                        type="number" 
                        id="one_rm_input" 
                        x-model.number="oneRm" 
                        min="20" 
                        max="400" 
                        step="2.5" 
                        class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-500 transition-colors text-base font-semibold"
                        @input="calculate(); saveLocal();"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-xs text-slate-500">kg</div>
                </div>
                <input 
                    type="range" 
                    x-model.number="oneRm" 
                    min="30" 
                    max="220" 
                    step="2.5" 
                    class="w-full accent-indigo-500 mt-2 cursor-pointer"
                    @input="calculate(); saveLocal();"
                >
            </div>

            <!-- Hedef Tekrar Sayısı -->
            <div>
                <div class="flex justify-between items-center mb-1.5">
                    <label for="reps_input" class="text-xs font-bold text-slate-300 uppercase tracking-wider">Hedef Tekrar Sayısı</label>
                    <span class="text-xs font-mono font-bold text-indigo-400" x-text="targetReps + ' tekrar'"></span>
                </div>
                <div class="relative">
                    <input 
                        type="number" 
                        id="reps_input" 
                        x-model.number="targetReps" 
                        min="1" 
                        max="12" 
                        step="1" 
                        class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-500 transition-colors text-base font-semibold"
                        @input="calculate(); saveLocal();"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-xs text-slate-500">tekrar</div>
                </div>
                <div class="flex gap-1.5 mt-2 overflow-x-auto pb-1 scrollbar-none">
                    <template x-for="r in [1, 3, 5, 6, 8, 10, 12]" :key="r">
                        <button 
                            type="button" 
                            @click="targetReps = r; calculate(); saveLocal();"
                            class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all"
                            :class="targetReps === r ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-slate-950 text-slate-400 border-slate-800 hover:text-white'"
                            x-text="r">
                        </button>
                    </template>
                </div>
            </div>

            <!-- RPE / RIR Seçimi -->
            <div>
                <div class="flex justify-between items-center mb-1.5">
                    <label class="text-xs font-bold text-slate-300 uppercase tracking-wider">Hedef RPE / RIR Şiddeti</label>
                    <span class="text-xs font-mono font-bold" :class="intensityClass" x-text="'RPE ' + selectedRpe.toFixed(1) + ' (RIR ' + rir.toFixed(1) + ')'"></span>
                </div>
                <select 
                    x-model.number="selectedRpe" 
                    @change="calculate(); saveLocal();"
                    class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-500 transition-colors text-sm font-semibold cursor-pointer">
                    <option value="10">RPE 10.0 — RIR 0 (Tükeniş / Maksimum Çaba)</option>
                    <option value="9.5">RPE 9.5 — RIR 0.5 (Belki 1 tekrar daha çıkabilir)</option>
                    <option value="9">RPE 9.0 — RIR 1 (Kesin 1 nizami tekrar payı)</option>
                    <option value="8.5">RPE 8.5 — RIR 1.5 (1-2 tekrar rezerv)</option>
                    <option value="8" selected>RPE 8.0 — RIR 2 (İdeal Hipertrofi / Altın Standart)</option>
                    <option value="7.5">RPE 7.5 — RIR 2.5 (2-3 tekrar rezerv)</option>
                    <option value="7">RPE 7.0 — RIR 3 (Hacim / Güç Birikimi)</option>
                    <option value="6.5">RPE 6.5 — RIR 3.5 (3-4 tekrar rezerv)</option>
                    <option value="6">RPE 6.0 — RIR 4 (Hız, Teknik ve Patlayıcılık)</option>
                </select>
            </div>
        </div>

        <!-- Calculated Output & Interpretation -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Hero Result Card -->
            <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950/40 border border-indigo-500/30 rounded-3xl p-6 shadow-2xl relative overflow-hidden">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                    <div class="space-y-2 text-center sm:text-left">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-400 block">Önerilen Çalışma Ağırlığı</span>
                        <div class="flex items-baseline gap-2 justify-center sm:justify-start">
                            <span class="text-5xl font-black font-display text-white tracking-tight" x-text="targetWeight.toFixed(1)"></span>
                            <span class="text-2xl font-bold text-indigo-400 font-display">kg</span>
                        </div>
                        <div class="flex items-center gap-3 justify-center sm:justify-start pt-1">
                            <span class="text-xs font-mono font-bold px-3 py-1 rounded-lg bg-slate-950 border border-slate-800 text-slate-300" x-text="'1RM %' + (intensityPercent * 100).toFixed(1)"></span>
                            <span class="text-xs font-mono font-bold px-3 py-1 rounded-lg" :class="intensityBadgeClass" x-text="intensityDesc"></span>
                        </div>
                    </div>

                    <!-- Plaka / Yükseklik Mini Bilgi -->
                    <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-4 text-center min-w-[140px] space-y-1">
                        <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wider block">Bar Başına Plaka</span>
                        <span class="text-lg font-black font-mono text-emerald-400" x-text="perSideWeight + ' kg'"></span>
                        <span class="text-[10px] text-slate-400 block">(20kg Olimpik Bar Dahil)</span>
                    </div>
                </div>

                <!-- Explanation Footnote -->
                <div class="mt-6 pt-5 border-t border-slate-800/80 text-xs text-slate-400 flex items-center justify-between">
                    <span>Eşdeğer Tükeniş Yükü: <b class="text-white font-mono" x-text="equivalentReps.toFixed(1) + ' Tekrar'"></b></span>
                    <span>Tavsiye Edilen Set Arası Dinlenme: <b class="text-indigo-300 font-mono" x-text="restRecommendation"></b></span>
                </div>
            </div>

            <!-- RPE Intensity Guide Badges -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-3">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-indigo-400"></i> Şiddet Seviyeleri ve Antrenman Amacı
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                    <div class="p-3 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-200">
                        <div class="font-bold flex items-center gap-1.5 text-rose-300 mb-0.5">
                            <i class="fa-solid fa-fire text-xs"></i> RPE 9.5 - 10.0 (RIR 0)
                        </div>
                        <p class="text-[11px] text-slate-400">Maksimum nöral tükeniş. Sadece PR denemeleri ve döngü son setlerinde kullanılmalıdır.</p>
                    </div>
                    <div class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-200">
                        <div class="font-bold flex items-center gap-1.5 text-amber-300 mb-0.5">
                            <i class="fa-solid fa-dumbbell text-xs"></i> RPE 8.5 - 9.0 (RIR 1)
                        </div>
                        <p class="text-[11px] text-slate-400">İleri hipertrofi uyarısı. Yüksek mekanik gerilim ve etkin motor ünite katılımı sağlar.</p>
                    </div>
                    <div class="p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-200">
                        <div class="font-bold flex items-center gap-1.5 text-emerald-300 mb-0.5">
                            <i class="fa-solid fa-trophy text-xs"></i> RPE 7.5 - 8.0 (RIR 2)
                        </div>
                        <p class="text-[11px] text-slate-400">Optimal hipertrofi tatlı noktası. Minimum yorgunluk birikimi ile maksimum kas gelişimi.</p>
                    </div>
                    <div class="p-3 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-200">
                        <div class="font-bold flex items-center gap-1.5 text-indigo-300 mb-0.5">
                            <i class="fa-solid fa-bolt text-xs"></i> RPE 6.0 - 7.0 (RIR 3-4)
                        </div>
                        <p class="text-[11px] text-slate-400">Hacim ve teknik birikimi. Hareket paternini kusursuzlaştırma ve hız geliştirme.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Complete 2D Color-Coded Reference Matrix Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-table-cells text-indigo-400"></i> İnteraktif RPE & Tekrar Referans Tablosu
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Seçili 1RM (<span class="text-indigo-400 font-bold" x-text="oneRm + ' kg'"></span>) baz alınarak üretilen ağırlıklar. Herhangi bir hücreye tıklayarak simülatörü anında ayarlayabilirsiniz.</p>
            </div>
            <div class="flex items-center gap-2 text-[10px] font-bold">
                <span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/30">RIR 0</span>
                <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">RIR 1</span>
                <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">RIR 2</span>
                <span class="px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">RIR 3-4</span>
            </div>
        </div>

        <div class="overflow-x-auto pb-2 scrollbar-thin">
            <table class="w-full text-left text-xs text-slate-300 border-collapse min-w-[700px]">
                <thead>
                    <tr class="border-b border-slate-800 text-[11px] uppercase tracking-wider font-bold text-slate-400 bg-slate-950/60">
                        <th class="py-3 px-3 text-center">RPE</th>
                        <th class="py-3 px-3 text-center">RIR</th>
                        <template x-for="r in [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]" :key="r">
                            <th class="py-3 px-2 text-center" x-text="r + ' Tekrar'"></th>
                        </template>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-mono">
                    <template x-for="rpeVal in [10.0, 9.5, 9.0, 8.5, 8.0, 7.5, 7.0, 6.5, 6.0]" :key="rpeVal">
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="py-2.5 px-3 text-center font-bold text-white" x-text="rpeVal.toFixed(1)"></td>
                            <td class="py-2.5 px-3 text-center text-slate-400" x-text="(10.0 - rpeVal).toFixed(1)"></td>
                            <template x-for="reps in [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]" :key="reps">
                                <td class="py-2 px-1 text-center">
                                    <button 
                                        type="button" 
                                        @click="selectCell(reps, rpeVal)" 
                                        class="w-full py-1.5 px-1 rounded-lg text-[11px] font-bold border transition-all hover:scale-105"
                                        :class="getCellStyle(reps, rpeVal)">
                                        <span x-text="getMatrixWeight(reps, rpeVal)"></span>
                                    </button>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function rpeCalculator(nscaTable) {
    return {
        oneRm: parseFloat(localStorage.getItem('hipertrofi_rpe_1rm')) || 100.0,
        targetReps: parseInt(localStorage.getItem('hipertrofi_rpe_reps')) || 5,
        selectedRpe: parseFloat(localStorage.getItem('hipertrofi_rpe_val')) || 8.0,
        nsca: nscaTable || {},

        rir: 2.0,
        equivalentReps: 7.0,
        intensityPercent: 0.83,
        targetWeight: 83.0,
        perSideWeight: 31.5,

        init() {
            this.calculate();
        },

        calculate() {
            this.oneRm = Math.max(10, parseFloat(this.oneRm) || 100.0);
            this.targetReps = Math.max(1, Math.min(12, parseInt(this.targetReps) || 5));
            this.selectedRpe = Math.max(6.0, Math.min(10.0, parseFloat(this.selectedRpe) || 8.0));

            this.rir = Math.max(0, 10.0 - this.selectedRpe);
            this.equivalentReps = this.targetReps + this.rir;

            this.intensityPercent = this.getPercentageForReps(this.equivalentReps);
            this.targetWeight = Math.round((this.oneRm * this.intensityPercent) * 2.0) / 2.0; // round to nearest 0.5kg
            this.perSideWeight = Math.max(0, Math.round(((this.targetWeight - 20) / 2.0) * 2.0) / 2.0);
        },

        getPercentageForReps(reps) {
            const intReps = Math.floor(reps);
            const frac = reps - intReps;

            const p1 = this.getNscaPct(intReps);
            const p2 = this.getNscaPct(intReps + 1);

            return p1 - (frac * (p1 - p2));
        },

        getNscaPct(reps) {
            if (this.nsca[reps]) return parseFloat(this.nsca[reps]);
            // Epley formula fallback: 1 / (1 + reps/30)
            return 1.0 / (1.0 + (reps / 30.0));
        },

        getMatrixWeight(reps, rpe) {
            const eq = reps + (10.0 - rpe);
            const pct = this.getPercentageForReps(eq);
            const w = Math.round((this.oneRm * pct) * 2.0) / 2.0;
            return w.toFixed(1);
        },

        getCellStyle(reps, rpe) {
            const isSelected = (this.targetReps === reps && Math.abs(this.selectedRpe - rpe) < 0.1);
            if (isSelected) {
                return 'bg-white text-slate-950 border-white shadow-lg shadow-white/20 ring-2 ring-indigo-400';
            }
            if (rpe >= 9.5) {
                return 'bg-rose-500/10 text-rose-300 border-rose-500/20 hover:border-rose-500/50';
            }
            if (rpe >= 8.5) {
                return 'bg-amber-500/10 text-amber-300 border-amber-500/20 hover:border-amber-500/50';
            }
            if (rpe >= 7.5) {
                return 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20 hover:border-emerald-500/50';
            }
            return 'bg-indigo-500/10 text-indigo-300 border-indigo-500/20 hover:border-indigo-500/50';
        },

        selectCell(reps, rpe) {
            this.targetReps = reps;
            this.selectedRpe = rpe;
            this.calculate();
            this.saveLocal();
        },

        saveLocal() {
            localStorage.setItem('hipertrofi_rpe_1rm', this.oneRm);
            localStorage.setItem('hipertrofi_rpe_reps', this.targetReps);
            localStorage.setItem('hipertrofi_rpe_val', this.selectedRpe);
        },

        get intensityClass() {
            if (this.selectedRpe >= 9.5) return 'text-rose-400';
            if (this.selectedRpe >= 8.5) return 'text-amber-400';
            if (this.selectedRpe >= 7.5) return 'text-emerald-400';
            return 'text-indigo-400';
        },

        get intensityBadgeClass() {
            if (this.selectedRpe >= 9.5) return 'bg-rose-500/15 border border-rose-500/30 text-rose-300';
            if (this.selectedRpe >= 8.5) return 'bg-amber-500/15 border border-amber-500/30 text-amber-300';
            if (this.selectedRpe >= 7.5) return 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-300';
            return 'bg-indigo-500/15 border border-indigo-500/30 text-indigo-300';
        },

        get intensityDesc() {
            if (this.selectedRpe >= 10.0) return 'Tükeniş (Maksimum)';
            if (this.selectedRpe >= 9.0) return 'Çok Ağır Şiddet';
            if (this.selectedRpe >= 8.0) return 'İdeal Hipertrofi';
            if (this.selectedRpe >= 7.0) return 'Orta / Hacim';
            return 'Hafif / Hız';
        },

        get restRecommendation() {
            if (this.selectedRpe >= 9.0) return '2.5 — 3.5 Dakika';
            if (this.selectedRpe >= 8.0) return '2.0 — 2.5 Dakika';
            return '1.5 — 2.0 Dakika';
        }
    };
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
