<?php
declare(strict_types=1);
use App\Core\Security;
$currentUser = $_SESSION['user'] ?? null;
$csrfToken = Security::generateCsrfToken();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$fitnessLevels = [
    'absolute_beginner' => 'Temel Adaptasyon',
    'beginner'          => 'Başlangıç Seviyesi',
    'intermediate'      => 'Orta Seviye',
    'advanced'          => 'İleri Seviye'
];
$userLevelRaw = $currentUser['fitness_level'] ?? 'beginner';
$userLevelTr = $fitnessLevels[$userLevelRaw] ?? 'Başlangıç Seviyesi';
?>
<!DOCTYPE html>
<html lang="tr" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Akıllı spor ve dinamik hipertrofi platformu ile kişiselleştirilmiş antrenman programları oluşturun ve ilerlemenizi canlı olarak takip edin.">
    <meta name="theme-color" content="#e11d48">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Hipertrofi">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/icons/icon-192x192.png">

    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').catch(function(err) {
                    console.warn('PWA SW registration failed:', err);
                });
            });
        }
    </script>

    <!-- Open Graph -->
    <meta property="og:title" content="<?= Security::escape($pageTitle ?? 'Akıllı Spor & Dinamik Hipertrofi Platformu') ?>">
    <meta property="og:description" content="Kişiselleştirilmiş antrenman programları oluşturun ve ilerlemenizi canlı olarak takip edin.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= Security::escape('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>">

    <title><?= Security::escape($pageTitle ?? 'Akıllı Spor & Dinamik Hipertrofi Platformu') ?></title>
    
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;700;900&display=swap" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS (CDN with custom config) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#fff1f2',
                            100: '#ffe4e6',
                            400: '#fb7185',
                            500: '#f43f5e',
                            600: '#e11d48',
                            700: '#be123c',
                            900: '#881337',
                        },
                        darkbg: '#000000',
                        cardbg: '#09090b',
                        cardborder: '#27272a'
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer components {
            .btn-sporty {
                @apply bg-gradient-to-r from-rose-600 to-orange-500 text-white font-bold rounded-xl shadow-lg shadow-orange-500/20 hover:shadow-orange-500/40 hover:from-rose-500 hover:to-orange-400 transition-all duration-300 transform hover:-translate-y-0.5 border border-rose-500/30;
            }
            .btn-sporty-outline {
                @apply border border-rose-600 text-rose-500 font-bold rounded-xl shadow-[0_0_10px_rgba(225,29,72,0.1)] hover:bg-rose-600/10 hover:shadow-[0_0_15px_rgba(225,29,72,0.2)] transition-all duration-300;
            }
            .card-sporty {
                @apply bg-zinc-900/80 backdrop-blur-md border border-zinc-800 rounded-2xl shadow-xl;
            }
            .text-gradient {
                @apply bg-clip-text text-transparent bg-gradient-to-r from-rose-500 to-orange-400;
            }
        }
    </style>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <!-- Chart.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0b0f19;
            color: #f3f4f6;
            -webkit-tap-highlight-color: transparent;
        }
        h1, h2, h3, h4, .font-display {
            font-family: 'Outfit', sans-serif;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #111827;
        }
        ::-webkit-scrollbar-thumb {
            background: #374151;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #4f46e5;
        }
    </style>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-darkbg text-slate-100 min-h-screen flex flex-col antialiased selection:bg-brand-500 selection:text-white">

    <!-- PWA Install Prompt Banner -->
    <div x-data="pwaInstallBanner()" x-show="canInstall" x-cloak class="bg-gradient-to-r from-rose-950/95 via-slate-900/95 to-indigo-950/95 border-b border-brand-500/30 px-4 py-2.5 text-xs text-white flex items-center justify-between gap-3 sticky top-0 z-[60] backdrop-blur-md shadow-lg">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center font-bold text-white shadow">
                <i class="fa-solid fa-mobile-screen-button text-sm"></i>
            </div>
            <div>
                <span class="font-bold text-white">Uygulamayı Ana Ekrana Ekleyin</span>
                <span class="text-slate-300 ml-1.5 hidden sm:inline">• Hızlı erişim, çevrimdışı önbellek ve tam ekran antrenman deneyimi.</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button @click="installApp()" class="px-3 py-1.5 bg-brand-600 hover:bg-brand-500 text-white font-bold rounded-lg shadow-md transition-all flex items-center gap-1.5 text-xs">
                <i class="fa-solid fa-download text-[10px]"></i> Ekle
            </button>
            <button @click="dismissBanner()" aria-label="Kapat" class="p-1.5 text-slate-400 hover:text-white rounded-lg transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
    <script>
    function pwaInstallBanner() {
        return {
            canInstall: false,
            deferredPrompt: null,
            init() {
                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    this.deferredPrompt = e;
                    if (!sessionStorage.getItem('pwa_prompt_dismissed')) {
                        this.canInstall = true;
                    }
                });
                window.addEventListener('appinstalled', () => {
                    this.canInstall = false;
                    this.deferredPrompt = null;
                });
            },
            async installApp() {
                if (!this.deferredPrompt) return;
                this.deferredPrompt.prompt();
                const choice = await this.deferredPrompt.userChoice;
                this.deferredPrompt = null;
                this.canInstall = false;
            },
            dismissBanner() {
                this.canInstall = false;
                sessionStorage.setItem('pwa_prompt_dismissed', '1');
            }
        };
    }
    </script>

    <!-- Top Navigation Header -->
    <header class="sticky top-0 z-50 bg-slate-900/80 backdrop-blur-md border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand Logo -->
                <div class="flex items-center gap-3">
                    <a href="/workout/dashboard" class="flex items-center gap-2.5 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-400 flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                            <i class="fa-solid fa-bolt text-white text-lg"></i>
                        </div>
                        <div>
                            <span class="font-display font-black text-xl tracking-tight text-white group-hover:text-brand-400 transition-colors">
                                Hipertrofi<span class="text-brand-500">.ai</span>
                            </span>
                            <span class="hidden sm:inline-block ml-1.5 px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase bg-brand-500/10 text-brand-400 rounded-full border border-brand-500/20">v2.1</span>
                        </div>
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                <?php if ($currentUser): ?>
                <?php $currentUri = $_SERVER['REQUEST_URI'] ?? ''; ?>
                <nav class="hidden lg:flex items-center gap-1 xl:gap-1.5">
                    <a href="/workout/dashboard" class="px-2.5 xl:px-3 py-2 rounded-lg text-xs xl:text-sm font-medium whitespace-nowrap transition-all flex items-center gap-1.5 <?= str_starts_with($currentUri, '/workout/dashboard') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                        <i class="fa-solid fa-table-columns text-slate-400 text-xs"></i>
                        <span>Panel</span>
                    </a>
                    <a href="/workout/live" class="px-2.5 xl:px-3 py-2 rounded-lg text-xs xl:text-sm font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 <?= str_starts_with($currentUri, '/workout/live') ? 'bg-brand-600 text-white shadow-md shadow-brand-600/30' : 'bg-brand-600/10 text-brand-400 border border-brand-500/30 hover:bg-brand-600 hover:text-white shadow-sm' ?>">
                        <i class="fa-solid fa-play text-xs"></i>
                        <span>Canlı İdman</span>
                    </a>
                    <a href="/analytics" class="px-2.5 xl:px-3 py-2 rounded-lg text-xs xl:text-sm font-medium whitespace-nowrap transition-all flex items-center gap-1.5 <?= str_starts_with($currentUri, '/analytics') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                        <i class="fa-solid fa-chart-line text-emerald-400 text-xs"></i>
                        <span>Analitik & PR</span>
                    </a>
                    <a href="/weigh-in" class="px-2.5 xl:px-3 py-2 rounded-lg text-xs xl:text-sm font-medium whitespace-nowrap transition-all flex items-center gap-1.5 <?= str_starts_with($currentUri, '/weigh-in') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                        <i class="fa-solid fa-weight-scale text-slate-400 text-xs"></i>
                        <span>Tartı & Ölçü</span>
                    </a>
                    <a href="/workout/exercises" class="px-2.5 xl:px-3 py-2 rounded-lg text-xs xl:text-sm font-medium whitespace-nowrap transition-all flex items-center gap-1.5 <?= str_starts_with($currentUri, '/workout/exercises') ? 'bg-slate-800 text-amber-400 font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                        <i class="fa-solid fa-book-bookmark text-amber-400 text-xs"></i>
                        <span>Egzersizler</span>
                    </a>
                    <a href="/calculators/1rm" class="px-2.5 xl:px-3 py-2 rounded-lg text-xs xl:text-sm font-medium whitespace-nowrap transition-all flex items-center gap-1.5 <?= (str_starts_with($currentUri, '/calculators') || str_starts_with($currentUri, '/tools')) ? 'bg-slate-800 text-brand-400 font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                        <i class="fa-solid fa-calculator text-brand-400 text-xs"></i>
                        <span>Hesaplayıcılar</span>
                    </a>
                    <a href="/onboarding/equipment" class="px-2.5 xl:px-3 py-2 rounded-lg text-xs xl:text-sm font-medium whitespace-nowrap transition-all flex items-center gap-1.5 <?= str_starts_with($currentUri, '/onboarding/equipment') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                        <i class="fa-solid fa-dumbbell text-slate-400 text-xs"></i>
                        <span>Ekipman</span>
                    </a>
                    <a href="/profile" class="px-2.5 xl:px-3 py-2 rounded-lg text-xs xl:text-sm font-medium whitespace-nowrap transition-all flex items-center gap-1.5 <?= str_starts_with($currentUri, '/profile') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                        <i class="fa-solid fa-user-gear text-slate-400 text-xs"></i>
                        <span>Profil</span>
                    </a>
                </nav>
                <?php endif; ?>

                <!-- User Profile / Auth Actions -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <?php if ($currentUser): ?>
                        <!-- Seviye Rozeti (Mobilde ve Masaüstünde Görünür) -->
                        <span class="px-2 sm:px-2.5 py-1 text-[10px] sm:text-xs font-bold rounded-full bg-brand-500/10 text-brand-400 border border-brand-500/20 whitespace-nowrap">
                            <?= $userLevelTr ?>
                        </span>

                        <!-- Masaüstü Kullanıcı Adı ve Profil Linki -->
                        <a href="/profile" title="Profil Ayarları & Boy/Kilo" class="hidden sm:flex flex-col text-right group hover:opacity-90 transition-all">
                            <span class="text-xs font-semibold text-white leading-tight group-hover:text-brand-400 flex items-center gap-1.5 justify-end">
                                <?= Security::escape($currentUser['name']) ?>
                                <i class="fa-solid fa-gear text-[10px] text-slate-500 group-hover:text-brand-400"></i>
                            </span>
                        </a>

                        <!-- Çıkış Butonu -->
                        <a href="/logout" title="Çıkış Yap" class="w-9 h-9 rounded-lg bg-slate-800 hover:bg-red-500/20 text-slate-400 hover:text-red-400 border border-slate-700 hover:border-red-500/30 flex items-center justify-center transition-all shrink-0">
                            <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
                        </a>
                    <?php else: ?>
                        <a href="/login" class="text-sm font-medium text-slate-300 hover:text-white px-3 py-1.5 transition-colors">Giriş</a>
                        <a href="/register" class="text-sm font-medium px-4 py-2 text-sm btn-sporty">Kayıt Ol</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Global Flash Alerts Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-28 lg:pb-12">
        <?php if ($flash): ?>
            <div x-data="{ show: true }" x-show="show" x-transition.duration.300ms role="<?= in_array($flash['type'], ['danger', 'warning']) ? 'alert' : 'status' ?>" class="mb-6 p-4 rounded-2xl flex items-start justify-between gap-3 shadow-lg <?=
                $flash['type'] === 'success' ? 'bg-emerald-950/60 border border-emerald-500/30 text-emerald-200' : 
                ($flash['type'] === 'danger' ? 'bg-rose-950/60 border border-rose-500/30 text-rose-200' : 
                ($flash['type'] === 'warning' ? 'bg-amber-950/60 border border-amber-500/30 text-amber-200' : 
                'bg-blue-950/60 border border-blue-500/30 text-blue-200')) ?>">
                <div class="flex items-center gap-3">
                    <i aria-hidden="true" class="text-lg <?=
                        $flash['type'] === 'success' ? 'fa-solid fa-circle-check text-emerald-400' : 
                        ($flash['type'] === 'danger' ? 'fa-solid fa-triangle-exclamation text-rose-400' : 
                        ($flash['type'] === 'warning' ? 'fa-solid fa-triangle-exclamation text-amber-400' : 
                        'fa-solid fa-circle-info text-blue-400')) ?>"></i>
                    <p class="text-sm font-medium">
                        <span class="sr-only"><?=
                            $flash['type'] === 'success' ? 'Başarılı:' :
                            ($flash['type'] === 'danger' ? 'Hata:' :
                            ($flash['type'] === 'warning' ? 'Uyarı:' :
                            'Bilgi:'))
                        ?></span>
                        <?= Security::escape($flash['message']) ?>
                    </p>
                </div>
                <button type="button" aria-label="Uyarıyı Kapat" @click="show = false; document.body.focus()" class="min-w-[44px] min-h-[44px] flex items-center justify-center p-2 -mr-2 -mt-2 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white transition-colors">
                    <i aria-hidden="true" class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
        <?php endif; ?>



