<?php
use App\Core\Security;
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

$valName = Security::escape($old['name'] ?? '');
$valEmail = Security::escape($old['email'] ?? '');
$valGender = $old['gender'] ?? 'male';
$valBirthDate = Security::escape($old['birth_date'] ?? '1995-05-15');
$valHeight = Security::escape((string)($old['height_cm'] ?? '178.0'));
$valWeight = Security::escape((string)($old['current_weight_kg'] ?? '75.0'));
?>
<div class="min-h-[80vh] flex items-center justify-center py-6 px-4">
    <div class="max-w-md w-full bg-slate-900/90 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl relative overflow-hidden">
        <div class="absolute -top-24 -left-24 w-48 h-48 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>

        <?php if (!empty($currentUser)): ?>
        <!-- Notice if already logged in -->
        <div class="mb-6 p-4 rounded-2xl bg-amber-950/60 border border-amber-500/30 text-amber-200">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-circle-user text-amber-400 text-lg mt-0.5 shrink-0"></i>
                <div class="space-y-1">
                    <span class="text-xs font-bold block text-white">Şu Anda Giriş Yapmış Durumdasınız</span>
                    <p class="text-[11px] text-amber-300">
                        <strong><?= Security::escape($currentUser['name']) ?></strong> hesabıyla oturumunuz açık. Farklı bir hesapla kaydolmak için önce çıkış yapabilirsiniz.
                    </p>
                    <div class="flex items-center gap-2 pt-2">
                        <a href="/workout/dashboard" class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs">
                            <i class="fa-solid fa-table-columns mr-1"></i> Panele Dön
                        </a>
                        <a href="/logout" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-rose-500/20 text-rose-300 border border-slate-700 text-xs font-bold">
                            <i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> Çıkış Yap
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="text-center mb-8">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-500 flex items-center justify-center shadow-lg shadow-indigo-500/25 mb-4">
                <i class="fa-solid fa-user-plus text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold font-display text-white tracking-tight">Yeni Profil Oluştur</h1>
            <p class="text-xs text-slate-400 mt-1">Kişiye özel dinamik ağırlık adaptasyonu ile hipertrofiyi başlat</p>
        </div>

        <form action="/register" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Ad Soyad</label>
                <input type="text" id="name" name="name" required placeholder="Adınız Soyadınız" value="<?= $valName ?>"
                    class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 transition-all">
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">E-Posta</label>
                <input type="email" id="email" name="email" required placeholder="ornek@alanadi.com" value="<?= $valEmail ?>"
                    class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 transition-all">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Parola (En az 8 karakter)</label>
                <input type="password" id="password" name="password" minlength="8" required placeholder="••••••••"
                    class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 transition-all">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="gender" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Cinsiyet</label>
                    <select id="gender" name="gender" class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500 transition-all">
                        <option value="male" <?= $valGender === 'male' ? 'selected' : '' ?>>Erkek</option>
                        <option value="female" <?= $valGender === 'female' ? 'selected' : '' ?>>Kadın</option>
                    </select>
                </div>
                <div>
                    <label for="birth_date" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Doğum Tarihi</label>
                    <input type="date" id="birth_date" name="birth_date" required value="<?= $valBirthDate ?>"
                        class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500 transition-all">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="height_cm" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Boy (cm)</label>
                    <input type="number" step="0.5" id="height_cm" name="height_cm" required value="<?= $valHeight ?>" placeholder="178"
                        class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 transition-all">
                </div>
                <div>
                    <label for="current_weight_kg" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Vücut Ağırlığı (kg)</label>
                    <input type="number" step="0.1" id="current_weight_kg" name="current_weight_kg" required value="<?= $valWeight ?>" placeholder="75.0"
                        class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 transition-all">
                </div>
            </div>

            <button type="submit"
                class="w-full py-3.5 px-4 mt-2 btn-sporty w-full py-3.5 px-4 text-sm flex items-center justify-center gap-2 flex items-center justify-center gap-2">
                <span>Hesabı Oluştur & Başla</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-slate-400">
            Zaten bir hesabınız var mı?
            <a href="/login" class="text-brand-400 hover:text-brand-300 font-semibold ml-1 transition-colors">Giriş Yapın</a>
        </div>
    </div>
</div>

