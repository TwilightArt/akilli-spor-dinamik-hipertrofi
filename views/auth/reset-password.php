<div class="min-h-[70vh] flex items-center justify-center py-6 px-4">
    <div class="max-w-md w-full bg-slate-900/90 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl">
        <div class="text-center mb-8">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mb-4">
                <i class="fa-solid fa-shield-halved text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold font-display text-white tracking-tight">Yeni Parola Oluştur</h1>
            <p class="text-xs text-slate-400 mt-1">Hesabınız için güçlü ve güvenli yeni bir parola belirleyin</p>
        </div>

        <form action="/reset-password" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email ?? '') ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Yeni Parola (En az 8 karakter)</label>
                <input type="password" id="password" name="password" minlength="8" required placeholder="••••••••"
                    class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 transition-all">
            </div>

            <button type="submit"
                class="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm rounded-xl shadow-lg shadow-emerald-600/30 active:scale-[0.99] transition-all">
                Parolayı Güncelle ve Giriş Yap
            </button>
        </form>
    </div>
</div>
