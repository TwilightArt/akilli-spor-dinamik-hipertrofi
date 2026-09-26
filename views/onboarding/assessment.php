<div class="max-w-3xl mx-auto py-4">
    <!-- Header Step Progress -->
    <div class="mb-8">
        <div class="flex items-center justify-between text-xs font-semibold text-slate-400 mb-2">
            <span class="text-brand-400 font-bold uppercase tracking-wider">Aşama 1 / 2: Kondisyon ve Kuvvet Testi</span>
            <span>Sonraki: Ekipman & Plakalar</span>
        </div>
        <div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-brand-600 to-indigo-500 w-1/2 rounded-full"></div>
        </div>
    </div>

    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl backdrop-blur-xl">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-brand-600/10 border border-brand-500/20 text-brand-400 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-gauge-high text-xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold font-display text-white">Kalistenik Kuvvet Değerlendirmesi</h1>
                <p class="text-xs text-slate-400 mt-1">Platformumuz, vücut ağırlığınız ve bu testteki tekrar sayılarınıza göre ilk antrenman ağırlıklarınızı NSCA ve Epley standartlarında otomatik olarak hesaplar.</p>
            </div>
        </div>

        <div class="mb-6 p-4 rounded-2xl bg-slate-950/60 border border-slate-800/80 text-xs text-slate-300 space-y-2">
            <div class="flex items-center gap-2 text-indigo-400 font-semibold">
                <i class="fa-solid fa-circle-info"></i>
                <span>Sıfır (0) Tekrar Çekenler İçin Otomatik Regresyon Güvencesi:</span>
            </div>
            <p>Eğer bir harekette henüz 0 çekiyorsanız endişelenmeyin! Sistemimiz sizi <code class="text-amber-400 font-mono">absolute_beginner</code> olarak sınıflandırır ve duvara şınav, negatif barfiks veya kapı havlu çekişi gibi güvenli regresyon zincirlerini otomatik devreye alır.</p>
        </div>

        <form action="/onboarding/assessment" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Pushup -->
                <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80 hover:border-slate-700 transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <label for="pushups" class="text-sm font-semibold text-white flex items-center gap-2">
                            <i class="fa-solid fa-person-praying text-brand-400"></i> Şınav (Push-Up)
                        </label>
                        <span class="text-[11px] text-slate-400">Tek Seferde</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mb-3">Göğüs yere değecek şekilde tam nizami şınav sayısı (Yatay İtiş katsayısı için).</p>
                    <input type="number" id="pushups" name="pushups" min="0" max="150" value="12" required
                        class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-lg font-bold text-white focus:outline-none focus:border-brand-500">
                </div>

                <!-- Chinup -->
                <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80 hover:border-slate-700 transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <label for="chinups" class="text-sm font-semibold text-white flex items-center gap-2">
                            <i class="fa-solid fa-hands text-brand-400"></i> Barfiks / Chin-Up
                        </label>
                        <span class="text-[11px] text-slate-400">Tek Seferde</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mb-3">Çene barı geçecek şekilde tam çekiş sayısı (Çekiş & Kol katsayısı için).</p>
                    <input type="number" id="chinups" name="chinups" min="0" max="100" value="3" required
                        class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-lg font-bold text-white focus:outline-none focus:border-brand-500">
                </div>

                <!-- Squat -->
                <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80 hover:border-slate-700 transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <label for="squats" class="text-sm font-semibold text-white flex items-center gap-2">
                            <i class="fa-solid fa-person-walking text-brand-400"></i> Vücut Ağırlığı Squat
                        </label>
                        <span class="text-[11px] text-slate-400">Tek Seferde</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mb-3">Kalça diz seviyesinin altına inecek şekilde kesintisiz squat sayısı.</p>
                    <input type="number" id="squats" name="squats" min="0" max="200" value="20" required
                        class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-lg font-bold text-white focus:outline-none focus:border-brand-500">
                </div>

                <!-- Plank Hold -->
                <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80 hover:border-slate-700 transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <label for="plank_seconds" class="text-sm font-semibold text-white flex items-center gap-2">
                            <i class="fa-solid fa-stopwatch text-brand-400"></i> Ön Plank (Saniye)
                        </label>
                        <span class="text-[11px] text-slate-400">İzometrik Süre</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mb-3">Düz omurga pozisyonunda bel düşmeden bekleyebildiğiniz maksimum süre.</p>
                    <input type="number" id="plank_seconds" name="plank_seconds" min="0" max="600" value="45" required
                        class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-lg font-bold text-white focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
                <button type="submit"
                    class="w-full sm:flex-1 py-4 px-6 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white font-bold text-sm rounded-2xl shadow-xl shadow-brand-600/30 transition-all flex items-center justify-center gap-2">
                    <span>Testi Kaydet ve Ekipman Seçimine Geç</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
                <a href="/workout/dashboard" class="w-full sm:w-auto px-5 py-3.5 rounded-2xl bg-slate-800/80 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-700 text-xs font-semibold text-center transition-all">
                    Şimdilik Atla ve Panele Git →
                </a>
            </div>
        </form>
    </div>
</div>
