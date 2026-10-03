<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-4xl mx-auto space-y-6" x-data="tdeeCalculator()" x-init="initData()">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 bg-slate-900 p-6 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-brand-500/20 text-brand-400 flex items-center justify-center">
                    <i class="fa-solid fa-fire text-lg"></i>
                </div>
                <h1 class="text-2xl font-black font-display text-white tracking-tight">TDEE & Makro Hesaplayıcı</h1>
            </div>
            <p class="text-sm text-slate-400 max-w-xl">Mifflin-St Jeor formülü ile Günlük Kalori İhtiyacınızı (TDEE) ve hedefinize uygun makro besin dağılımını hesaplayın.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Input Form Area -->
        <div class="lg:col-span-5 bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl relative">
            <h2 class="text-lg font-bold text-white mb-6 border-b border-slate-800 pb-3">Biyometrik Verileriniz</h2>

            <form @submit.prevent="calculate" class="space-y-5">
                <!-- Cinsiyet -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Cinsiyet</label>
                    <div class="flex gap-4">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" x-model="gender" value="male" class="peer sr-only" @change="saveToLocalStorage">
                            <div class="p-3 rounded-xl border border-slate-700 text-center font-medium text-slate-400 peer-checked:border-brand-500 peer-checked:text-brand-400 peer-checked:bg-brand-500/10 transition-all">
                                <i class="fa-solid fa-mars mr-1"></i> Erkek
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" x-model="gender" value="female" class="peer sr-only" @change="saveToLocalStorage">
                            <div class="p-3 rounded-xl border border-slate-700 text-center font-medium text-slate-400 peer-checked:border-brand-500 peer-checked:text-brand-400 peer-checked:bg-brand-500/10 transition-all">
                                <i class="fa-solid fa-venus mr-1"></i> Kadın
                            </div>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Yaş -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Yaş</label>
                        <input type="number" x-model.number="age" min="15" max="100" class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-brand-500 transition-colors" @input="saveToLocalStorage">
                    </div>
                    <!-- Boy -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Boy (cm)</label>
                        <input type="number" x-model.number="height" min="100" max="250" class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-brand-500 transition-colors" @input="saveToLocalStorage">
                    </div>
                </div>

                <!-- Kilo -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Kilo (kg)</label>
                    <input type="number" x-model.number="weight" min="30" max="250" step="0.1" class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-brand-500 transition-colors" @input="saveToLocalStorage">
                </div>

                <!-- Aktivite Seviyesi -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Aktivite Seviyesi</label>
                    <select x-model.number="activityMultiplier" class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-brand-500 transition-colors appearance-none" @change="saveToLocalStorage">
                        <option value="1.2">Masa Başı / Hareketsiz (Sedentary)</option>
                        <option value="1.375">Hafif Aktif (Haftada 1-3 gün idman)</option>
                        <option value="1.55">Orta Aktif (Haftada 3-5 gün idman)</option>
                        <option value="1.725">Çok Aktif (Haftada 6-7 gün ağır idman)</option>
                        <option value="1.9">Ekstra Aktif (Günde 2 kez / Fiziksel İş)</option>
                    </select>
                </div>

                <!-- Hedef -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Fitness Hedefi</label>
                    <select x-model="goal" class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-brand-500 transition-colors appearance-none" @change="saveToLocalStorage">
                        <option value="cut">Defisit (Kilo Verme - %20 Azalt)</option>
                        <option value="maintain">Koruma (Mevcut Kiloyu Sürdürme)</option>
                        <option value="bulk">Bulk (Kas Kazanımı - %15 Artır)</option>
                    </select>
                </div>

                <button type="submit" class="w-full bg-brand-600 hover:bg-brand-500 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-brand-600/30 flex items-center justify-center gap-2 mt-4">
                    <i class="fa-solid fa-calculator"></i> Hesapla
                </button>
            </form>
        </div>

        <!-- Results Area -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Kalori Özeti Kartı -->
            <div x-show="calculated" x-transition class="bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl relative overflow-hidden" style="display: none;">
                <div class="absolute right-0 top-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <h3 class="text-lg font-bold text-white mb-6 border-b border-slate-700 pb-3">Günlük Enerji İhtiyacı</h3>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="bg-slate-950/50 p-4 rounded-2xl border border-slate-800 text-center">
                        <span class="block text-xs text-slate-400 font-bold uppercase mb-1">BMR (Bazal)</span>
                        <span class="text-2xl font-black text-white font-display" x-text="bmr + ' kcal'"></span>
                    </div>
                    <div class="bg-slate-950/50 p-4 rounded-2xl border border-slate-800 text-center relative overflow-hidden">
                        <div class="absolute inset-0 bg-brand-600/10"></div>
                        <span class="block text-xs text-brand-400 font-bold uppercase mb-1 relative z-10">TDEE (Koruma)</span>
                        <span class="text-2xl font-black text-brand-300 font-display relative z-10" x-text="tdee + ' kcal'"></span>
                    </div>
                </div>

                <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 text-center mt-6">
                    <span class="block text-sm text-slate-400 font-bold uppercase tracking-widest mb-2" x-text="goalTitle"></span>
                    <span class="text-5xl font-black text-white font-display" x-text="targetCalories + ' kcal'"></span>
                    <p class="text-xs text-slate-500 mt-3" x-text="goalDescription"></p>
                </div>
            </div>

            <!-- Makro Dağılımı Kartı -->
            <div x-show="calculated" x-transition class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl" style="display: none;">
                <h3 class="text-lg font-bold text-white mb-6 border-b border-slate-800 pb-3 flex items-center justify-between">
                    Hedefe Uygun Makro Dağılımı
                    <span class="text-xs font-medium text-slate-400 bg-slate-800 px-2 py-1 rounded-md">Protein x 2.2g</span>
                </h3>

                <div class="space-y-5">
                    <!-- Protein -->
                    <div>
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-sm font-bold text-rose-400"><i class="fa-solid fa-drumstick-bite mr-1.5"></i>Protein</span>
                            <span class="font-display font-black text-white" x-text="macros.protein + 'g'"></span>
                        </div>
                        <div class="w-full bg-slate-950 rounded-full h-3">
                            <div class="bg-rose-500 h-3 rounded-full" :style="'width: ' + macros.proteinPercent + '%'"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-500 mt-1 font-semibold uppercase">
                            <span x-text="macros.proteinCals + ' kcal'"></span>
                            <span x-text="'%' + macros.proteinPercent"></span>
                        </div>
                    </div>

                    <!-- Yağ -->
                    <div>
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-sm font-bold text-amber-400"><i class="fa-solid fa-cheese mr-1.5"></i>Yağ</span>
                            <span class="font-display font-black text-white" x-text="macros.fat + 'g'"></span>
                        </div>
                        <div class="w-full bg-slate-950 rounded-full h-3">
                            <div class="bg-amber-500 h-3 rounded-full" :style="'width: ' + macros.fatPercent + '%'"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-500 mt-1 font-semibold uppercase">
                            <span x-text="macros.fatCals + ' kcal'"></span>
                            <span x-text="'%' + macros.fatPercent"></span>
                        </div>
                    </div>

                    <!-- Karbonhidrat -->
                    <div>
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-sm font-bold text-blue-400"><i class="fa-solid fa-bowl-rice mr-1.5"></i>Karbonhidrat</span>
                            <span class="font-display font-black text-white" x-text="macros.carbs + 'g'"></span>
                        </div>
                        <div class="w-full bg-slate-950 rounded-full h-3">
                            <div class="bg-blue-500 h-3 rounded-full" :style="'width: ' + macros.carbsPercent + '%'"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-500 mt-1 font-semibold uppercase">
                            <span x-text="macros.carbCals + ' kcal'"></span>
                            <span x-text="'%' + macros.carbsPercent"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="!calculated" class="h-full flex flex-col items-center justify-center bg-slate-900 border border-slate-800 rounded-3xl p-10 text-center opacity-70">
                <i class="fa-solid fa-calculator text-4xl text-slate-700 mb-4"></i>
                <p class="text-slate-500 font-medium max-w-sm">Günlük kalori ve makro dağılımınızı görmek için sol taraftaki formu doldurun ve "Hesapla" butonuna tıklayın.</p>
            </div>
        </div>
    </div>
