<?php
use App\Core\Security;
?>
<div class="max-w-xl mx-auto py-8 px-4" x-data="{
    loading: true,
    coachMessage: '',
    async fetchAiCoach() {
        try {
            let res = await fetch('/api/ai-coach?session_id=<?= $sessionId ?>');
            let data = await res.json();
            this.coachMessage = data.message || 'Hata oluştu.';
        } catch(e) {
            this.coachMessage = 'Bağlantı hatası.';
        } finally {
            this.loading = false;
        }
    }
}" x-init="fetchAiCoach()">
    
    <!-- Success Banner -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-emerald-500/20 text-emerald-400 mb-4 shadow-[0_0_30px_rgba(16,185,129,0.3)]">
            <i class="fa-solid fa-trophy text-4xl"></i>
        </div>
        <h1 class="text-3xl font-display font-black text-white mb-2">Antrenman Tamamlandı!</h1>
        <p class="text-slate-400">Harika bir iş çıkardın, kasların dinlenmeyi hak etti.</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 gap-4 mb-8">
        <div class="card-sporty p-5 text-center">
            <div class="text-brand-400 mb-1"><i class="fa-solid fa-dumbbell text-xl"></i></div>
            <div class="text-2xl font-black text-white"><?= Security::escape($summary['total_sets_completed'] ?? 0) ?></div>
            <div class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">Toplam Set</div>
        </div>
        <div class="card-sporty p-5 text-center">
            <div class="text-orange-400 mb-1"><i class="fa-solid fa-weight-hanging text-xl"></i></div>
            <div class="text-2xl font-black text-white"><?= Security::escape(round($summary['total_volume_kg'] ?? 0)) ?> kg</div>
            <div class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">Kaldırılan Hacim</div>
        </div>
    </div>

    <!-- AI Coach Section -->
    <div class="card-sporty p-6 relative overflow-hidden mb-8 border-brand-500/30">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-brand-600/20 rounded-full blur-2xl"></div>
        <h3 class="text-lg font-display font-bold text-gradient flex items-center gap-2 mb-4">
            <i class="fa-solid fa-robot"></i> Yapay Zeka Koç Analizi
        </h3>
        
        <div x-show="loading" class="flex flex-col items-center justify-center py-6 text-brand-400">
            <i class="fa-solid fa-circle-notch fa-spin text-3xl mb-3"></i>
            <span class="text-sm font-semibold animate-pulse">Setlerin analiz ediliyor...</span>
        </div>
        
        <div x-show="!loading" style="display: none;" class="relative">
            <i class="fa-solid fa-quote-left text-3xl text-zinc-800 absolute -top-2 -left-2 z-0"></i>
            <p class="text-slate-300 relative z-10 leading-relaxed font-medium" x-text="coachMessage"></p>
        </div>
    </div>

    <a href="/workout/dashboard" class="btn-sporty w-full py-4 flex items-center justify-center gap-2 text-lg">
        Panele Dön <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>
