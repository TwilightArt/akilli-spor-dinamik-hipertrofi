<?php
use App\Core\Security;
$sessionId = (int)$session['id'];
$initialExercisesJson = json_encode(array_values($exercises), JSON_UNESCAPED_UNICODE);
?>
<script>
window.__LIVE_WORKOUT_CONFIG__ = {
    sessionId: <?= $sessionId ?>,
    csrfToken: '<?= Security::escape($csrfToken) ?>',
    exercises: <?= $initialExercisesJson ?: '[]' ?>
};

function liveWorkout(config) {
    return {
        sessionId: config.sessionId,
        csrfToken: config.csrfToken,
        exercises: config.exercises || [],
        currentExerciseIndex: 0,
        actualReps: 10,
        rirBucket: '1-2',
        formBreakdown: false,
        isSubmitting: false,
        adaptationBanner: null,

        // Timer State
        timerRemaining: 0,
        timerRunning: false,
        timerInterval: null,
        timerNotice: 'Dinlenme süresi',

        init() {
            if (this.currentExercise) {
                this.actualReps = this.currentExercise.target_reps_min || 10;
            }
        },

        get currentExercise() {
            return this.exercises[this.currentExerciseIndex] || null;
        },

        getTargetSetsArray() {
            const count = parseInt(this.currentExercise?.target_sets || 3);
            const arr = [];
            for (let i = 1; i <= count; i++) {
                arr.push(i);
            }
            return arr;
        },

        getLogForSet(setNum) {
            if (!this.currentExercise || !this.currentExercise.logs) return null;
            return this.currentExercise.logs.find(l => parseInt(l.set_number) === parseInt(setNum)) || null;
        },

        getSetCardClass(setNum) {
            const completed = parseInt(this.currentExercise?.completed_sets || 0);
            const activeSet = parseInt(this.currentExercise?.next_set_number || 1);
            const targetSets = parseInt(this.currentExercise?.target_sets || 3);

            if (setNum <= completed) {
                return 'bg-emerald-950/20 border-emerald-500/30 text-slate-200';
            }
            if (setNum === activeSet && completed < targetSets) {
                return 'bg-brand-950/40 border-brand-500/60 shadow-lg shadow-brand-500/10 text-white';
            }
            return 'bg-slate-950/40 border-slate-800 text-slate-400 opacity-60';
        },

        prevExercise() {
            if (this.currentExerciseIndex > 0) {
                this.currentExerciseIndex--;
                this.syncInputs();
            }
        },

        nextExercise() {
            if (this.currentExerciseIndex < this.exercises.length - 1) {
                this.currentExerciseIndex++;
                this.syncInputs();
            }
        },

        syncInputs() {
            if (this.currentExercise) {
                this.actualReps = this.currentExercise.target_reps_min || 10;
                this.rirBucket = '1-2';
                this.formBreakdown = false;
            }
        },

        async submitSet() {
            if (!this.currentExercise || this.isSubmitting) return;
            this.isSubmitting = true;

            const isDuration = (this.currentExercise.movement_pattern === 'core');
            const setNumber = this.currentExercise.next_set_number || 1;
            const submittedWeight = parseFloat(this.currentExercise.current_weight || 0);

            const payload = new URLSearchParams();
            payload.append('csrf_token', this.csrfToken);
            payload.append('session_id', this.sessionId);
            payload.append('exercise_id', this.currentExercise.exercise_id);
            payload.append('set_number', setNumber);
            payload.append('set_type', isDuration ? 'duration' : 'reps');
            payload.append('actual_weight', submittedWeight);
            payload.append('target_reps', this.currentExercise.target_reps_max || 10);
            payload.append('actual_reps', isDuration ? '' : this.actualReps);
            payload.append('duration_seconds', isDuration ? this.actualReps : '');
            payload.append('rir_bucket', this.rirBucket);
            payload.append('form_breakdown', this.formBreakdown ? '1' : '0');

            try {
                const response = await fetch('/api/log_set', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: payload.toString()
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    alert(data.error || 'Set kaydedilirken bir hata oluştu.');
                    this.isSubmitting = false;
                    return;
                }

                // 1. Append log to current exercise logs array
                if (!this.currentExercise.logs) {
                    this.currentExercise.logs = [];
                }
                this.currentExercise.logs.push({
                    id: data.log_id,
                    session_id: this.sessionId,
                    exercise_id: this.currentExercise.exercise_id,
                    set_number: setNumber,
                    set_type: isDuration ? 'duration' : 'reps',
                    actual_weight: submittedWeight,
                    actual_reps: isDuration ? null : this.actualReps,
                    duration_seconds: isDuration ? this.actualReps : null,
                    rir_bucket: this.rirBucket,
                    form_breakdown: this.formBreakdown ? 1 : 0
                });

                // 2. Calculate weight difference
                const nextWeight = parseFloat(data.next_weight);
                const diff = (nextWeight - submittedWeight).toFixed(1);

                // 3. Update active exercise reactive state
                this.currentExercise.current_weight = nextWeight;
                this.currentExercise.plate_recipe = data.plate_recipe;
                this.currentExercise.completed_sets = this.currentExercise.logs.length;
                this.currentExercise.next_set_number = this.currentExercise.completed_sets + 1;
                this.currentExercise.last_engine_result = data;

                // 4. Construct high-visibility adaptation banner
                let diffBadge = '';
                if (parseFloat(diff) > 0) {
                    diffBadge = `(+${diff} kg artış)`;
                } else if (parseFloat(diff) < 0) {
                    diffBadge = `(${diff} kg düşüş)`;
                } else {
                    diffBadge = `(Ağırlık korundu)`;
                }

                this.adaptationBanner = {
                    show: true,
                    title: `⚡ Set ${setNumber} Tamamlandı — Canlı Adaptasyon Uygulandı!`,
                    message: `Sonraki setler hemen ${submittedWeight} kg → ${nextWeight} kg ${diffBadge} olarak güncellendi.`,
                    direction: data.direction
                };

                // Auto-hide adaptation banner after 7 seconds
                setTimeout(() => {
                    if (this.adaptationBanner) {
                        this.adaptationBanner.show = false;
                    }
                }, 7000);

                // 5. Reset inputs for the next set
                this.actualReps = this.currentExercise.target_reps_min || 10;
                this.rirBucket = '1-2';
                this.formBreakdown = false;

                // 6. Start rest timer (default 90s + extension)
                let restDuration = 90;
                if (data.rest_extension_seconds > 0) {
                    restDuration += data.rest_extension_seconds;
                    this.timerNotice = 'RIR 0 toparlanması (+30s dinlenme eklendi)';
                } else {
                    this.timerNotice = 'Standart hipertrofi toparlanma süresi';
                }
                this.startTimer(restDuration);

            } catch (err) {
                console.error(err);
                alert('Sunucuyla iletişim kurulamadı.');
            } finally {
                this.isSubmitting = false;
            }
        },

        startTimer(seconds) {
            clearInterval(this.timerInterval);
            this.timerRemaining = seconds;
            this.timerRunning = true;

            this.timerInterval = setInterval(() => {
                if (this.timerRemaining > 0) {
                    this.timerRemaining--;
                } else {
                    this.timerRunning = false;
                    clearInterval(this.timerInterval);
                    if ('vibrate' in navigator) {
                        navigator.vibrate([200, 100, 200]);
                    }
                }
            }, 1000);
        },

        toggleTimer() {
            if (this.timerRunning) {
                clearInterval(this.timerInterval);
                this.timerRunning = false;
            } else if (this.timerRemaining > 0) {
                this.startTimer(this.timerRemaining);
            }
        },

        adjustTimer(sec) {
            this.timerRemaining += sec;
        },

        formatTime(seconds) {
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            return `${m}:${s < 10 ? '0' : ''}${s}`;
        }
    };
}
document.addEventListener('alpine:init', () => {
    Alpine.data('liveWorkout', liveWorkout);
});
</script>

