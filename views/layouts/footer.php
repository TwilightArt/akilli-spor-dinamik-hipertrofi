    </main>

    <?php if (isset($_SESSION['user'])): ?>
    <div class="block lg:hidden fixed bottom-0 left-0 w-full bg-zinc-950/95 backdrop-blur-xl border-t border-zinc-800 z-50 pb-safe">
        <nav class="flex items-center justify-around p-2">
            <a href="/workout/dashboard" class="flex flex-col items-center gap-1 p-2 text-slate-400 hover:text-brand-400 transition-colors">
                <i class="fa-solid fa-home text-xl"></i>
                <span class="text-[10px] font-medium">Panel</span>
            </a>
            <a href="/workout/dashboard" class="flex flex-col items-center gap-1 p-2 -mt-6">
                <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-brand-600 to-orange-500 text-white flex items-center justify-center shadow-lg shadow-brand-500/30 border-4 border-zinc-950">
                    <i class="fa-solid fa-dumbbell text-lg"></i>
                </div>
                <span class="text-[10px] font-bold text-brand-400 mt-1">Antrenman</span>
            </a>
            <a href="/analytics" class="flex flex-col items-center gap-1 p-2 text-slate-400 hover:text-brand-400 transition-colors">
                <i class="fa-solid fa-chart-simple text-xl"></i>
                <span class="text-[10px] font-medium">Analiz</span>
            </a>
            <a href="/profile" class="flex flex-col items-center gap-1 p-2 text-slate-400 hover:text-brand-400 transition-colors">
                <i class="fa-solid fa-user text-xl"></i>
                <span class="text-[10px] font-medium">Profil</span>
            </a>
        </nav>
    </div>
    <div class="h-20 lg:hidden"></div>
    <?php endif; ?>

    <!-- Production Footer -->
    <footer class="border-t border-slate-800/80 bg-slate-950/60 py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                <span>cPanel Production Engine v2.1 • PHP 8.2+ Strict PDO</span>
            </div>
            <p>© <?= date('Y') ?> Akıllı Spor & Dinamik Hipertrofi Platformu. Tüm hakları saklıdır.</p>
        </div>
    </footer>

    <!-- Global App Helpers -->
    <script src="/assets/js/app.js"></script>
    <script>
        // Global CSRF helper for vanilla fetch requests
        window.getCsrfToken = function() {
            return '<?= $csrfToken ?>';
        };
    </script>
</body>
</html>
