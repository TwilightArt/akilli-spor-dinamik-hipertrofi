<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-4xl mx-auto" x-data="tdeeCalculator()">

    <div class="mb-6">
        <h1 class="text-2xl font-black font-display text-white tracking-tight flex items-center gap-2">
            <i class="fa-solid fa-calculator text-blue-400"></i> TDEE & Makro Besin Hesaplayıcı
        </h1>
        <p class="text-sm text-slate-400 mt-1">Günlük kalori ihtiyacınızı (TDEE) ve hedefinize uygun makro dağılımını hesaplayın.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Form Section -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
                <h2 class="text-lg font-bold text-white mb-4 border-b border-slate-800 pb-2">Kişisel Bilgiler</h2>

                <form @submit.prevent="calculate" class="space-y-4">

                    <!-- Gender -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">Cinsiyet</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer">
                                <input type="radio" x-model="form.gender" value="male" class="peer sr-only">
                                <div class="px-4 py-2.5 rounded-xl border border-slate-700 bg-slate-800 text-slate-300 text-sm font-medium text-center peer-checked:border-blue-500 peer-checked:bg-blue-500/10 peer-checked:text-blue-400 transition-all">
                                    <i class="fa-solid fa-mars mr-1"></i> Erkek
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" x-model="form.gender" value="female" class="peer sr-only">
                                <div class="px-4 py-2.5 rounded-xl border border-slate-700 bg-slate-800 text-slate-300 text-sm font-medium text-center peer-checked:border-pink-500 peer-checked:bg-pink-500/10 peer-checked:text-pink-400 transition-all">
                                    <i class="fa-solid fa-venus mr-1"></i> Kadın
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Age -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1.5">Yaş</label>
                            <div class="relative">
                                <input type="number" x-model.number="form.age" min="15" max="100" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all outline-none" required>
                                <span class="absolute right-4 top-2.5 text-slate-500 text-sm">yıl</span>
                            </div>
                        </div>

                        <!-- Height -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1.5">Boy</label>
                            <div class="relative">
                                <input type="number" x-model.number="form.height" min="100" max="250" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all outline-none" required>
                                <span class="absolute right-4 top-2.5 text-slate-500 text-sm">cm</span>
                            </div>
                        </div>
                    </div>

                    <!-- Weight -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">Kilo</label>
                        <div class="relative">
                            <input type="number" x-model.number="form.weight" min="30" max="250" step="0.1" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all outline-none" required>
                            <span class="absolute right-4 top-2.5 text-slate-500 text-sm">kg</span>
                        </div>
                    </div>

                    <!-- Activity Level -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">Aktivite Seviyesi</label>
                        <select x-model.number="form.activityMultiplier" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all outline-none appearance-none">
                            <option value="1.2">Sedanter (Masa başı iş, az/hiç egzersiz)</option>
                            <option value="1.375">Hafif Aktif (Haftada 1-3 gün hafif egzersiz)</option>
                            <option value="1.55">Orta Aktif (Haftada 3-5 gün orta egzersiz)</option>
                            <option value="1.725">Çok Aktif (Haftada 6-7 gün ağır egzersiz)</option>
                            <option value="1.9">Ekstra Aktif (Ağır fiziksel iş / çift antrenman)</option>
                        </select>
                    </div>

                    <!-- Goal -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">Hedef (Makrolar İçin)</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" x-model="form.goal" value="cut" class="peer sr-only">
                                <div class="px-2 py-2 rounded-xl border border-slate-700 bg-slate-800 text-slate-300 text-xs font-medium text-center peer-checked:border-rose-500 peer-checked:bg-rose-500/10 peer-checked:text-rose-400 transition-all">
                                    Defisit<br><span class="text-[9px] opacity-70">(-20%)</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" x-model="form.goal" value="maintain" class="peer sr-only">
                                <div class="px-2 py-2 rounded-xl border border-slate-700 bg-slate-800 text-slate-300 text-xs font-medium text-center peer-checked:border-emerald-500 peer-checked:bg-emerald-500/10 peer-checked:text-emerald-400 transition-all">
                                    Koruma<br><span class="text-[9px] opacity-70">(0%)</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" x-model="form.goal" value="bulk" class="peer sr-only">
                                <div class="px-2 py-2 rounded-xl border border-slate-700 bg-slate-800 text-slate-300 text-xs font-medium text-center peer-checked:border-brand-500 peer-checked:bg-brand-500/10 peer-checked:text-brand-400 transition-all">
                                    Bulk<br><span class="text-[9px] opacity-70">(+15%)</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="w-full mt-2 bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl shadow-lg shadow-blue-500/20 transition-all active:scale-[0.98] flex items-center justify-center gap-2">
                        <i class="fa-solid fa-calculator"></i> Hesapla
                    </button>
                </form>
            </div>
        </div>

        <!-- Results Section -->
        <div class="lg:col-span-7 space-y-6" x-show="hasCalculated" x-transition.opacity style="display: none;">

            <!-- Calories Overview -->
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-800 rounded-2xl p-6 shadow-xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <i class="fa-solid fa-fire text-6xl text-white"></i>
                </div>

                <div class="grid grid-cols-2 gap-6 relative z-10">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Bazal Metabolizma (BMR)</span>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-black text-white" x-text="results.bmr"></span>
                            <span class="text-sm text-slate-400">kcal</span>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1">Sadece hayatta kalmak için harcanan enerji.</p>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Günlük İhtiyaç (TDEE)</span>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-black text-blue-400" x-text="results.tdee"></span>
                            <span class="text-sm text-slate-400">kcal</span>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1">Aktivite seviyeniz dahil harcanan enerji.</p>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-slate-700/50">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-2 text-center" x-text="goalTitle"></span>
                    <div class="text-center">
                        <span class="text-5xl font-black font-display text-white tracking-tight" x-text="results.targetCalories"></span>
                        <span class="text-lg text-slate-400 ml-1">kcal / gün</span>
                    </div>
                </div>
            </div>

            <!-- Macros Breakdown -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
                <h3 class="text-sm font-bold text-white mb-4 flex items-center justify-between">
                    <span>Önerilen Makro Dağılımı</span>
                    <span class="text-xs font-normal text-slate-400 bg-slate-800 px-2 py-0.5 rounded-full" x-text="results.targetCalories + ' kcal'"></span>
                </h3>

                <div class="space-y-4">
                    <!-- Protein -->
                    <div>
                        <div class="flex justify-between text-sm mb-1.5">
                            <span class="font-medium text-emerald-400"><i class="fa-solid fa-drumstick-bite mr-1"></i> Protein</span>
                            <span class="font-bold text-white" x-text="results.macros.protein + 'g'"></span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-2">
                            <div class="bg-emerald-500 h-2 rounded-full" :style="'width: ' + results.macros.proteinPct + '%'"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-500 mt-1">
                            <span>4 kcal / g</span>
                            <span x-text="results.macros.proteinPct + '%'"></span>
                        </div>
                    </div>

                    <!-- Yağ -->
                    <div>
                        <div class="flex justify-between text-sm mb-1.5">
                            <span class="font-medium text-amber-400"><i class="fa-solid fa-cheese mr-1"></i> Yağ</span>
                            <span class="font-bold text-white" x-text="results.macros.fat + 'g'"></span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-2">
                            <div class="bg-amber-500 h-2 rounded-full" :style="'width: ' + results.macros.fatPct + '%'"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-500 mt-1">
                            <span>9 kcal / g</span>
                            <span x-text="results.macros.fatPct + '%'"></span>
                        </div>
                    </div>

                    <!-- Karbonhidrat -->
                    <div>
                        <div class="flex justify-between text-sm mb-1.5">
                            <span class="font-medium text-blue-400"><i class="fa-solid fa-wheat-awn mr-1"></i> Karbonhidrat</span>
                            <span class="font-bold text-white" x-text="results.macros.carbs + 'g'"></span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" :style="'width: ' + results.macros.carbsPct + '%'"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-500 mt-1">
                            <span>4 kcal / g</span>
                            <span x-text="results.macros.carbsPct + '%'"></span>
                        </div>
                    </div>
                </div>

                <div class="mt-5 p-3 rounded-xl bg-slate-800/50 text-[11px] text-slate-400 border border-slate-700/50">
                    <i class="fa-solid fa-circle-info text-blue-400 mr-1"></i> Protein ihtiyacı vücut ağırlığına göre hesaplanır (~2g/kg). Kalan kaloriler yağ (genellikle toplam kalorinin %25-30'u) ve karbonhidratlara dağıtılır.
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('tdeeCalculator', () => ({
        hasCalculated: false,
        form: {
            gender: 'male',
            age: '',
            height: '',
            weight: '',
            activityMultiplier: 1.2,
            goal: 'maintain'
        },
        results: {
            bmr: 0,
            tdee: 0,
            targetCalories: 0,
            macros: {
                protein: 0,
                fat: 0,
                carbs: 0,
                proteinPct: 0,
                fatPct: 0,
                carbsPct: 0
            }
        },

        init() {
            // Load saved data from localStorage if exists
            const saved = localStorage.getItem('tdee_form_data');
            if (saved) {
                try {
                    this.form = { ...this.form, ...JSON.parse(saved) };
                    if (this.form.age && this.form.weight && this.form.height) {
                        this.calculate();
                    }
                } catch(e) {}
            } else {
                // Try to initialize from PHP user data if available
                <?php if ($currentUser ?? false): ?>
                const uWeight = <?= (float)($currentUser['weight_kg'] ?? 0) ?>;
                const uHeight = <?= (float)($currentUser['height_cm'] ?? 0) ?>;
                if(uWeight > 0) this.form.weight = uWeight;
                if(uHeight > 0) this.form.height = uHeight;
                <?php endif; ?>
            }
        },

        get goalTitle() {
            if (this.form.goal === 'cut') return 'Kilo Verme Hedefi (Defisit)';
            if (this.form.goal === 'bulk') return 'Kas Kazanım Hedefi (Bulk)';
            return 'Kilo Koruma Hedefi';
        },

        calculate() {
            const { gender, age, height, weight, activityMultiplier, goal } = this.form;

            if(!age || !height || !weight) return;

            // Save to localStorage
            localStorage.setItem('tdee_form_data', JSON.stringify(this.form));

            // Mifflin-St Jeor Equation
            // Men: (10 × weight in kg) + (6.25 × height in cm) - (5 × age in years) + 5
            // Women: (10 × weight in kg) + (6.25 × height in cm) - (5 × age in years) - 161

            let bmr = (10 * weight) + (6.25 * height) - (5 * age);
            bmr += (gender === 'male') ? 5 : -161;

            const tdee = bmr * activityMultiplier;

            let targetCalories = tdee;
            if (goal === 'cut') {
                targetCalories = tdee * 0.80; // 20% deficit
            } else if (goal === 'bulk') {
                targetCalories = tdee * 1.15; // 15% surplus
            }

            // Macros Logic
            // Protein: 2.2g per kg of bodyweight
            const protein = Math.round(weight * 2.2);
            const proteinCals = protein * 4;

            // Fat: 25% of target calories
            const fatCals = targetCalories * 0.25;
            const fat = Math.round(fatCals / 9);

            // Carbs: Remainder
            const carbCals = targetCalories - proteinCals - fatCals;
            const carbs = Math.round(carbCals / 4);

            // Update Results
            this.results.bmr = Math.round(bmr);
            this.results.tdee = Math.round(tdee);
            this.results.targetCalories = Math.round(targetCalories);

            this.results.macros.protein = protein;
            this.results.macros.fat = fat;
            this.results.macros.carbs = carbs > 0 ? carbs : 0;

            const totalCals = (protein*4) + (fat*9) + (carbs*4);
            this.results.macros.proteinPct = Math.round(((protein*4) / totalCals) * 100);
            this.results.macros.fatPct = Math.round(((fat*9) / totalCals) * 100);
            this.results.macros.carbsPct = Math.round(((carbs*4) / totalCals) * 100);

            this.hasCalculated = true;
        }
    }));
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
