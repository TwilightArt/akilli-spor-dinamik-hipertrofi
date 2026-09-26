<?php
$eq = $equipment ?? null;
$platesArr = $eq['plates_array'] ?? [
    '20kg'   => 2,
    '10kg'   => 4,
    '5kg'    => 4,
    '2.5kg'  => 4,
    '1.25kg' => 4,
    '0.5kg'  => 2
];
?>
<div class="max-w-3xl mx-auto py-4" x-data="{
    location: '<?= $eq['workout_location'] ?? 'home_weights' ?>',
    hasBarbell: <?= !empty($eq['has_barbell']) ? 'true' : 'true' ?>,
    hasDumbbells: <?= !empty($eq['has_dumbbells']) ? 'true' : 'true' ?>
}">
    <!-- Header Step Progress -->
    <div class="mb-8">
        <div class="flex items-center justify-between text-xs font-semibold text-slate-400 mb-2">
            <a href="/onboarding/assessment" class="text-slate-400 hover:text-white transition-colors">← Geri: Kuvvet Testi</a>
            <span class="text-brand-400 font-bold uppercase tracking-wider">Aşama 2 / 2: Donanım ve Plaka Envanteri</span>
        </div>
        <div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-brand-600 to-emerald-500 w-full rounded-full"></div>
        </div>
    </div>

    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl backdrop-blur-xl">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-dumbbell text-xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold font-display text-white">Ekipman & Plaka Envanteri</h1>
                <p class="text-xs text-slate-400 mt-1">PlateSolver motorumuz, set aralarında elinizde olmayan plakaları asla önermez; hedef ağırlığa en yakın çift kombinasyonu anında hesaplar.</p>
            </div>
        </div>

        <form action="/onboarding/equipment" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <!-- Location Selector -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-3">Antrenman Konumu / Ortamı</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="cursor-pointer border p-4 rounded-2xl flex flex-col gap-1 transition-all"
                        :class="location === 'commercial_gym' ? 'bg-brand-600/10 border-brand-500 text-white shadow-md' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700'">
                        <input type="radio" name="workout_location" value="commercial_gym" x-model="location" class="hidden">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm text-white">Spor Salonu</span>
                            <i class="fa-solid fa-building text-base text-brand-400"></i>
                        </div>
                        <span class="text-[11px] text-slate-400">Tüm makineler, kablolar ve barlar mevcut</span>
                    </label>

                    <label class="cursor-pointer border p-4 rounded-2xl flex flex-col gap-1 transition-all"
                        :class="location === 'home_weights' ? 'bg-brand-600/10 border-brand-500 text-white shadow-md' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700'">
                        <input type="radio" name="workout_location" value="home_weights" x-model="location" class="hidden">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm text-white">Evde Ağırlık</span>
                            <i class="fa-solid fa-house text-base text-brand-400"></i>
                        </div>
                        <span class="text-[11px] text-slate-400">Bar, ayarlanabilir dambıl ve döküm plakalar</span>
                    </label>

                    <label class="cursor-pointer border p-4 rounded-2xl flex flex-col gap-1 transition-all"
                        :class="location === 'bodyweight' ? 'bg-brand-600/10 border-brand-500 text-white shadow-md' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700'">
                        <input type="radio" name="workout_location" value="bodyweight" x-model="location" class="hidden">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm text-white">Vücut Ağırlığı</span>
                            <i class="fa-solid fa-person text-base text-brand-400"></i>
                        </div>
                        <span class="text-[11px] text-slate-400">Yalnızca kalistenik & kapı/barfiks alanı</span>
                    </label>
                </div>
            </div>

            <!-- Bar Hardware Settings -->
            <div x-show="location !== 'bodyweight'" class="p-5 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-4">
                <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-weight-hanging text-brand-400"></i> Bar Demirleri ve Taban Ağırlıkları
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Barbell -->
                    <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <input type="checkbox" id="has_barbell" name="has_barbell" value="1" x-model="hasBarbell"
                                class="w-4 h-4 rounded text-brand-600 bg-slate-800 border-slate-700 focus:ring-0">
                            <div>
                                <label for="has_barbell" class="text-xs font-semibold text-white block">Halter / Barbell</label>
                                <span class="text-[10px] text-slate-400">Uzun bar demiri</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5" x-show="hasBarbell">
                            <input type="number" step="0.5" name="barbell_weight_kg" value="<?= $eq['barbell_weight_kg'] ?? 20.0 ?>"
                                class="w-16 bg-slate-950 border border-slate-700 rounded-lg px-2 py-1 text-xs text-center font-bold text-white focus:outline-none">
                            <span class="text-xs text-slate-400">kg</span>
                        </div>
                    </div>

                    <!-- Dumbbell -->
                    <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <input type="checkbox" id="has_dumbbells" name="has_dumbbells" value="1" x-model="hasDumbbells"
                                class="w-4 h-4 rounded text-brand-600 bg-slate-800 border-slate-700 focus:ring-0">
                            <div>
                                <label for="has_dumbbells" class="text-xs font-semibold text-white block">Dambıl Kolları</label>
                                <span class="text-[10px] text-slate-400">Tek kol boş ağırlığı</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5" x-show="hasDumbbells">
                            <input type="number" step="0.25" name="dumbbell_handle_weight_kg" value="<?= $eq['dumbbell_handle_weight_kg'] ?? 2.5 ?>"
                                class="w-16 bg-slate-950 border border-slate-700 rounded-lg px-2 py-1 text-xs text-center font-bold text-white focus:outline-none">
                            <span class="text-xs text-slate-400">kg</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Plates Inventory -->
            <div x-show="location !== 'bodyweight'" class="p-5 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-circle-dot text-brand-400"></i> Eldeki Döküm / Kauçuk Plakalar (Adet)
                    </h3>
                    <span class="text-[10px] text-slate-400">İki yana eşit takılır (çiftler taranır)</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <?php 
                    $plateTypes = [
                        '20kg'   => '20 kg Plaka',
                        '15kg'   => '15 kg Plaka',
                        '10kg'   => '10 kg Plaka',
                        '5kg'    => '5 kg Plaka',
                        '2.5kg'  => '2.5 kg Plaka',
                        '1.25kg' => '1.25 kg Plaka',
                        '0.5kg'  => '0.5 kg Plaka'
                    ];
                    foreach ($plateTypes as $key => $title):
                        $val = $platesArr[$key] ?? ($key === '10kg' || $key === '5kg' || $key === '2.5kg' || $key === '1.25kg' ? 4 : 2);
                    ?>
                    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-white block"><?= $title ?></span>
                            <span class="text-[10px] text-slate-500">Mevcut stok</span>
                        </div>
                        <input type="number" min="0" max="20" name="plate_<?= $key ?>" value="<?= $val ?>"
                            class="w-14 bg-slate-950 border border-slate-700 rounded-lg px-2 py-1 text-xs text-center font-bold text-white focus:outline-none">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
                <button type="submit"
                    class="w-full sm:flex-1 py-4 px-6 bg-gradient-to-r from-emerald-600 to-brand-600 hover:from-emerald-500 hover:to-brand-500 text-white font-bold text-sm rounded-2xl shadow-xl shadow-brand-600/30 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <span>Envanteri Kaydet ve AI Programımı Kur</span>
                </button>
                <a href="/workout/dashboard" class="w-full sm:w-auto px-5 py-3.5 rounded-2xl bg-slate-800/80 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-700 text-xs font-semibold text-center transition-all">
                    Panele Dön →
                </a>
            </div>
        </form>
    </div>
</div>
