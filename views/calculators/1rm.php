<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-4xl mx-auto space-y-6" x-data="calculator1RM()">
    <?php $activeTab = '1rm'; require __DIR__ . '/_tabs.php'; ?>
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white mb-1">1RM Hesaplayıcı</h1>
            <p class="text-slate-400 text-sm">Epley ve Brzycki formülleri ile 1 Tekrar Maksimumunuzu hesaplayın.</p>
        </div>
        <div class="w-12 h-12 rounded-xl bg-brand-500/10 border border-brand-500/20 flex items-center justify-center text-brand-400">
            <i class="fa-solid fa-calculator text-xl"></i>
        </div>
    </div>

    <!-- Input Card -->
    <div class="bg-slate-800 rounded-2xl border border-slate-700 p-5 sm:p-6 mb-6 shadow-xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Weight Input -->
            <div>
                <label for="weight" class="block text-sm font-medium text-slate-300 mb-2">Kaldırılan Ağırlık (kg)</label>
                <div class="relative">
                    <input
                        type="number"
                        id="weight"
                        x-model.number="weight"
                        @input="calculate()"
                        class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors text-lg"
                        placeholder="Örn: 80"
                        min="1"
                        step="0.5"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <span class="text-slate-500 font-medium">kg</span>
                    </div>
                </div>
            </div>

            <!-- Reps Input -->
            <div>
                <label for="reps" class="block text-sm font-medium text-slate-300 mb-2">Tekrar Sayısı</label>
                <div class="relative">
                    <input
                        type="number"
                        id="reps"
                        x-model.number="reps"
                        @input="calculate()"
                        class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors text-lg"
                        placeholder="Örn: 5"
                        min="1"
                        max="30"
                        step="1"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <span class="text-slate-500 font-medium">tekrar</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
             <button @click="reset()" class="px-4 py-2 text-sm font-medium text-slate-400 hover:text-white transition-colors">
                <i class="fa-solid fa-arrow-rotate-right mr-1.5"></i> Sıfırla
            </button>
        </div>
    </div>

    <!-- Results Section -->
    <div x-show="hasValidInput()" x-transition.opacity class="space-y-6">

        <!-- Main Result Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Epley Result -->
            <div class="bg-slate-800/50 rounded-2xl border border-slate-700/50 p-4 text-center">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Epley Formülü</div>
                <div class="text-2xl font-bold text-white"><span x-text="epleyMax"></span> <span class="text-sm font-medium text-slate-500">kg</span></div>
            </div>

            <!-- Average Result (Highlighted) -->
            <div class="bg-gradient-to-br from-brand-600 to-indigo-600 rounded-2xl border border-brand-500 p-5 text-center shadow-lg shadow-brand-500/20 transform scale-105 z-10">
                <div class="text-xs font-semibold text-brand-200 uppercase tracking-wider mb-1">Ortalama 1RM</div>
                <div class="text-4xl font-black text-white tracking-tight"><span x-text="averageMax"></span> <span class="text-lg font-medium text-brand-300">kg</span></div>
            </div>

            <!-- Brzycki Result -->
            <div class="bg-slate-800/50 rounded-2xl border border-slate-700/50 p-4 text-center">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Brzycki Formülü</div>
                <div class="text-2xl font-bold text-white"><span x-text="brzyckiMax"></span> <span class="text-sm font-medium text-slate-500">kg</span></div>
            </div>
        </div>

        <!-- Percentages Table -->
        <div class="bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden shadow-xl">
            <div class="px-5 py-4 border-b border-slate-700 bg-slate-800/50 flex justify-between items-center">
                <h3 class="font-semibold text-white">Yüzde Dağılımı (Ortalama 1RM)</h3>
                <i class="fa-solid fa-chart-pie text-slate-500"></i>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-900/50 text-slate-400">
                        <tr>
                            <th class="px-5 py-3 font-medium">Yüzde (%)</th>
                            <th class="px-5 py-3 font-medium text-right">Ağırlık (kg)</th>
                            <th class="px-5 py-3 font-medium">Hedef (Reps)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        <template x-for="pct in percentages" :key="pct.val">
                            <tr class="hover:bg-slate-700/30 transition-colors">
                                <td class="px-5 py-3 font-medium text-brand-400" x-text="pct.val + '%'"></td>
                                <td class="px-5 py-3 font-bold text-white text-right" x-text="calculatePercentage(pct.val)"></td>
                                <td class="px-5 py-3 text-slate-400">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-900 text-slate-300" x-text="pct.reps"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-blue-900/20 border border-blue-500/20 rounded-xl p-4 flex gap-3 text-sm text-blue-200">
            <i class="fa-solid fa-circle-info mt-0.5 text-blue-400"></i>
            <p>1RM (One Rep Max), tek seferde kaldırabileceğiniz maksimum ağırlığı ifade eder. Bu hesaplamalar tahmini değerlerdir ve özellikle 10 tekrarın üzerindeki girişlerde sapma gösterebilir.</p>
        </div>

    </div>

    <!-- Empty State -->
    <div x-show="!hasValidInput()" class="text-center py-12 px-4 border-2 border-dashed border-slate-700 rounded-2xl">
        <div class="w-16 h-16 rounded-full bg-slate-800 flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-dumbbell text-2xl text-slate-500"></i>
        </div>
        <h3 class="text-lg font-medium text-white mb-2">Değerleri Girin</h3>
        <p class="text-slate-400 max-w-sm mx-auto">1RM ve yüzde dağılımlarını görmek için yukarıdaki alana kaldırdığınız ağırlığı ve tekrar sayısını girin.</p>
    </div>

