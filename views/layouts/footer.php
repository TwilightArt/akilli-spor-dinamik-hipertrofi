    </main>

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
<script>
        if ("serviceWorker" in navigator) {
            window.addEventListener("load", () => {
                navigator.serviceWorker.register("/sw.js").then(reg => {
                    console.log("Service Worker registered.", reg);
                }).catch(err => {
                    console.log("Service Worker registration failed:", err);
                });
            });
        }
    </script>
</body>
</html>

