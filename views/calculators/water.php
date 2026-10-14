<?php
declare(strict_types=1);
require_once __DIR__ . '/../layouts/header.php';
$defaultWeight = isset($user['current_weight_kg']) && (float)$user['current_weight_kg'] > 0 
    ? (float)$user['current_weight_kg'] 
    : 75.0;
?>

<div class="max-w-4xl mx-auto space-y-6" x-data="waterCalculator(<?= $defaultWeight ?>)">
    <?php $activeTab = 'water'; require __DIR__ . '/_tabs.php'; ?>

    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 bg-slate-900 p-6 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center border border-cyan-500/30">
                    <i class="fa-solid fa-droplet text-lg"></i>
                </div>
                <h1 class="text-2xl font-black font-display text-white tracking-tight">Günlük Su İhtiyacı Hesaplayıcısı</h1>
            </div>
            <p class="text-sm text-slate-400 max-w-xl">Vücut ağırlığınız, günlük antrenman süreniz, iklim ve kreatin kullanımınıza göre bilimsel optimal hidrasyon hedefinizi hesaplayın.</p>
        </div>
        <div class="relative z-10 flex items-center gap-2">
            <span class="text-xs font-bold px-3 py-1.5 rounded-xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-300">
                <i class="fa-solid fa-flask text-[11px] mr-1"></i> ACSM & ISSN Standartları
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Input Controls Area -->
        <div class="lg:col-span-5 bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-5">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3 flex items-center gap-2">
                <i class="fa-solid fa-sliders text-cyan-400 text-sm"></i> Parametrelerinizi Belirleyin
            </h2>

            <!-- Vücut Ağırlığı -->
            <div>
                <div class="flex justify-between items-center mb-1.5">
                    <label for="weight_input" class="text-xs font-bold text-slate-300 uppercase tracking-wider">Vücut Ağırlığı (kg)</label>
                    <span class="text-xs font-mono font-bold text-cyan-400" x-text="weight + ' kg'"></span>
                </div>
                <div class="relative">
                    <input 
                        type="number" 
                        id="weight_input" 
                        x-model.number="weight" 
                        min="35" 
                        max="220" 
                        step="0.5" 
                        class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-cyan-500 transition-colors text-base font-semibold"
                        @input="calculate(); saveLocal();"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-xs text-slate-500">kg</div>
                </div>
                <input 
                    type="range" 
                    x-model.number="weight" 
                    min="40" 
                    max="140" 
                    step="0.5" 
                    class="w-full accent-cyan-500 mt-2 cursor-pointer"
                    @input="calculate(); saveLocal();"
                >
            </div>

            <!-- Antrenman Süresi -->
            <div>
                <div class="flex justify-between items-center mb-1.5">
                    <label for="minutes_input" class="text-xs font-bold text-slate-300 uppercase tracking-wider">Günlük Egzersiz Süresi</label>
                    <span class="text-xs font-mono font-bold text-cyan-400" x-text="trainingMinutes + ' dakika'"></span>
                </div>
                <div class="relative">
                    <input 
                        type="number" 
                        id="minutes_input" 
                        x-model.number="trainingMinutes" 
                        min="0" 
                        max="240" 
                        step="5" 
                        class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-cyan-500 transition-colors text-base font-semibold"
                        @input="calculate(); saveLocal();"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-xs text-slate-500">dk</div>
                </div>
                <input 
                    type="range" 
                    x-model.number="trainingMinutes" 
                    min="0" 
                    max="120" 
                    step="5" 
                    class="w-full accent-cyan-500 mt-2 cursor-pointer"
                    @input="calculate(); saveLocal();"
                >
            </div>

            <!-- İklim & Çevre -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">İklim & Hava Sıcaklığı</label>
                <div class="grid grid-cols-3 gap-2">
                    <button 
                        type="button" 
                        @click="climate = 'temperate'; calculate(); saveLocal();" 
                        class="p-2.5 rounded-xl border text-center transition-all flex flex-col items-center gap-1"
                        :class="climate === 'temperate' ? 'border-cyan-500 bg-cyan-500/15 text-cyan-300' : 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'">
                        <i class="fa-solid fa-cloud-sun text-sm"></i>
                        <span class="text-[11px] font-bold">Ilıman</span>
                        <span class="text-[9px] text-slate-500">+0.0L</span>
                    </button>
                    <button 
                        type="button" 
                        @click="climate = 'warm'; calculate(); saveLocal();" 
                        class="p-2.5 rounded-xl border text-center transition-all flex flex-col items-center gap-1"
                        :class="climate === 'warm' ? 'border-amber-500 bg-amber-500/15 text-amber-300' : 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'">
                        <i class="fa-solid fa-sun text-sm"></i>
                        <span class="text-[11px] font-bold">Sıcak</span>
                        <span class="text-[9px] text-slate-500">+0.4L</span>
                    </button>
                    <button 
                        type="button" 
                        @click="climate = 'hot'; calculate(); saveLocal();" 
                        class="p-2.5 rounded-xl border text-center transition-all flex flex-col items-center gap-1"
                        :class="climate === 'hot' ? 'border-rose-500 bg-rose-500/15 text-rose-300' : 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'">
                        <i class="fa-solid fa-temperature-arrow-up text-sm"></i>
                        <span class="text-[11px] font-bold">Çok Sıcak</span>
                        <span class="text-[9px] text-slate-500">+0.8L</span>
                    </button>
                </div>
            </div>

            <!-- Kreatin Kullanımı -->
            <div class="pt-2">
                <label class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-950 border border-slate-800 cursor-pointer hover:border-slate-700 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/15 text-indigo-400 flex items-center justify-center border border-indigo-500/20">
                            <i class="fa-solid fa-dna text-xs"></i>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-white block">Kreatin Monohidrat Kullanıyorum</span>
                            <span class="text-[10px] text-slate-400">Kas içi su tutulumu için ilave +500 ml hidrasyon önerilir</span>
                        </div>
                    </div>
                    <input 
                        type="checkbox" 
                        x-model="creatine" 
                        class="w-5 h-5 rounded border-slate-700 text-cyan-600 focus:ring-cyan-500 bg-slate-900 cursor-pointer"
                        @change="calculate(); saveLocal();"
                    >
                </label>
            </div>
        </div>

        <!-- Result Displays Area -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Hero Result Card -->
            <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-cyan-950/40 border border-cyan-500/30 rounded-3xl p-6 shadow-2xl relative overflow-hidden">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                    <div class="space-y-2 text-center sm:text-left">
                        <span class="text-xs font-bold uppercase tracking-wider text-cyan-400 block">Günlük Hedef Hidrasyon</span>
                        <div class="flex items-baseline gap-2 justify-center sm:justify-start">
                            <span class="text-5xl font-black font-display text-white tracking-tight" x-text="totalLiters.toFixed(2)"></span>
                            <span class="text-2xl font-bold text-cyan-400 font-display">Litre</span>
                        </div>
                        <div class="flex items-center gap-3 justify-center sm:justify-start pt-1">
                            <span class="text-xs font-mono font-bold px-3 py-1 rounded-lg bg-slate-950 border border-slate-800 text-slate-300" x-text="totalMl + ' ml'"></span>
                            <span class="text-xs font-mono font-bold px-3 py-1 rounded-lg bg-cyan-500/10 border border-cyan-500/30 text-cyan-300" x-text="'≈ ' + glasses + ' Bardak (250ml)'"></span>
                        </div>
                    </div>

                    <!-- Water Bottle Graphic -->
                    <div class="relative w-24 h-40 bg-slate-950 border-2 border-cyan-500/40 rounded-3xl p-1.5 flex flex-col justify-end overflow-hidden shadow-inner">
                        <div class="absolute top-1 left-1/2 -translate-x-1/2 w-8 h-2 bg-slate-800 rounded-t-sm border border-slate-700"></div>
                        <div 
                            class="w-full bg-gradient-to-t from-cyan-600 to-cyan-400 rounded-2xl transition-all duration-500 flex items-center justify-center relative"
                            :style="'height: ' + Math.min(100, Math.max(25, (totalLiters / 5.0) * 100)) + '%'">
                            <span class="text-[10px] font-black text-slate-950 font-mono tracking-tighter" x-text="totalLiters.toFixed(1) + 'L'"></span>
                        </div>
                    </div>
                </div>

                <!-- Breakdown Bars -->
                <div class="mt-6 pt-5 border-t border-slate-800/80 grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                    <div class="bg-slate-950/70 p-2.5 rounded-xl border border-slate-800/80">
                        <span class="text-[10px] text-slate-400 block">Temel Biyometrik</span>
                        <span class="text-xs font-bold text-white font-mono" x-text="baseWater.toFixed(2) + ' L'"></span>
                    </div>
                    <div class="bg-slate-950/70 p-2.5 rounded-xl border border-slate-800/80">
                        <span class="text-[10px] text-slate-400 block">Egzersiz / Ter</span>
                        <span class="text-xs font-bold text-cyan-300 font-mono" x-text="'+' + trainingWater.toFixed(2) + ' L'"></span>
                    </div>
                    <div class="bg-slate-950/70 p-2.5 rounded-xl border border-slate-800/80">
                        <span class="text-[10px] text-slate-400 block">İklim Faktörü</span>
                        <span class="text-xs font-bold text-amber-300 font-mono" x-text="'+' + climateWater.toFixed(2) + ' L'"></span>
                    </div>
                    <div class="bg-slate-950/70 p-2.5 rounded-xl border border-slate-800/80">
                        <span class="text-[10px] text-slate-400 block">Kreatin Desteği</span>
                        <span class="text-xs font-bold text-indigo-300 font-mono" x-text="'+' + (creatine ? '0.50' : '0.00') + ' L'"></span>
                    </div>
                </div>
            </div>

            <!-- Hydration Timeline Schedule -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <h3 class="text-sm font-bold text-white flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-clock text-cyan-400"></i> Gün İçi Zaman Dilimli Tüketim Planı
                    </span>
                    <span class="text-[10px] text-slate-400">Tek seferde değil, periyodik yudumlayın</span>
                </h3>

                <div class="space-y-2.5">
                    <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-950/70 border border-slate-800/80 text-xs">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-cyan-500/10 text-cyan-400 flex items-center justify-center font-bold font-mono text-[11px]">07:30</span>
                            <div>
                                <span class="font-bold text-white block">Uyanış Hidrasyonu</span>
                                <span class="text-[11px] text-slate-400">Gece boyunca kaybedilen sıvıyı yerine koymak için</span>
                            </div>
                        </div>
                        <span class="font-mono font-bold text-cyan-300">500 ml (2 Bardak)</span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-950/70 border border-slate-800/80 text-xs">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-bold font-mono text-[11px]">11:00</span>
                            <div>
                                <span class="font-bold text-white block">Öğle Öncesi Zindelik</span>
                                <span class="text-[11px] text-slate-400">Metabolizma hızını ve odaklanmayı canlı tutar</span>
                            </div>
                        </div>
                        <span class="font-mono font-bold text-indigo-300">500 ml (2 Bardak)</span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-950/70 border border-slate-800/80 text-xs">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-400 flex items-center justify-center font-bold font-mono text-[11px]">İDMAN</span>
                            <div>
                                <span class="font-bold text-white block">Antrenman Esnası & Sonrası</span>
                                <span class="text-[11px] text-slate-400">Set aralarında yudum yudum + toparlanma</span>
                            </div>
                        </div>
                        <span class="font-mono font-bold text-rose-300" x-text="Math.round(trainingWater * 1000 + 350) + ' ml'"></span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-950/70 border border-slate-800/80 text-xs">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-slate-800 text-slate-300 flex items-center justify-center font-bold font-mono text-[11px]">AKŞAM</span>
                            <div>
                                <span class="font-bold text-white block">Akşam Yemeği ve Gece</span>
                                <span class="text-[11px] text-slate-400">Uykudan 1 saat önce kesilecek şekilde dengeli</span>
                            </div>
                        </div>
                        <span class="font-mono font-bold text-slate-300" x-text="Math.max(400, totalMl - (1000 + Math.round(trainingWater * 1000 + 350))) + ' ml'"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function waterCalculator(initialWeight) {
    return {
        weight: parseFloat(localStorage.getItem('hipertrofi_water_weight')) || initialWeight || 75.0,
        trainingMinutes: parseInt(localStorage.getItem('hipertrofi_water_mins')) || 45,
        climate: localStorage.getItem('hipertrofi_water_climate') || 'temperate',
        creatine: localStorage.getItem('hipertrofi_water_creatine') === 'true',

        baseWater: 0,
        trainingWater: 0,
        climateWater: 0,
        totalLiters: 0,
        totalMl: 0,
        glasses: 0,

        init() {
            this.calculate();
        },

        calculate() {
            const w = Math.max(30, parseFloat(this.weight) || 75.0);
            const m = Math.max(0, parseInt(this.trainingMinutes) || 0);

            // Base formula: 0.035L per kg
            this.baseWater = w * 0.035;

            // Training sweat loss: 0.35L per 30 minutes
            this.trainingWater = (m / 30.0) * 0.35;

            // Climate factor
            this.climateWater = (this.climate === 'warm') ? 0.4 : (this.climate === 'hot' ? 0.8 : 0.0);

            // Creatine addition
            const creatineWater = this.creatine ? 0.5 : 0.0;

            this.totalLiters = this.baseWater + this.trainingWater + this.climateWater + creatineWater;
            this.totalMl = Math.round(this.totalLiters * 1000);
            this.glasses = Math.ceil(this.totalMl / 250);
        },

        saveLocal() {
            localStorage.setItem('hipertrofi_water_weight', this.weight);
            localStorage.setItem('hipertrofi_water_mins', this.trainingMinutes);
            localStorage.setItem('hipertrofi_water_climate', this.climate);
            localStorage.setItem('hipertrofi_water_creatine', this.creatine ? 'true' : 'false');
        }
    };
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
