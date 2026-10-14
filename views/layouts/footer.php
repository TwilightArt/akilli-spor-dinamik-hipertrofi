    </main>

    <?php if (isset($_SESSION['user'])): ?>
    <?php $currentUri = $_SERVER['REQUEST_URI'] ?? ''; ?>
    <!-- Single Mobile Bottom Navigation Bar (5 Main Tabs) -->
    <div class="fixed bottom-0 left-0 right-0 z-50 lg:hidden bg-slate-950/95 backdrop-blur-xl border-t border-slate-800 shadow-2xl pb-safe">
        <nav class="flex items-center justify-around px-2 py-1.5 max-w-lg mx-auto">
            <!-- 1. Panel -->
            <a href="/workout/dashboard" class="flex flex-col items-center gap-1 py-1 px-2.5 transition-colors <?= str_starts_with($currentUri, '/workout/dashboard') ? 'text-brand-400 font-bold' : 'text-slate-400 hover:text-slate-200' ?>">
                <i class="fa-solid fa-table-columns text-base"></i>
                <span class="text-[10px]">Panel</span>
            </a>

            <!-- 2. Analiz -->
            <a href="/analytics" class="flex flex-col items-center gap-1 py-1 px-2.5 transition-colors <?= str_starts_with($currentUri, '/analytics') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-slate-200' ?>">
                <i class="fa-solid fa-chart-line text-base"></i>
                <span class="text-[10px]">Analiz</span>
            </a>

            <!-- 3. Canlı (Ortada Vurgulu & Yükseltilmiş Buton) -->
            <a href="/workout/live" class="flex flex-col items-center -mt-5 group focus:outline-none">
                <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-brand-600 via-rose-500 to-orange-500 text-white flex items-center justify-center shadow-lg shadow-brand-600/40 border-4 border-slate-950 group-active:scale-95 transition-transform">
                    <i class="fa-solid fa-play text-xs ml-0.5"></i>
                </div>
                <span class="text-[10px] font-bold mt-1 <?= str_starts_with($currentUri, '/workout/live') ? 'text-brand-400' : 'text-slate-300' ?>">Canlı</span>
            </a>

            <!-- 4. Araçlar -->
            <a href="/calculators/1rm" class="flex flex-col items-center gap-1 py-1 px-2.5 transition-colors <?= (str_starts_with($currentUri, '/calculators') || str_starts_with($currentUri, '/tools')) ? 'text-brand-400 font-bold' : 'text-slate-400 hover:text-slate-200' ?>">
                <i class="fa-solid fa-calculator text-base"></i>
                <span class="text-[10px]">Araçlar</span>
            </a>

            <!-- 5. Profil -->
            <a href="/profile" class="flex flex-col items-center gap-1 py-1 px-2.5 transition-colors <?= str_starts_with($currentUri, '/profile') ? 'text-brand-400 font-bold' : 'text-slate-400 hover:text-slate-200' ?>">
                <i class="fa-solid fa-user-gear text-base"></i>
                <span class="text-[10px]">Profil</span>
            </a>
        </nav>
    </div>
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