<div x-data="liveWorkout(window.__LIVE_WORKOUT_CONFIG__)" class="max-w-2xl mx-auto space-y-5 pb-24">

    <!-- Top Session Header & Timer Status -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-5 shadow-xl backdrop-blur-xl flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Canlı Oturum #<?= $sessionId ?></span>
            </div>
            <h1 class="text-lg font-bold font-display text-gradient">
                <?= Security::escape($dayInfo['focus_area'] ?? 'Genel Hipertrofi Oturumu') ?>
            </h1>
        </div>

        <!-- End Workout Form -->
        <form action="/workout/end" method="POST" @submit="return confirm('Antrenmanı tamamlamak istediğinize emin misiniz? Tüm set ve hacim verileriniz kaydedilecek.');">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="session_id" value="<?= $sessionId ?>">
            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-rose-500/20 text-slate-300 hover:text-rose-400 border border-slate-700 hover:border-rose-500/30 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                <i class="fa-solid fa-flag-checkered text-xs"></i>
                <span>Bitir</span>
            </button>
        </form>
    </div>

    <!-- Program Day Switcher Bar -->
    <?php if (!empty($allProgramDays)): ?>
    <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
        <?php foreach ($allProgramDays as $idx => $pd): ?>
            <?php $isActiveDay = ($pd['id'] == $currentDayId); ?>
            <a href="/workout/live?day=<?= (int)$pd['id'] ?>" 
               class="px-4 py-2.5 rounded-2xl text-xs font-bold whitespace-nowrap transition-all flex items-center gap-2 <?= $isActiveDay ? 'bg-gradient-to-r from-brand-600 to-indigo-600 text-white shadow-lg shadow-brand-600/30 ring-1 ring-brand-400' : 'bg-slate-900/80 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800' ?>">
                <i class="fa-solid fa-calendar-day <?= $isActiveDay ? 'text-brand-200' : 'text-slate-500' ?>"></i>
                <span><?= ($idx + 1) ?>. Gün: <?= Security::escape($pd['focus_area']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Active Rest Countdown Timer Banner (Auto-expands on set log) -->
    <div x-show="timerRunning || timerRemaining > 0" x-transition.duration.300ms 
        class="p-4 rounded-2xl bg-gradient-to-r from-indigo-950/90 to-brand-950/90 border border-brand-500/40 shadow-xl flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-brand-600/30 text-brand-300 border border-brand-500/30 flex items-center justify-center text-xl font-bold font-mono">
                <span x-text="formatTime(timerRemaining)"></span>
            </div>
            <div>
                <span class="text-xs font-bold text-white block">Dinlenme Sayacı</span>
                <span class="text-[11px] text-slate-400" x-text="timerNotice"></span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="adjustTimer(30)" class="px-2.5 py-1.5 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-xs font-bold text-slate-300 border border-slate-700">
                +30s
            </button>
            <button type="button" @click="toggleTimer()" class="w-9 h-9 rounded-lg bg-brand-600 text-white flex items-center justify-center shadow-md">
                <i :class="timerRunning ? 'fa-solid fa-pause text-xs' : 'fa-solid fa-play text-xs'"></i>
            </button>
        </div>
    </div>

    <!-- Live Adaptation Toast Notification -->
    <div x-show="adaptationBanner?.show" x-transition.duration.300ms class="p-4 rounded-2xl shadow-xl flex items-center gap-3 border"
        :class="adaptationBanner?.direction === 'DECREASE' ? 'bg-rose-950/90 border-rose-500/40 text-rose-200' : (adaptationBanner?.direction === 'INCREASE' ? 'bg-indigo-950/90 border-indigo-500/40 text-indigo-200' : 'bg-emerald-950/90 border-emerald-500/40 text-emerald-200')">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
            :class="adaptationBanner?.direction === 'DECREASE' ? 'bg-rose-500/20 text-rose-300' : (adaptationBanner?.direction === 'INCREASE' ? 'bg-indigo-500/20 text-indigo-300' : 'bg-emerald-500/20 text-emerald-300')">
            <i :class="adaptationBanner?.direction === 'DECREASE' ? 'fa-solid fa-arrow-trend-down' : (adaptationBanner?.direction === 'INCREASE' ? 'fa-solid fa-arrow-trend-up' : 'fa-solid fa-check')"></i>
        </div>
        <div class="flex-1">
            <span class="text-xs font-black uppercase tracking-wider block" x-text="adaptationBanner?.title"></span>
            <p class="text-xs mt-0.5 leading-relaxed" x-text="adaptationBanner?.message"></p>
        </div>
        <button type="button" @click="adaptationBanner.show = false" class="text-slate-400 hover:text-white p-1 text-xs">✕</button>
    </div>

    <!-- Empty Exercises State Fallback -->
    <div x-show="!currentExercise || exercises.length === 0" class="bg-slate-900/90 border border-slate-800 rounded-3xl p-8 text-center space-y-4">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-2xl">
            <i class="fa-solid fa-mug-hot"></i>
        </div>
        <div>
            <h3 class="text-lg font-bold text-white mb-1">Bu Güne Ait Egzersiz Bulunamadı</h3>
            <p class="text-sm text-slate-400 max-w-md mx-auto">Seçilen gün için kayıtlı egzersiz bulunmuyor veya dinlenme günü. Yukarıdaki gün sekmelerinden başka bir güne geçebilir veya antrenman paneline dönebilirsiniz.</p>
        </div>
        <div class="flex items-center justify-center gap-3 pt-2">
            <a href="/workout/dashboard" class="px-5 py-2.5 rounded-xl btn-sporty px-5 py-2.5 text-xs">
                <i class="fa-solid fa-arrow-left mr-2"></i> Panele Dön
            </a>
        </div>
    </div>

    <!-- Exercise Steps Carousel / Quick Select -->
    <div x-show="exercises.length > 0" class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
        <template x-for="(ex, idx) in exercises" :key="ex.exercise_id">
            <button type="button" @click="currentExerciseIndex = idx; syncInputs();"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border shrink-0 flex items-center gap-1.5"
                :class="currentExerciseIndex === idx ? 'bg-slate-800 border-brand-500 text-white shadow-md ring-1 ring-brand-500/40' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:text-slate-300'">
                <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]"
                      :class="ex.completed_sets >= ex.target_sets ? 'bg-emerald-500 text-slate-950 font-black' : (currentExerciseIndex === idx ? 'bg-brand-500 text-white' : 'bg-slate-800 text-slate-400')"
                      x-text="ex.completed_sets >= ex.target_sets ? '✓' : (idx + 1)"></span>
                <span class="truncate max-w-[130px]" x-text="ex.exercise_name"></span>
            </button>
        </template>
    </div>

    <!-- Main Active Exercise Card -->
    <div x-show="currentExercise" class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-2xl backdrop-blur-xl relative overflow-hidden space-y-6">
        <!-- Glow background -->
        <div class="absolute -top-20 -right-20 w-48 h-48 bg-brand-600/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Exercise Navigation & Order -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-brand-600/10 text-brand-400 border border-brand-500/20">
                    Egzersiz <span x-text="currentExerciseIndex + 1"></span> / <span x-text="exercises.length"></span>
                </span>
                <span class="text-xs text-slate-400 capitalize" x-text="currentExercise?.primary_muscle_group"></span>
            </div>

            <div class="flex items-center gap-1.5">
                <button type="button" @click="prevExercise()" :disabled="currentExerciseIndex === 0"
                    class="w-8 h-8 rounded-lg bg-slate-800 text-slate-300 disabled:opacity-30 flex items-center justify-center hover:bg-slate-700 transition-colors">
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </button>
                <button type="button" @click="nextExercise()" :disabled="currentExerciseIndex === exercises.length - 1"
                    class="w-8 h-8 rounded-lg bg-slate-800 text-slate-300 disabled:opacity-30 flex items-center justify-center hover:bg-slate-700 transition-colors">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
            </div>
        </div>

        <!-- Exercise Title -->
        <div>
            <h2 class="text-2xl font-black font-display text-white tracking-tight" x-text="currentExercise?.exercise_name"></h2>
            <div class="flex items-center gap-2 mt-1 text-xs text-slate-400">
                <span>Hedef: <strong class="text-white" x-text="currentExercise?.target_sets"></strong> Set</span>
                <span>•</span>
                <span>Tekrar Aralığı: <strong class="text-white" x-text="currentExercise?.target_reps_min + '-' + currentExercise?.target_reps_max"></strong></span>
                <span>•</span>
                <span class="text-brand-400 font-semibold uppercase text-[10px]" x-text="currentExercise?.required_equipment"></span>
            </div>
        </div>

        <!-- Plate Solver Recipe Card -->
        <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 flex items-start gap-3 shadow-inner">
            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-brand-400 flex items-center justify-center shrink-0 mt-0.5">
                <i class="fa-solid fa-layer-group text-base"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Canlı Plaka & Bar Dizilimi</span>
                    <span class="text-[10px] font-bold text-indigo-400" x-text="(currentExercise?.current_weight || 0) + ' kg için'"></span>
                </div>
                <p class="text-sm font-bold text-indigo-300 truncate mt-0.5" x-text="currentExercise?.plate_recipe || 'Vücut Ağırlığı / Standart Ekipman'"></p>
            </div>
        </div>

        <!-- Set Progression Matrix (Set-by-Set Dynamic List) -->
        <div class="space-y-2.5">
            <div class="flex items-center justify-between text-xs font-bold text-slate-400 uppercase tracking-wider px-1">
                <span class="flex items-center gap-1.5">
                    <i class="fa-solid fa-list-check text-brand-400"></i>
                    <span>Set İlerlemesi & Canlı Yük Dağılımı</span>
                </span>
                <span class="text-indigo-400 font-mono" x-text="(currentExercise?.completed_sets || 0) + ' / ' + (currentExercise?.target_sets || 3) + ' Set'"></span>
            </div>

            <div class="space-y-2">
                <template x-for="setIdx in getTargetSetsArray()" :key="setIdx">
                    <div class="p-3.5 rounded-2xl border transition-all flex items-center justify-between gap-3"
                        :class="getSetCardClass(setIdx)">
                        
                        <!-- Left: Set Number & Details -->
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs shrink-0"
                                :class="setIdx <= (currentExercise?.completed_sets || 0) ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : (setIdx === currentExercise?.next_set_number && (currentExercise?.completed_sets || 0) < (currentExercise?.target_sets || 3) ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/40 ring-2 ring-brand-400/50' : 'bg-slate-900 text-slate-600 border border-slate-800')">
                                <span x-show="setIdx <= (currentExercise?.completed_sets || 0)"><i class="fa-solid fa-check text-xs"></i></span>
                                <span x-show="setIdx > (currentExercise?.completed_sets || 0)" x-text="setIdx"></span>
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-bold text-white" x-text="'Set ' + setIdx"></span>
                                    
                                    <!-- Completed Badge -->
                                    <template x-if="setIdx <= (currentExercise?.completed_sets || 0)">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Tamamlandı ✓</span>
                                    </template>

                                    <!-- Active Badge -->
                                    <template x-if="setIdx === currentExercise?.next_set_number && (currentExercise?.completed_sets || 0) < (currentExercise?.target_sets || 3)">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-brand-500/20 text-brand-300 border border-brand-500/30 animate-pulse">Aktif Set ⚡</span>
                                    </template>

                                    <!-- Upcoming Badge -->
                                    <template x-if="setIdx > (currentExercise?.next_set_number || 1)">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-slate-900 text-slate-500 border border-slate-800">Sırada ⏳</span>
                                    </template>
                                </div>

                                <!-- Details for completed set -->
                                <template x-if="getLogForSet(setIdx)">
                                    <div class="text-[11px] text-slate-300 mt-0.5 truncate">
                                        <span>Yapılan: <strong class="text-white" x-text="getLogForSet(setIdx).actual_weight + ' kg × ' + (getLogForSet(setIdx).actual_reps || getLogForSet(setIdx).duration_seconds || '0') + (currentExercise?.movement_pattern === 'core' ? ' sn' : ' tekrar')"></strong></span>
                                        <span class="text-slate-500 mx-1">•</span>
                                        <span class="text-indigo-300 font-bold" x-text="'RIR ' + getLogForSet(setIdx).rir_bucket"></span>
                                        <template x-if="getLogForSet(setIdx).form_breakdown == 1">
                                            <span class="text-amber-400 font-bold ml-1">⚠️ Form Bozuldu</span>
                                        </template>
                                    </div>
                                </template>

                                <!-- Details for active set -->
                                <template x-if="!getLogForSet(setIdx) && setIdx === currentExercise?.next_set_number">
                                    <div class="text-[11px] text-brand-300 mt-0.5">
                                        <span>Önerilen Yük: <strong class="text-white" x-text="(currentExercise?.current_weight || 0) + ' kg'"></strong></span>
                                        <span class="text-slate-500 mx-1">•</span>
                                        <span>Hedef: <strong class="text-slate-200" x-text="currentExercise?.target_reps_min + '-' + currentExercise?.target_reps_max + ' tekrar'"></strong></span>
                                    </div>
                                </template>

                                <!-- Details for pending upcoming set -->
                                <template x-if="!getLogForSet(setIdx) && setIdx > (currentExercise?.next_set_number || 1)">
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        <span>Beklenen Ağırlık: <strong class="text-indigo-300" x-text="(currentExercise?.current_weight || 0) + ' kg'"></strong></span>
                                        <span class="text-slate-500 mx-1">•</span>
                                        <span class="text-[10px] text-slate-500">(Canlı adapte edilir)</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Right: Weight Indicator -->
                        <div class="text-right shrink-0">
                            <template x-if="getLogForSet(setIdx)">
                                <span class="text-sm font-black font-mono text-emerald-400" x-text="getLogForSet(setIdx).actual_weight + ' kg'"></span>
                            </template>
                            <template x-if="!getLogForSet(setIdx)">
                                <div class="flex flex-col items-end">
                                    <span class="text-sm font-black font-mono" :class="setIdx === currentExercise?.next_set_number ? 'text-white' : 'text-slate-500'" x-text="(currentExercise?.current_weight || 0) + ' kg'"></span>
                                    <span class="text-[9px] uppercase tracking-wider font-bold" :class="setIdx === currentExercise?.next_set_number ? 'text-brand-400' : 'text-slate-600'">Öneri</span>
                                </div>
                            </template>
                        </div>

                    </div>
                </template>
            </div>
        </div>

        <!-- STATE A: Active Set Input Form (When there are sets remaining) -->
        <div x-show="(currentExercise?.completed_sets || 0) < (currentExercise?.target_sets || 3)" class="p-5 rounded-2xl bg-slate-950/90 border border-slate-800 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                <span class="text-sm font-bold text-white flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-brand-500 animate-ping"></span>
                    <span>Set <strong class="text-brand-400" x-text="currentExercise?.next_set_number || 1"></strong> Girişi</span>
                </span>
                <span class="text-xs text-slate-400">
                    Hedef: <strong class="text-white font-mono" x-text="currentExercise?.target_reps_min + '-' + currentExercise?.target_reps_max"></strong> tekrar
                </span>
            </div>

            <!-- Weight and Rep Inputs -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Weight Input -->
                <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800">
                    <label for="weight_input" class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ağırlık (kg)</label>
                    <div class="flex items-center justify-between gap-1">
                        <button type="button" aria-label="Ağırlığı 2.5 kg azalt" @click="currentExercise.current_weight = Math.max(0, (parseFloat(currentExercise.current_weight || 0) - 2.5).toFixed(1))"
                            class="w-9 h-9 rounded-xl bg-slate-800 text-slate-200 active:bg-slate-700 font-bold text-lg flex items-center justify-center hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors">-</button>
                        <input type="number" id="weight_input" step="0.5" x-model.number="currentExercise.current_weight"
                            class="w-full bg-transparent text-center text-xl font-black text-white focus:outline-none focus:ring-2 focus:ring-brand-500 rounded-md">
                        <button type="button" aria-label="Ağırlığı 2.5 kg artır" @click="currentExercise.current_weight = (parseFloat(currentExercise.current_weight || 0) + 2.5).toFixed(1)"
                            class="w-9 h-9 rounded-xl bg-slate-800 text-slate-200 active:bg-slate-700 font-bold text-lg flex items-center justify-center hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors">+</button>
                    </div>
                </div>

                <!-- Reps Input -->
                <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800">
                    <label for="reps_input" class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                        <span x-text="currentExercise?.movement_pattern === 'core' ? 'Süre (sn)' : 'Yapılan Tekrar'"></span>
                    </label>
                    <div class="flex items-center justify-between gap-1">
                        <button type="button" aria-label="Tekrar sayısını 1 azalt" @click="actualReps = Math.max(0, actualReps - 1)"
                            class="w-9 h-9 rounded-xl bg-slate-800 text-slate-200 active:bg-slate-700 font-bold text-lg flex items-center justify-center hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors">-</button>
                        <input type="number" id="reps_input" x-model.number="actualReps"
                            class="w-full bg-transparent text-center text-xl font-black text-white focus:outline-none focus:ring-2 focus:ring-brand-500 rounded-md">
                        <button type="button" aria-label="Tekrar sayısını 1 artır" @click="actualReps++"
                            class="w-9 h-9 rounded-xl bg-slate-800 text-slate-200 active:bg-slate-700 font-bold text-lg flex items-center justify-center hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors">+</button>
                    </div>
                </div>
            </div>

            <!-- 3 One-Tap RIR Buttons -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Tek Otorite RIR (Cepte Kalan Tekrar)
                </label>
                <div class="grid grid-cols-3 gap-2">
                    <!-- RIR 0: Failure -->
                    <button type="button" @click="rirBucket = '0'"
                        :class="rirBucket === '0' ? 'bg-rose-600 text-white border-rose-500 shadow-lg shadow-rose-600/30' : 'bg-slate-900 text-slate-300 border-slate-800 hover:border-slate-700'"
                        class="py-3 px-2 rounded-2xl border text-center transition-all flex flex-col items-center justify-center">
                        <span class="text-sm font-black">Tükeniş (0)</span>
                        <span class="text-[10px] opacity-80 mt-0.5">1 tekrar daha çıkmaz</span>
                    </button>

                    <!-- RIR 1-2: Ideal Hypertrophy -->
                    <button type="button" @click="rirBucket = '1-2'"
                        :class="rirBucket === '1-2' ? 'bg-emerald-600 text-white border-emerald-500 shadow-lg shadow-emerald-600/30' : 'bg-slate-900 text-slate-300 border-slate-800 hover:border-slate-700'"
                        class="py-3 px-2 rounded-2xl border text-center transition-all flex flex-col items-center justify-center">
                        <span class="text-sm font-black">İdeal (1-2)</span>
                        <span class="text-[10px] opacity-80 mt-0.5">Hipertrofi Penceresi</span>
                    </button>

                    <!-- RIR 3+: Too Easy -->
                    <button type="button" @click="rirBucket = '3+'"
                        :class="rirBucket === '3+' ? 'bg-indigo-600 text-white border-indigo-500 shadow-lg shadow-indigo-600/30' : 'bg-slate-900 text-slate-300 border-slate-800 hover:border-slate-700'"
                        class="py-3 px-2 rounded-2xl border text-center transition-all flex flex-col items-center justify-center">
                        <span class="text-sm font-black">Kolay (3+)</span>
                        <span class="text-[10px] opacity-80 mt-0.5">Yük yetersiz, artır</span>
                    </button>
                </div>
            </div>

            <!-- One-Tap Form Breakdown Switch -->
            <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-amber-400 text-lg"></i>
                    <div>
                        <span id="form_breakdown_label" class="text-xs font-bold text-white block">Form Bozuldu mu?</span>
                        <span id="form_breakdown_desc" class="text-[10px] text-slate-400">Momentum kullanıldıysa veya eklem açısı bozulduysa işaretleyin (-%15 emniyet).</span>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="formBreakdown" aria-labelledby="form_breakdown_label" aria-describedby="form_breakdown_desc" class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-amber-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                </label>
            </div>

            <!-- Submit Set Button -->
            <button type="button" @click="submitSet()" :disabled="isSubmitting"
                class="w-full py-4 px-6 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white font-black text-base rounded-2xl shadow-xl shadow-brand-600/30 active:scale-[0.98] transition-all flex items-center justify-center gap-3 disabled:opacity-50">
                <span x-show="!isSubmitting">Set <span x-text="currentExercise?.next_set_number || 1"></span>'i Tamamla & Sonraki Setleri Güncelle</span>
                <span x-show="isSubmitting">Hipertrofi Motoru Hesaplanıyor (< 0.05s)...</span>
                <i class="fa-solid fa-bolt" x-show="!isSubmitting"></i>
            </button>
        </div>

        <!-- STATE B: Exercise Completed Celebration Card (When all target sets are done) -->
        <div x-show="(currentExercise?.completed_sets || 0) >= (currentExercise?.target_sets || 3)" class="p-6 rounded-2xl bg-gradient-to-br from-emerald-950/70 via-slate-900 to-slate-950 border border-emerald-500/40 text-center space-y-4 shadow-xl">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-3xl shadow-lg shadow-emerald-500/20 animate-bounce">
                <i class="fa-solid fa-trophy"></i>
            </div>
            <div>
                <h3 class="text-xl font-black text-white font-display">Tebrikler! Egzersiz Tamamlandı</h3>
                <p class="text-xs text-slate-300 mt-1 max-w-md mx-auto">
                    <strong class="text-emerald-400" x-text="currentExercise?.exercise_name"></strong> için <span x-text="currentExercise?.target_sets"></span>/<span x-text="currentExercise?.target_sets"></span> set başarıyla tamamlandı ve dinamik hipertrofi adaptasyonları uygulandı.
                </p>
            </div>

            <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                <template x-if="currentExerciseIndex < exercises.length - 1">
                    <button type="button" @click="nextExercise()"
                        class="w-full sm:w-auto px-6 py-3.5 bg-gradient-to-r from-emerald-600 to-brand-600 hover:from-emerald-500 hover:to-brand-500 text-white font-black text-sm rounded-xl shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2 transition-all">
                        <span>Sonraki Egzersize Geç</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </template>

                <template x-if="currentExerciseIndex >= exercises.length - 1">
                    <div class="text-center w-full">
                        <span class="text-xs font-bold text-emerald-400 block mb-2">🎉 Bugünkü antrenmanın tüm egzersizleri başarıyla bitti!</span>
                        <button type="button" @click="document.querySelector('form[action=\'/workout/end\']').submit()"
                            class="w-full sm:w-auto px-8 py-3.5 bg-gradient-to-r from-brand-600 to-indigo-600 text-white font-black text-sm rounded-xl shadow-lg shadow-brand-600/30 flex items-center justify-center gap-2 mx-auto">
                            <i class="fa-solid fa-flag-checkered"></i>
                            <span>Antrenmanı Bitir & Hacim Özetini İncele</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Engine Decision Feedback Alert -->
        <div x-show="currentExercise?.last_engine_result" x-transition.duration.300ms class="p-4 rounded-2xl border"
            :class="currentExercise?.last_engine_result?.direction === 'DECREASE' ? 'bg-rose-950/60 border-rose-500/30 text-rose-200' : 
                    (currentExercise?.last_engine_result?.direction === 'INCREASE' ? 'bg-indigo-950/60 border-indigo-500/30 text-indigo-200' : 'bg-emerald-950/60 border-emerald-500/30 text-emerald-200')">
            <div class="flex items-start gap-3">
                <i class="mt-0.5 text-base" :class="currentExercise?.last_engine_result?.direction === 'DECREASE' ? 'fa-solid fa-arrow-trend-down text-rose-400' : (currentExercise?.last_engine_result?.direction === 'INCREASE' ? 'fa-solid fa-arrow-trend-up text-indigo-400' : 'fa-solid fa-circle-check text-emerald-400')"></i>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider mb-0.5 flex items-center gap-2">
                        <span>Son Hesaplanan Öneri:</span>
                        <span class="px-2 py-0.5 rounded font-black font-mono text-white bg-slate-900 border border-slate-700" x-text="(currentExercise?.last_engine_result?.next_weight || 0) + ' kg'"></span>
                    </div>
                    <p class="text-xs leading-relaxed" x-text="currentExercise?.last_engine_result?.reason"></p>
                </div>
            </div>
        </div>
    </div>

</div>