</div>

<script>
    function calculator1RM() {
        return {
            weight: null,
            reps: null,
            epleyMax: 0,
            brzyckiMax: 0,
            averageMax: 0,
            percentages: [
                { val: 100, reps: '1 Rep' },
                { val: 95, reps: '2 Reps' },
                { val: 90, reps: '3-4 Reps' },
                { val: 85, reps: '5-6 Reps' },
                { val: 80, reps: '7-8 Reps' },
                { val: 75, reps: '9-10 Reps' },
                { val: 70, reps: '11-12 Reps' },
                { val: 65, reps: '13-14 Reps' },
                { val: 60, reps: '15+ Reps' },
                { val: 55, reps: '20+ Reps' },
                { val: 50, reps: '25+ Reps' }
            ],

            init() {
                // Load from localStorage if exists
                const savedData = localStorage.getItem('calc_1rm_data');
                if (savedData) {
                    try {
                        const parsed = JSON.parse(savedData);
                        if (parsed.weight && parsed.reps) {
                            this.weight = parsed.weight;
                            this.reps = parsed.reps;
                            this.calculate();
                        }
                    } catch (e) {
                        console.error('Error parsing localStorage for 1rm calc');
                    }
                }
            },

            hasValidInput() {
                return this.weight > 0 && this.reps > 0;
            },

            calculate() {
                if (!this.hasValidInput()) {
                    this.epleyMax = 0;
                    this.brzyckiMax = 0;
                    this.averageMax = 0;
                    return;
                }

                let w = parseFloat(this.weight);
                let r = parseInt(this.reps, 10);

                if (r === 1) {
                    this.epleyMax = w.toFixed(1);
                    this.brzyckiMax = w.toFixed(1);
                    this.averageMax = w.toFixed(1);
                } else {
                    // Epley Formula: W * (1 + R/30)
                    let epley = w * (1 + r / 30);

                    // Brzycki Formula: W * (36 / (37 - R))
                    let brzycki = w * (36 / (37 - r));

                    this.epleyMax = epley.toFixed(1);
                    this.brzyckiMax = brzycki.toFixed(1);
                    this.averageMax = ((epley + brzycki) / 2).toFixed(1);
                }

                // Save to localStorage
                localStorage.setItem('calc_1rm_data', JSON.stringify({
                    weight: w,
                    reps: r
                }));
            },

            calculatePercentage(percent) {
                if (!this.averageMax) return '0.0';
                return (parseFloat(this.averageMax) * (percent / 100)).toFixed(1);
            },

            reset() {
                this.weight = null;
                this.reps = null;
                this.calculate();
                localStorage.removeItem('calc_1rm_data');
            }
        }
    }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
