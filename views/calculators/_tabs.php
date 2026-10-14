<?php
declare(strict_types=1);
/**
 * Shared Calculator & Tool Navigation Tabs
 * Expects $activeTab string ('1rm', 'tdee', 'water', 'rpe', 'hiit')
 */
$activeTab = $activeTab ?? '1rm';

$tabs = [
    [
        'id'    => '1rm',
        'title' => '1RM Hesaplayıcı',
        'url'   => '/calculators/1rm',
        'icon'  => 'fa-calculator'
    ],
    [
        'id'    => 'tdee',
        'title' => 'TDEE & Makro',
        'url'   => '/calculators/tdee',
        'icon'  => 'fa-fire'
    ],
    [
        'id'    => 'water',
        'title' => 'Su İhtiyacı',
        'url'   => '/calculators/water',
        'icon'  => 'fa-droplet'
    ],
    [
        'id'    => 'rpe',
        'title' => 'RPE / RIR Tablosu',
        'url'   => '/calculators/rpe',
        'icon'  => 'fa-gauge-high'
    ],
    [
        'id'    => 'hiit',
        'title' => 'HIIT / Tabata',
        'url'   => '/tools/hiit',
        'icon'  => 'fa-stopwatch-20'
    ]
];
?>

<div class="mb-6 overflow-x-auto pb-1 scrollbar-none">
    <nav class="flex items-center gap-2 p-1.5 bg-slate-900/90 border border-slate-800 rounded-2xl min-w-max shadow-lg backdrop-blur-md">
        <?php foreach ($tabs as $tab): ?>
            <?php $isActive = ($activeTab === $tab['id']); ?>
            <a href="<?= $tab['url'] ?>" 
               class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 flex items-center gap-2 <?= $isActive 
                   ? 'bg-gradient-to-r from-brand-600 to-rose-600 text-white shadow-lg shadow-brand-600/30 ring-1 ring-white/20' 
                   : 'text-slate-400 hover:text-white hover:bg-slate-800/80' ?>">
                <i class="fa-solid <?= $tab['icon'] ?> text-sm <?= $isActive ? 'text-white' : 'text-slate-500' ?>"></i>
                <span><?= $tab['title'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</div>
