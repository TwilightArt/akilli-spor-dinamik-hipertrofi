<div class="min-h-[75vh] flex items-center justify-center py-6 px-4">
    <div class="max-w-md w-full bg-slate-900/90 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl relative overflow-hidden">
        <!-- Glow accent -->
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-brand-600/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="text-center mb-8">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-500 flex items-center justify-center shadow-lg shadow-brand-500/25 mb-4">
                <i class="fa-solid fa-lock text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold font-display text-white tracking-tight">Tekrar Hoş Geldiniz</h1>
            <p class="text-xs text-slate-400 mt-1">Dinamik hipertrofi oturumunuza kaldığınız yerden devam edin</p>
        </div>

        <form action="/login" method="POST" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">E-Posta Adresi</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                        <i class="fa-solid fa-envelope text-sm"></i>
                    </span>
                    <input type="email" id="email" name="email" required autocomplete="email" placeholder="ornek@alanadi.com"
                        class="w-full bg-slate-950/80 border border-slate-800 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Parola</label>
                    <a href="/forgot-password" class="text-xs text-brand-400 hover:text-brand-300 transition-colors">Şifremi Unuttum?</a>
                </div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                        <i class="fa-solid fa-key text-sm"></i>
                    </span>
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••"
                        class="w-full bg-slate-950/80 border border-slate-800 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                </div>
            </div>

            <button type="submit"
                class="w-full py-3.5 px-4 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white font-semibold text-sm rounded-xl shadow-lg shadow-brand-600/30 active:scale-[0.99] transition-all flex items-center justify-center gap-2">
                <span>Giriş Yap</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-slate-400">
            Hesabınız yok mu?
            <a href="/register" class="text-brand-400 hover:text-brand-300 font-semibold ml-1 transition-colors">Hemen Ücretsiz Kayıt Olun</a>
        </div>
    </div>
</div>
