<?php
declare(strict_types=1);
require_once __DIR__ . '/../layouts/header.php';
use App\Core\Security;

$exercisesJson = json_encode($exercises, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$injuriesJson = json_encode($activeInjuries, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<div class="max-w-6xl mx-auto space-y-6" x-data="exerciseLibrary(<?= htmlspecialchars($exercisesJson, ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars($injuriesJson, ENT_QUOTES, 'UTF-8') ?>)">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 bg-slate-900 p-6 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-brand-500/20 text-brand-400 flex items-center justify-center border border-brand-500/30">
                    <i class="fa-solid fa-book-bookmark text-lg"></i>
                </div>
                <h1 class="text-2xl font-black font-display text-white tracking-tight">Egzersiz Kütüphanesi & Biyomekanik Rehber</h1>
            </div>
            <p class="text-sm text-slate-400 max-w-2xl">Kas grubu, ekipman, zorluk seviyesi ve eklem stres analizine göre filtrelenebilir eksiksiz egzersiz kataloğu.</p>
        </div>

        <div class="relative z-10 flex flex-wrap items-center gap-2">
            <!-- Active Injury Warning Pill (if user has active injuries) -->
            <template x-if="activeInjuries.length > 0">
                <div class="px-3.5 py-1.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs font-bold flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-rose-400"></i>
                    <span>Aktif Sakatlık: <b x-text="formatJoints(activeInjuries)"></b></span>
                </div>
            </template>
            <div class="px-3.5 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-400 text-xs font-mono font-bold">
                <span class="text-white" x-text="filteredExercises.length"></span> / <span x-text="exercises.length"></span> Hareket
            </div>
        </div>
    </div>

    <!-- Filter & Search Controls Bar -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            <!-- Search Input -->
            <div class="md:col-span-5 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="Egzersiz adı veya kas grubu ara..." 
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-11 pr-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 transition-colors">
            </div>

            <!-- Movement Pattern Dropdown -->
            <div class="md:col-span-3">
                <select 
                    x-model="selectedPattern" 
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-xs font-semibold text-slate-200 focus:outline-none focus:border-brand-500 transition-colors cursor-pointer">
                    <option value="all">Tüm Hareket Desenleri</option>
                    <option value="push_horizontal">Göğüs / Yatay İtiş</option>
                    <option value="push_vertical">Omuz / Dikey İtiş</option>
                    <option value="pull_horizontal">Sırt / Yatay Çekiş</option>
                    <option value="pull_vertical">Sırt / Dikey Çekiş</option>
                    <option value="squat">Bacak / Squat</option>
                    <option value="hinge">Arka Bacak / Deadlift-Hinge</option>
                    <option value="isolation_arm">Kol / Biceps & Triceps</option>
                    <option value="isolation_shoulder">Omuz / İzole</option>
                    <option value="core">Karın & Merkez (Core)</option>
                </select>
            </div>

            <!-- Equipment Filter -->
            <div class="md:col-span-2">
                <select 
                    x-model="selectedEquipment" 
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-xs font-semibold text-slate-200 focus:outline-none focus:border-brand-500 transition-colors cursor-pointer">
                    <option value="all">Tüm Ekipmanlar</option>
                    <option value="barbell">Halter (Barbell)</option>
                    <option value="dumbbell">Dambıl</option>
                    <option value="bodyweight">Vücut Ağırlığı</option>
                    <option value="machine">Makine</option>
                    <option value="cable">Kablo</option>
                </select>
            </div>

            <!-- Difficulty Filter -->
            <div class="md:col-span-2">
                <select 
                    x-model="selectedDifficulty" 
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-xs font-semibold text-slate-200 focus:outline-none focus:border-brand-500 transition-colors cursor-pointer">
                    <option value="all">Tüm Seviyeler</option>
                    <option value="absolute_beginner">Sıfırdan Başlayan</option>
                    <option value="beginner">Başlangıç</option>
                    <option value="intermediate">Orta Seviye</option>
                    <option value="advanced">İleri Seviye</option>
                </select>
            </div>
        </div>

        <!-- Quick Filter Tags & Injury Toggle -->
        <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-800/80">
            <!-- Muscle Quick Filter Pills -->
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[11px] font-bold text-slate-500 mr-1 uppercase">Bölge:</span>
                <template x-for="m in ['all', 'chest', 'back', 'legs', 'shoulders', 'arms', 'core']" :key="m">
                    <button 
                        type="button" 
                        @click="selectedMuscle = m" 
                        class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all"
                        :class="selectedMuscle === m 
                            ? 'bg-brand-600 text-white border-brand-500 shadow-sm' 
                            : 'bg-slate-950 text-slate-400 border-slate-800 hover:text-white hover:border-slate-700'"
                        x-text="translateMuscle(m)">
                    </button>
                </template>
            </div>

            <!-- Hide Injured Exercises Toggle (only if user has active injuries) -->
            <template x-if="activeInjuries.length > 0">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-300 select-none bg-slate-950 px-3 py-1.5 rounded-xl border border-slate-800 hover:border-slate-700 transition-colors">
                    <input type="checkbox" x-model="hideHarmful" class="rounded border-slate-700 text-rose-600 focus:ring-rose-500 bg-slate-900 cursor-pointer">
                    <span>Sakatlığımla Çakışanları Gizle</span>
                </label>
            </template>
        </div>
    </div>

    <!-- Exercise Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <template x-for="ex in filteredExercises" :key="ex.id">
            <div class="bg-slate-900 border rounded-3xl p-5 shadow-xl transition-all duration-200 hover:-translate-y-1 flex flex-col justify-between"
                 :class="hasInjuryConflict(ex) 
                     ? 'border-rose-500/40 bg-gradient-to-b from-slate-900 to-rose-950/20' 
                     : 'border-slate-800 hover:border-slate-700'">
                
                <div>
                    <!-- Top Badges Row -->
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider"
                              :class="getPatternBadgeClass(ex.movement_pattern)"
                              x-text="translatePattern(ex.movement_pattern)"></span>

                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold font-mono bg-slate-950 text-slate-400 border border-slate-800"
                              x-text="translateDifficulty(ex.difficulty_level)"></span>
                    </div>

                    <!-- Title -->
                    <h3 class="text-base font-bold text-white mb-2 leading-snug" x-text="ex.name"></h3>

                    <!-- Injury Conflict High-Visibility Banner -->
                    <template x-if="hasInjuryConflict(ex)">
                        <div class="mb-3 p-2.5 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-200 text-xs flex items-start gap-2">
                            <i class="fa-solid fa-triangle-exclamation text-rose-400 text-sm mt-0.5 shrink-0"></i>
                            <div>
                                <span class="font-bold block">Sakatlık Riski Mevcut</span>
                                <span class="text-[11px] text-rose-300" x-text="'Aktif sakatlığınız (' + getConflictingJoints(ex) + ') bu egzersizin yük binen eklemiyle çakışıyor.'"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Details Table -->
                    <div class="space-y-1.5 text-xs text-slate-400 mb-4 bg-slate-950/60 p-3 rounded-2xl border border-slate-800/80">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Hedef Kas:</span>
                            <span class="font-bold text-slate-200" x-text="translateMuscle(ex.primary_muscle_group)"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Gereken Ekipman:</span>
                            <span class="font-bold text-slate-200 flex items-center gap-1.5">
                                <i :class="getEquipmentIcon(ex.required_equipment)" class="text-brand-400"></i>
                                <span x-text="translateEquipment(ex.required_equipment)"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Footer Stress Points -->
                <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px]">
                    <span class="text-slate-500 font-medium">Binen Eklem Stresi:</span>
                    <div class="flex flex-wrap gap-1 justify-end">
                        <template x-for="j in getStressedJointsList(ex)" :key="j">
                            <span class="px-2 py-0.5 rounded-md font-bold text-[10px]"
                                  :class="isJointInjured(j) ? 'bg-rose-500/30 text-rose-200 border border-rose-500/50' : 'bg-slate-800 text-slate-400'"
                                  x-text="translateJoint(j)"></span>
                        </template>
                        <template x-if="getStressedJointsList(ex).length === 0">
                            <span class="text-slate-600 italic">Düşük Stres</span>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="filteredExercises.length === 0" class="text-center py-16 bg-slate-900 border border-slate-800 rounded-3xl p-6">
        <i class="fa-solid fa-filter-circle-xmark text-4xl text-slate-600 mb-3"></i>
        <h3 class="text-base font-bold text-white mb-1">Aramanıza Uygun Egzersiz Bulunamadı</h3>
        <p class="text-xs text-slate-400 max-w-sm mx-auto mb-4">Arama kriterlerinizi veya sakatlık filtresini gevşeterek tekrar deneyin.</p>
        <button type="button" @click="resetFilters()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold border border-slate-700">
            Filtreleri Temizle
        </button>
    </div>
</div>

<script>
function exerciseLibrary(exercisesData, activeInjuriesData) {
    return {
        exercises: exercisesData || [],
        activeInjuries: activeInjuriesData || [],
        searchQuery: '',
        selectedPattern: 'all',
        selectedEquipment: 'all',
        selectedDifficulty: 'all',
        selectedMuscle: 'all',
        hideHarmful: false,

        get filteredExercises() {
            return this.exercises.filter(ex => {
                // Search query
                if (this.searchQuery.trim() !== '') {
                    const q = this.searchQuery.toLowerCase();
                    const nameMatch = (ex.name || '').toLowerCase().includes(q);
                    const muscleMatch = this.translateMuscle(ex.primary_muscle_group).toLowerCase().includes(q);
                    if (!nameMatch && !muscleMatch) return false;
                }

                // Pattern
                if (this.selectedPattern !== 'all' && ex.movement_pattern !== this.selectedPattern) {
                    return false;
                }

                // Equipment
                if (this.selectedEquipment !== 'all' && ex.required_equipment !== this.selectedEquipment) {
                    return false;
                }

                // Difficulty
                if (this.selectedDifficulty !== 'all' && ex.difficulty_level !== this.selectedDifficulty) {
                    return false;
                }

                // Muscle
                if (this.selectedMuscle !== 'all' && ex.primary_muscle_group !== this.selectedMuscle) {
                    return false;
                }

                // Harmful toggle
                if (this.hideHarmful && this.hasInjuryConflict(ex)) {
                    return false;
                }

                return true;
            });
        },

        hasInjuryConflict(ex) {
            if (!this.activeInjuries || this.activeInjuries.length === 0) return false;
            const stresses = this.getStressedJointsList(ex);
            return stresses.some(j => this.activeInjuries.includes(j));
        },

        getConflictingJoints(ex) {
            const stresses = this.getStressedJointsList(ex);
            const conflicting = stresses.filter(j => this.activeInjuries.includes(j));
            return conflicting.map(j => this.translateJoint(j)).join(', ');
        },

        isJointInjured(j) {
            return this.activeInjuries.includes(j);
        },

        getStressedJointsList(ex) {
            const list = [];
            if (ex.joint_stresses) {
                ex.joint_stresses.split(',').forEach(s => {
                    const clean = s.trim();
                    if (clean && !list.includes(clean)) list.push(clean);
                });
            }
            if (ex.primary_joint_stress && !list.includes(ex.primary_joint_stress)) {
                list.push(ex.primary_joint_stress);
            }
            return list;
        },

        translatePattern(pat) {
            const map = {
                'push_horizontal': 'Göğüs / Yatay İtiş',
                'push_vertical': 'Omuz / Dikey İtiş',
                'pull_horizontal': 'Sırt / Yatay Çekiş',
                'pull_vertical': 'Sırt / Dikey Çekiş',
                'squat': 'Bacak / Squat',
                'hinge': 'Arka Bacak / Hinge',
                'isolation_arm': 'Kol İzole',
                'isolation_shoulder': 'Omuz İzole',
                'core': 'Core / Karın'
            };
            return map[pat] || pat;
        },

        getPatternBadgeClass(pat) {
            if (pat.startsWith('push')) return 'bg-rose-500/20 text-rose-300 border border-rose-500/30';
            if (pat.startsWith('pull')) return 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30';
            if (pat === 'squat' || pat === 'hinge') return 'bg-amber-500/20 text-amber-300 border border-amber-500/30';
            if (pat.includes('arm')) return 'bg-purple-500/20 text-purple-300 border border-purple-500/30';
            return 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30';
        },

        translateMuscle(m) {
            const map = {
                'all': 'Tümü',
                'chest': 'Göğüs',
                'back': 'Sırt',
                'legs': 'Bacak',
                'shoulders': 'Omuz',
                'arms': 'Kollar',
                'core': 'Karın / Core'
            };
            return map[m] || m;
        },

        translateEquipment(eq) {
            const map = {
                'barbell': 'Halter (Barbell)',
                'dumbbell': 'Dambıl',
                'bodyweight': 'Vücut Ağırlığı',
                'machine': 'Makine',
                'cable': 'Kablo'
            };
            return map[eq] || eq;
        },

        getEquipmentIcon(eq) {
            const map = {
                'barbell': 'fa-solid fa-dumbbell',
                'dumbbell': 'fa-solid fa-hand-fist',
                'bodyweight': 'fa-solid fa-person-running',
                'machine': 'fa-solid fa-gears',
                'cable': 'fa-solid fa-arrows-up-down'
            };
            return map[eq] || 'fa-solid fa-dumbbell';
        },

        translateDifficulty(d) {
            const map = {
                'absolute_beginner': 'Sıfırdan Başlayan',
                'beginner': 'Başlangıç',
                'intermediate': 'Orta Seviye',
                'advanced': 'İleri Seviye'
            };
            return map[d] || d;
        },

        translateJoint(j) {
            const map = {
                'shoulder': 'Omuz',
                'elbow': 'Dirsek',
                'lower_back': 'Bel / Alt Sırt',
                'knee': 'Diz',
                'wrist': 'Bilek',
                'neck': 'Boyun',
                'other': 'Diğer'
            };
            return map[j] || j;
        },

        formatJoints(list) {
            return list.map(j => this.translateJoint(j)).join(', ');
        },

        resetFilters() {
            this.searchQuery = '';
            this.selectedPattern = 'all';
            this.selectedEquipment = 'all';
            this.selectedDifficulty = 'all';
            this.selectedMuscle = 'all';
            this.hideHarmful = false;
        }
    };
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
