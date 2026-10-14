<?php
declare(strict_types=1);
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6" x-data="hiitTimer()">
    <?php $activeTab = 'hiit'; require __DIR__ . '/_tabs.php'; ?>

    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 bg-slate-900 p-6 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center border border-amber-500/30">
                    <i class="fa-solid fa-stopwatch-20 text-lg"></i>
                </div>
                <h1 class="text-2xl font-black font-display text-white tracking-tight">HIIT & Tabata Akıllı Kronometre</h1>
            </div>
            <p class="text-sm text-slate-400 max-w-xl">Web Audio API sesli sayaç uyarıları, hazır bilimsel antrenman şablonları ve tam ekran kontrolüyle yüksek yoğunluklu interval sayacı.</p>
        </div>
        <div class="relative z-10 flex items-center gap-2">
            <!-- Ses Aç/Kapat Butonu -->
            <button 
                type="button" 
                @click="toggleSound()"
                class="px-3.5 py-2 rounded-xl text-xs font-bold border transition-all flex items-center gap-2"
                :class="soundOn ? 'bg-amber-500/15 border-amber-500/30 text-amber-300' : 'bg-slate-950 border-slate-800 text-slate-500 hover:text-slate-300'">
                <i :class="soundOn ? 'fa-solid fa-volume-high' : 'fa-solid fa-volume-xmark'"></i>
                <span x-text="soundOn ? 'Sesler Açık' : 'Sessiz Mod'"></span>
            </button>
        </div>
    </div>

    <!-- Preset Selector Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <button 
            type="button" 
            @click="setPreset('tabata')"
            :disabled="isRunning"
            class="p-4 rounded-2xl border text-left transition-all relative overflow-hidden group disabled:opacity-50"
            :class="activePreset === 'tabata' ? 'bg-gradient-to-br from-slate-900 to-amber-950/40 border-amber-500 shadow-lg shadow-amber-500/10' : 'bg-slate-900 border-slate-800 hover:border-slate-700'">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Tabata</span>
                <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-slate-950 text-slate-400">4 Dk</span>
            </div>
            <div class="text-xs text-white font-bold mb-1">20s Çalış / 10s Dinlen</div>
            <div class="text-[11px] text-slate-400">8 Yoğun Set</div>
        </button>

        <button 
            type="button" 
            @click="setPreset('hiit')"
            :disabled="isRunning"
            class="p-4 rounded-2xl border text-left transition-all relative overflow-hidden group disabled:opacity-50"
            :class="activePreset === 'hiit' ? 'bg-gradient-to-br from-slate-900 to-rose-950/40 border-rose-500 shadow-lg shadow-rose-500/10' : 'bg-slate-900 border-slate-800 hover:border-slate-700'">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-400">Klasik HIIT</span>
                <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-slate-950 text-slate-400">10 Dk</span>
            </div>
            <div class="text-xs text-white font-bold mb-1">40s Çalış / 20s Dinlen</div>
            <div class="text-[11px] text-slate-400">10 Kardiyo Seti</div>
        </button>

        <button 
            type="button" 
            @click="setPreset('emom')"
            :disabled="isRunning"
            class="p-4 rounded-2xl border text-left transition-all relative overflow-hidden group disabled:opacity-50"
            :class="activePreset === 'emom' ? 'bg-gradient-to-br from-slate-900 to-indigo-950/40 border-indigo-500 shadow-lg shadow-indigo-500/10' : 'bg-slate-900 border-slate-800 hover:border-slate-700'">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-400">EMOM</span>
                <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-slate-950 text-slate-400">12 Dk</span>
            </div>
            <div class="text-xs text-white font-bold mb-1">50s Çalış / 10s Dinlen</div>
            <div class="text-[11px] text-slate-400">12 Dakikalık Tur</div>
        </button>

        <button 
            type="button" 
            @click="setPreset('custom')"
            :disabled="isRunning"
            class="p-4 rounded-2xl border text-left transition-all relative overflow-hidden group disabled:opacity-50"
            :class="activePreset === 'custom' ? 'bg-gradient-to-br from-slate-900 to-cyan-950/40 border-cyan-500 shadow-lg shadow-cyan-500/10' : 'bg-slate-900 border-slate-800 hover:border-slate-700'">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-cyan-400">Özel Şablon</span>
                <i class="fa-solid fa-sliders text-xs text-slate-400"></i>
            </div>
            <div class="text-xs text-white font-bold mb-1">Kendi Temponuz</div>
            <div class="text-[11px] text-slate-400">Ayarlanabilir Süreler</div>
        </button>
    </div>

    <!-- Custom Config Panel (shown when custom is active and not running) -->
    <div x-show="activePreset === 'custom' && !isRunning" x-transition.duration.200ms class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div>
            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Çalışma (sn)</label>
            <input type="number" x-model.number="workTime" min="5" max="300" step="5" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono text-center font-bold focus:border-cyan-500 outline-none">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Dinlenme (sn)</label>
            <input type="number" x-model.number="restTime" min="0" max="180" step="5" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono text-center font-bold focus:border-cyan-500 outline-none">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Toplam Set</label>
            <input type="number" x-model.number="totalSets" min="1" max="50" step="1" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono text-center font-bold focus:border-cyan-500 outline-none">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Hazırlık (sn)</label>
            <input type="number" x-model.number="prepareTime" min="3" max="30" step="1" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono text-center font-bold focus:border-cyan-500 outline-none">
        </div>
    </div>

    <!-- Big Interactive Stopwatch Display -->
    <div 
        class="rounded-3xl border p-8 sm:p-12 shadow-2xl transition-all duration-500 relative overflow-hidden flex flex-col items-center justify-center text-center"
        :class="{
            'bg-gradient-to-b from-slate-900 via-slate-900 to-indigo-950/60 border-indigo-500/40': phase === 'prepare',
            'bg-gradient-to-b from-slate-900 via-slate-900 to-emerald-950/70 border-emerald-500/50 shadow-emerald-500/10': phase === 'work',
            'bg-gradient-to-b from-slate-900 via-slate-900 to-amber-950/60 border-amber-500/50 shadow-amber-500/10': phase === 'rest',
            'bg-gradient-to-b from-slate-900 via-slate-900 to-purple-950/70 border-purple-500/50': phase === 'complete',
            'bg-slate-900 border-slate-800': phase === 'idle'
        }">

        <!-- Background Glow Ring -->
        <div class="absolute -top-32 w-96 h-96 rounded-full blur-3xl pointer-events-none opacity-20"
             :class="{
                 'bg-indigo-500': phase === 'prepare',
                 'bg-emerald-500': phase === 'work',
                 'bg-amber-500': phase === 'rest',
                 'bg-purple-500': phase === 'complete',
                 'bg-slate-500': phase === 'idle'
             }"></div>

        <!-- Phase Badge -->
        <div class="mb-4 relative z-10">
            <span class="px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest border transition-all inline-flex items-center gap-2"
                  :class="{
                      'bg-indigo-500/20 text-indigo-300 border-indigo-500/40 animate-pulse': phase === 'prepare',
                      'bg-emerald-500/20 text-emerald-300 border-emerald-500/40 shadow-lg shadow-emerald-500/20 animate-pulse': phase === 'work',
                      'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-lg shadow-amber-500/20': phase === 'rest',
                      'bg-purple-500/20 text-purple-300 border-purple-500/40': phase === 'complete',
                      'bg-slate-800 text-slate-400 border-slate-700': phase === 'idle'
                  }">
                <i class="fa-solid" :class="{
                    'fa-shoe-prints': phase === 'prepare',
                    'fa-bolt-lightning': phase === 'work',
                    'fa-heart-pulse': phase === 'rest',
                    'fa-trophy': phase === 'complete',
                    'fa-stopwatch': phase === 'idle'
                }"></i>
                <span x-text="phaseLabel"></span>
            </span>
        </div>

        <!-- Big Digits Display -->
        <div class="relative z-10 my-2">
            <span class="text-7xl sm:text-9xl font-black font-mono tracking-tighter text-white tabular-nums drop-shadow-md select-none"
                  x-text="formatSeconds(timeLeft)"></span>
        </div>

        <!-- Set Count and Overall Remaining Time -->
        <div class="flex items-center gap-6 mt-4 text-xs font-mono font-bold text-slate-400 relative z-10">
            <span class="flex items-center gap-1.5 bg-slate-950/80 px-3.5 py-1.5 rounded-xl border border-slate-800">
                <i class="fa-solid fa-layer-group text-slate-500"></i>
                <span class="text-slate-200" x-text="'Set: ' + (currentSet > 0 ? currentSet : 1) + ' / ' + totalSets"></span>
            </span>
            <span class="flex items-center gap-1.5 bg-slate-950/80 px-3.5 py-1.5 rounded-xl border border-slate-800">
                <i class="fa-solid fa-hourglass-half text-slate-500"></i>
                <span class="text-slate-200" x-text="'Kalan: ' + formatSeconds(totalRemainingTime)"></span>
            </span>
        </div>

        <!-- Progress Bar -->
        <div class="w-full max-w-md bg-slate-950/90 h-2.5 rounded-full mt-8 overflow-hidden border border-slate-800 relative z-10">
            <div 
                class="h-full transition-all duration-300 rounded-full"
                :class="{
                    'bg-indigo-500': phase === 'prepare',
                    'bg-emerald-500': phase === 'work',
                    'bg-amber-500': phase === 'rest',
                    'bg-purple-500': phase === 'complete',
                    'bg-slate-700': phase === 'idle'
                }"
                :style="'width: ' + progressPercent + '%'"></div>
        </div>

        <!-- Control Buttons -->
        <div class="flex items-center justify-center gap-4 mt-8 relative z-10">
            <!-- Reset Button -->
            <button 
                type="button" 
                @click="resetTimer()" 
                title="Sıfırla"
                class="w-12 h-12 rounded-2xl bg-slate-800/90 hover:bg-slate-700 border border-slate-700 text-slate-300 flex items-center justify-center transition-all hover:scale-105 active:scale-95 shadow-md">
                <i class="fa-solid fa-rotate-left text-sm"></i>
            </button>

            <!-- Main Play / Pause Button -->
            <button 
                type="button" 
                @click="togglePlay()" 
                class="px-8 py-4 rounded-2xl text-base font-black tracking-wide text-white flex items-center gap-3 transition-all transform hover:scale-105 active:scale-95 shadow-xl"
                :class="isRunning 
                    ? 'bg-rose-600 hover:bg-rose-500 shadow-rose-600/30' 
                    : 'bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 shadow-emerald-500/30'">
                <i :class="isRunning ? 'fa-solid fa-pause text-lg' : 'fa-solid fa-play text-lg'"></i>
                <span x-text="isRunning ? 'DURAKLAT' : (phase === 'idle' ? 'BAŞLAT' : 'DEVAM ET')"></span>
            </button>

            <!-- Skip / Next Set Button -->
            <button 
                type="button" 
                @click="nextInterval()" 
                :disabled="phase === 'idle' || phase === 'complete'"
                title="Sonraki Interval"
                class="w-12 h-12 rounded-2xl bg-slate-800/90 hover:bg-slate-700 border border-slate-700 text-slate-300 flex items-center justify-center transition-all hover:scale-105 active:scale-95 shadow-md disabled:opacity-40 disabled:pointer-events-none">
                <i class="fa-solid fa-forward-step text-sm"></i>
            </button>
        </div>
    </div>
