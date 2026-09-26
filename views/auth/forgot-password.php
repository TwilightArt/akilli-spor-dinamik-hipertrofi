<div class="min-h-[70vh] flex items-center justify-center py-6 px-4">
    <div class="max-w-md w-full bg-slate-900/90 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl">
        <div class="text-center mb-8">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center mb-4">
                <i class="fa-solid fa-key text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold font-display text-white tracking-tight">Parolanızı mı Unuttunuz?</h1>
            <p class="text-xs text-slate-400 mt-1">Kayıtlı e-posta adresinizi girin, sıfırlama bağlantısını iletelim</p>
        </div>

        <?php if (!empty($_SESSION['dev_reset_link'])): ?>
            <div class="mb-5 p-3 rounded-xl bg-indigo-950/70 border border-indigo-500/30 text-xs text-indigo-200">
                <span class="font-bold block text-indigo-400 mb-1">Geliştirici Test Bağlantısı:</span>
                <a href="<?= $_SESSION['dev_reset_link'] ?>" class="text-brand-300 underline break-all"><?= $_SESSION['dev_reset_link'] ?></a>
            </div>
            <?php unset($_SESSION['dev_reset_link']); ?>
        <?php endif; ?>

        <form action="/forgot-password" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Kayıtlı E-Posta</label>
                <input type="email" id="email" name="email" required placeholder="ornek@alanadi.com"
                    class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 transition-all">
            </div>

            <button type="submit"
                class="w-full py-3.5 px-4 bg-brand-600 hover:bg-brand-500 text-white font-semibold text-sm rounded-xl shadow-lg shadow-brand-600/30 active:scale-[0.99] transition-all">
                Sıfırlama Bağlantısı Gönder
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-slate-400">
            Hatırladınız mı?
            <a href="/login" class="text-brand-400 hover:text-brand-300 font-semibold ml-1">Giriş Yap</a>
        </div>
    </div>
</div>