</div>

<script>
function tdeeCalculator() {
    return {
        gender: 'male',
        age: 25,
        height: 180,
        weight: 80,
        activityMultiplier: 1.55,
        goal: 'maintain',
        calculated: false,
        bmr: 0,
        tdee: 0,
        targetCalories: 0,
        macros: {
            protein: 0, proteinCals: 0, proteinPercent: 0,
            fat: 0, fatCals: 0, fatPercent: 0,
            carbs: 0, carbCals: 0, carbsPercent: 0
        },

        get goalTitle() {
            if (this.goal === 'cut') return 'Hedef: Kilo Verme (Defisit)';
            if (this.goal === 'bulk') return 'Hedef: Kas Kazanımı (Bulk)';
            return 'Hedef: Kiloyu Koruma';
        },

        get goalDescription() {
            if (this.goal === 'cut') return 'TDEE üzerinden %20 kalori açığı oluşturuldu. Sağlıklı bir şekilde yağ yakmak için idealdir.';
            if (this.goal === 'bulk') return 'TDEE üzerine %15 kalori fazlası eklendi. Hipertrofi odaklı antrenmanlar için uygundur.';
            return 'Mevcut kilonuzu korumak ve antrenman performansınızı desteklemek için gereken kalori miktarı.';
        },

        initData() {
            const saved = localStorage.getItem('tdee_calculator_data');
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    this.gender = data.gender || 'male';
                    this.age = data.age || 25;
                    this.height = data.height || 180;
                    this.weight = data.weight || 80;
                    this.activityMultiplier = data.activityMultiplier || 1.55;
                    this.goal = data.goal || 'maintain';
                } catch(e) {}
            }
        },

        saveToLocalStorage() {
            const data = {
                gender: this.gender,
                age: this.age,
                height: this.height,
                weight: this.weight,
                activityMultiplier: this.activityMultiplier,
                goal: this.goal
            };
            localStorage.setItem('tdee_calculator_data', JSON.stringify(data));
        },

        calculate() {
            this.saveToLocalStorage();

            // Mifflin-St Jeor Equation
            // Erkek: 10 x weight(kg) + 6.25 x height(cm) - 5 x age(y) + 5
            // Kadın: 10 x weight(kg) + 6.25 x height(cm) - 5 x age(y) - 161
            let bmrCalc = (10 * this.weight) + (6.25 * this.height) - (5 * this.age);
            if (this.gender === 'male') {
                bmrCalc += 5;
            } else {
                bmrCalc -= 161;
            }

            this.bmr = Math.round(bmrCalc);

            // TDEE = BMR * Activity Level
            this.tdee = Math.round(this.bmr * this.activityMultiplier);

            // Apply Goals
            if (this.goal === 'cut') {
                this.targetCalories = Math.round(this.tdee * 0.80);
            } else if (this.goal === 'bulk') {
                this.targetCalories = Math.round(this.tdee * 1.15);
            } else {
                this.targetCalories = this.tdee;
            }

            // Macro Calculation Strategy (Evidence-based hypertrophy)
            // Protein: 2.2g per kg of body weight
            // Fat: 25% of total target calories
            // Carbs: Remainder of calories

            let pGrams = this.weight * 2.2;
            if (this.goal === 'cut') pGrams = this.weight * 2.4; // Higher protein in deficit

            this.macros.protein = Math.round(pGrams);
            this.macros.proteinCals = this.macros.protein * 4;

            this.macros.fatCals = Math.round(this.targetCalories * 0.25);
            this.macros.fat = Math.round(this.macros.fatCals / 9);

            this.macros.carbCals = this.targetCalories - (this.macros.proteinCals + this.macros.fatCals);
            if (this.macros.carbCals < 0) this.macros.carbCals = 0;
            this.macros.carbs = Math.round(this.macros.carbCals / 4);

            // Percentages
            this.macros.proteinPercent = Math.round((this.macros.proteinCals / this.targetCalories) * 100);
            this.macros.fatPercent = Math.round((this.macros.fatCals / this.targetCalories) * 100);
            this.macros.carbsPercent = 100 - (this.macros.proteinPercent + this.macros.fatPercent);

            this.calculated = true;
        }
    }
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