</div>

<script>
function hiitTimer() {
    return {
        activePreset: 'tabata',
        workTime: 20,
        restTime: 10,
        totalSets: 8,
        prepareTime: 5,

        phase: 'idle', // 'idle', 'prepare', 'work', 'rest', 'complete'
        timeLeft: 20,
        currentSet: 0,
        isRunning: false,
        intervalId: null,
        soundOn: window.HipertrofiApp ? window.HipertrofiApp.audioEnabled : true,
        wakeLock: null,

        init() {
            this.setPreset('tabata');
        },

        get phaseLabel() {
            switch (this.phase) {
                case 'prepare': return 'HAZIRLANIN';
                case 'work':    return 'ÇALIŞMA (WORK)';
                case 'rest':    return 'DİNLENME (REST)';
                case 'complete': return 'TEBRİKLER! TAMAMLANDI';
                default:        return 'HAZIR';
            }
        },

        get progressPercent() {
            if (this.phase === 'complete') return 100;
            if (this.phase === 'idle') return 0;
            let currentTotal = (this.phase === 'prepare') ? this.prepareTime : 
                               ((this.phase === 'work') ? this.workTime : this.restTime);
            if (currentTotal <= 0) return 100;
            return Math.max(0, Math.min(100, Math.round(((currentTotal - this.timeLeft) / currentTotal) * 100)));
        },

        get totalRemainingTime() {
            if (this.phase === 'idle' || this.phase === 'complete') {
                return (this.workTime + this.restTime) * this.totalSets;
            }
            let remainingSets = Math.max(0, this.totalSets - this.currentSet);
            let timeFromCurrent = this.timeLeft;
            if (this.phase === 'prepare') {
                timeFromCurrent += (this.workTime + this.restTime) * this.totalSets;
                return timeFromCurrent;
            }
            if (this.phase === 'work') {
                timeFromCurrent += this.restTime + ((this.workTime + this.restTime) * remainingSets);
                return timeFromCurrent;
            }
            if (this.phase === 'rest') {
                timeFromCurrent += (this.workTime + this.restTime) * remainingSets;
                return timeFromCurrent;
            }
            return 0;
        },

        setPreset(preset) {
            this.activePreset = preset;
            if (preset === 'tabata') {
                this.workTime = 20;
                this.restTime = 10;
                this.totalSets = 8;
                this.prepareTime = 5;
            } else if (preset === 'hiit') {
                this.workTime = 40;
                this.restTime = 20;
                this.totalSets = 10;
                this.prepareTime = 5;
            } else if (preset === 'emom') {
                this.workTime = 50;
                this.restTime = 10;
                this.totalSets = 12;
                this.prepareTime = 5;
            }
            this.resetTimer();
        },

        togglePlay() {
            if (this.isRunning) {
                this.pauseTimer();
            } else {
                this.startTimer();
            }
        },

        startTimer() {
            if (this.phase === 'idle' || this.phase === 'complete') {
                this.phase = 'prepare';
                this.timeLeft = this.prepareTime;
                this.currentSet = 1;
            }
            this.isRunning = true;
            this.requestWakeLock();

            clearInterval(this.intervalId);
            this.intervalId = setInterval(() => {
                this.tick();
            }, 1000);
        },

        pauseTimer() {
            this.isRunning = false;
            clearInterval(this.intervalId);
        },

        resetTimer() {
            this.pauseTimer();
            this.phase = 'idle';
            this.currentSet = 0;
            this.timeLeft = this.workTime;
        },

        tick() {
            if (this.timeLeft > 0) {
                this.timeLeft--;
                // Play warning tick on last 3 seconds
                if (this.timeLeft <= 3 && this.timeLeft > 0) {
                    if (this.soundOn && window.HipertrofiApp) {
                        window.HipertrofiApp.playCountdownTick();
                    }
                }
            } else {
                this.nextInterval();
            }
        },

        nextInterval() {
            if (this.phase === 'prepare') {
                this.phase = 'work';
                this.timeLeft = this.workTime;
                if (this.soundOn && window.HipertrofiApp) window.HipertrofiApp.playWorkStart();
                if ('vibrate' in navigator) navigator.vibrate([200]);
            } else if (this.phase === 'work') {
                if (this.currentSet >= this.totalSets && this.restTime === 0) {
                    this.finishWorkout();
                    return;
                }
                this.phase = 'rest';
                this.timeLeft = this.restTime;
                if (this.soundOn && window.HipertrofiApp) window.HipertrofiApp.playRestStart();
                if ('vibrate' in navigator) navigator.vibrate([100, 100]);
            } else if (this.phase === 'rest') {
                if (this.currentSet >= this.totalSets) {
                    this.finishWorkout();
                    return;
                }
                this.currentSet++;
                this.phase = 'work';
                this.timeLeft = this.workTime;
                if (this.soundOn && window.HipertrofiApp) window.HipertrofiApp.playWorkStart();
                if ('vibrate' in navigator) navigator.vibrate([200]);
            }
        },

        finishWorkout() {
            this.pauseTimer();
            this.phase = 'complete';
            this.timeLeft = 0;
            if (this.soundOn && window.HipertrofiApp) window.HipertrofiApp.playWorkoutComplete();
            if ('vibrate' in navigator) navigator.vibrate([300, 100, 300, 100, 500]);
        },

        toggleSound() {
            if (window.HipertrofiApp) {
                this.soundOn = window.HipertrofiApp.toggleSound();
            } else {
                this.soundOn = !this.soundOn;
            }
        },

        formatSeconds(sec) {
            sec = Math.max(0, parseInt(sec) || 0);
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        },

        async requestWakeLock() {
            try {
                if ('wakeLock' in navigator) {
                    this.wakeLock = await navigator.wakeLock.request('screen');
                }
            } catch (err) {
                console.log('WakeLock not granted:', err);
            }
        }
    };
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
