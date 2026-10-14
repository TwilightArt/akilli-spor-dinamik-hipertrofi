<?php
declare(strict_types=1);

/**
 * SQLite Local Development Bootstrapper
 * Auto-creates tables and seeds standard 50 exercises for zero-config local testing
 */

namespace App\Database;

use PDO;

class SqliteBootstrap
{
    public static function init(PDO $pdo): void
    {
        $pdo->exec("PRAGMA foreign_keys = ON;");

        // Create tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                gender TEXT NOT NULL,
                birth_date TEXT NOT NULL,
                height_cm REAL NOT NULL,
                current_weight_kg REAL DEFAULT 75.0,
                fitness_level TEXT DEFAULT 'beginner',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN current_weight_kg REAL DEFAULT 75.0;");
        } catch (\Throwable $e) {}

        try { $pdo->exec("ALTER TABLE users ADD COLUMN pushups_count INTEGER DEFAULT 0;"); } catch (\Throwable $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN chinups_count INTEGER DEFAULT 0;"); } catch (\Throwable $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN squats_count INTEGER DEFAULT 0;"); } catch (\Throwable $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN plank_seconds INTEGER DEFAULT 30;"); } catch (\Throwable $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN calisthenic_score INTEGER DEFAULT 0;"); } catch (\Throwable $e) {}

        $pdo->exec("

            CREATE TABLE IF NOT EXISTS login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address TEXT NOT NULL,
                email TEXT NULL,
                attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                success INTEGER DEFAULT 0
            );

            CREATE TABLE IF NOT EXISTS password_resets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL,
                token_hash TEXT NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS user_equipment (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                has_barbell INTEGER DEFAULT 0,
                has_dumbbells INTEGER DEFAULT 0,
                barbell_weight_kg REAL DEFAULT 20.00,
                dumbbell_handle_weight_kg REAL DEFAULT 2.50,
                available_plates TEXT NOT NULL,
                workout_location TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS exercises (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                movement_pattern TEXT NOT NULL,
                primary_muscle_group TEXT NOT NULL,
                primary_joint_stress TEXT NULL,
                required_equipment TEXT NOT NULL,
                is_regression_of INTEGER NULL,
                difficulty_level TEXT DEFAULT 'beginner',
                FOREIGN KEY (is_regression_of) REFERENCES exercises(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS exercise_joint_stress (
                exercise_id INTEGER NOT NULL,
                joint_area TEXT NOT NULL,
                stress_level TEXT DEFAULT 'primary',
                PRIMARY KEY (exercise_id, joint_area),
                FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS user_calibration_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                movement_pattern TEXT NOT NULL,
                base_coefficient REAL NOT NULL,
                adjusted_coefficient REAL NOT NULL,
                reason TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS injury_reports (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                joint_area TEXT NOT NULL,
                severity TEXT DEFAULT 'mild',
                reported_date TEXT NOT NULL,
                notes TEXT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS user_programs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                program_type TEXT NOT NULL,
                week_start_date TEXT NOT NULL,
                is_deload_week INTEGER DEFAULT 0,
                ai_generated_raw_json TEXT NULL,
                generation_source TEXT DEFAULT 'ai_generated',
                generation_retry_count INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS program_days (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                program_id INTEGER NOT NULL,
                day_of_week INTEGER NOT NULL,
                focus_area TEXT NOT NULL,
                FOREIGN KEY (program_id) REFERENCES user_programs(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS program_exercises (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                program_day_id INTEGER NOT NULL,
                exercise_id INTEGER NOT NULL,
                order_in_session INTEGER NOT NULL,
                target_sets INTEGER NOT NULL,
                target_reps_min INTEGER NOT NULL,
                target_reps_max INTEGER NOT NULL,
                FOREIGN KEY (program_day_id) REFERENCES program_days(id) ON DELETE CASCADE,
                FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE RESTRICT
            );

            CREATE TABLE IF NOT EXISTS workout_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                program_day_id INTEGER NULL,
                started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                ended_at DATETIME NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (program_day_id) REFERENCES program_days(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS daily_weigh_ins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                weight_kg REAL NOT NULL,
                logged_date TEXT NOT NULL,
                notes TEXT NULL,
                UNIQUE (user_id, logged_date),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS body_measurements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                measured_date TEXT NOT NULL,
                neck_cm REAL NULL,
                shoulder_cm REAL NULL,
                chest_cm REAL NULL,
                arm_cm REAL NULL,
                waist_cm REAL NULL,
                hip_cm REAL NULL,
                thigh_cm REAL NULL,
                calf_cm REAL NULL,
                body_fat_pct REAL NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS workout_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                session_id INTEGER NOT NULL,
                exercise_id INTEGER NOT NULL,
                set_number INTEGER NOT NULL,
                set_type TEXT DEFAULT 'reps',
                target_weight REAL NULL,
                actual_weight REAL NOT NULL,
                target_reps INTEGER NULL,
                actual_reps INTEGER NULL,
                duration_seconds INTEGER NULL,
                rpe INTEGER NULL,
                rir_bucket TEXT NOT NULL,
                form_breakdown INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (session_id) REFERENCES workout_sessions(id) ON DELETE CASCADE,
                FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE RESTRICT
            );
        ");

        // Check if seed data exists
        $cnt = (int)$pdo->query("SELECT COUNT(*) FROM exercises")->fetchColumn();
        if ($cnt === 0) {
            // Seed 50 exercises
            $pdo->exec("
                INSERT INTO exercises (id, name, movement_pattern, primary_muscle_group, primary_joint_stress, required_equipment, is_regression_of, difficulty_level) VALUES
                (1, 'Wall Push-Up', 'push_horizontal', 'chest', 'wrist', 'bodyweight', NULL, 'absolute_beginner'),
                (2, 'Incline Bench Knee Push-Up', 'push_horizontal', 'chest', 'wrist', 'bodyweight', 1, 'absolute_beginner'),
                (3, 'Knee Push-Up', 'push_horizontal', 'chest', 'wrist', 'bodyweight', 2, 'beginner'),
                (4, 'Standard Push-Up', 'push_horizontal', 'chest', 'shoulder', 'bodyweight', 3, 'beginner'),
                (5, 'Diamond Push-Up', 'push_horizontal', 'chest', 'elbow', 'bodyweight', 4, 'intermediate'),
                (6, 'Decline Push-Up', 'push_horizontal', 'chest', 'shoulder', 'bodyweight', 4, 'intermediate'),
                (7, 'Barbell Flat Bench Press', 'push_horizontal', 'chest', 'shoulder', 'barbell', 4, 'intermediate'),
                (8, 'Dumbbell Flat Bench Press', 'push_horizontal', 'chest', 'shoulder', 'dumbbell', 7, 'beginner'),
                (9, 'Dumbbell Incline Bench Press', 'push_horizontal', 'chest', 'shoulder', 'dumbbell', 8, 'intermediate'),
                (10, 'Machine Chest Press', 'push_horizontal', 'chest', 'elbow', 'machine', 8, 'beginner'),
                (11, 'Pike Push-Up with Knees', 'push_vertical', 'shoulders', 'wrist', 'bodyweight', NULL, 'absolute_beginner'),
                (12, 'Pike Push-Up', 'push_vertical', 'shoulders', 'shoulder', 'bodyweight', 11, 'beginner'),
                (13, 'Elevated Feet Pike Push-Up', 'push_vertical', 'shoulders', 'shoulder', 'bodyweight', 12, 'intermediate'),
                (14, 'Seated Dumbbell Overhead Press', 'push_vertical', 'shoulders', 'shoulder', 'dumbbell', 12, 'beginner'),
                (15, 'Standing Barbell Overhead Press (OHP)', 'push_vertical', 'shoulders', 'lower_back', 'barbell', 14, 'intermediate'),
                (16, 'Machine Shoulder Press', 'push_vertical', 'shoulders', 'shoulder', 'machine', 14, 'beginner'),
                (17, 'Door Towel Row', 'pull_horizontal', 'back', 'elbow', 'bodyweight', NULL, 'absolute_beginner'),
                (18, 'Incline Bodyweight Australian Row (High Bar)', 'pull_horizontal', 'back', 'elbow', 'bodyweight', 17, 'beginner'),
                (19, 'Horizontal Inverted Row (Low Bar)', 'pull_horizontal', 'back', 'elbow', 'bodyweight', 18, 'intermediate'),
                (20, 'One-Arm Dumbbell Row', 'pull_horizontal', 'back', 'lower_back', 'dumbbell', 19, 'beginner'),
                (21, 'Barbell Bent-Over Row', 'pull_horizontal', 'back', 'lower_back', 'barbell', 20, 'intermediate'),
                (22, 'Seated Cable Row', 'pull_horizontal', 'back', 'lower_back', 'cable', 20, 'beginner'),
                (23, 'Chest Supported Machine Row', 'pull_horizontal', 'back', 'elbow', 'machine', 21, 'beginner'),
                (24, 'Band-Assisted Negative Chin-Up', 'pull_vertical', 'back', 'elbow', 'bodyweight', NULL, 'absolute_beginner'),
                (25, 'Negative Chin-Up (Slow Eccentric)', 'pull_vertical', 'back', 'elbow', 'bodyweight', 24, 'beginner'),
                (26, 'Chin-Up (Underhand Grip)', 'pull_vertical', 'back', 'elbow', 'bodyweight', 25, 'intermediate'),
                (27, 'Pull-Up (Overhand Grip)', 'pull_vertical', 'back', 'shoulder', 'bodyweight', 26, 'advanced'),
                (28, 'Lat Pulldown (Cable/Machine)', 'pull_vertical', 'back', 'shoulder', 'cable', 26, 'beginner'),
                (29, 'Chair Assisted Bodyweight Squat', 'squat', 'legs', 'knee', 'bodyweight', NULL, 'absolute_beginner'),
                (30, 'Bodyweight Air Squat', 'squat', 'legs', 'knee', 'bodyweight', 29, 'beginner'),
                (31, 'Goblet Squat (Dumbbell)', 'squat', 'legs', 'knee', 'dumbbell', 30, 'beginner'),
                (32, 'Barbell Back Squat', 'squat', 'legs', 'lower_back', 'barbell', 31, 'intermediate'),
                (33, 'Barbell Front Squat', 'squat', 'legs', 'knee', 'barbell', 32, 'advanced'),
                (34, 'Leg Press (Machine)', 'squat', 'legs', 'knee', 'machine', 31, 'beginner'),
                (35, 'Bulgarian Split Squat (Dumbbell)', 'squat', 'legs', 'knee', 'dumbbell', 31, 'intermediate'),
                (36, 'Glute Bridge (Bodyweight)', 'hinge', 'legs', 'lower_back', 'bodyweight', NULL, 'absolute_beginner'),
                (37, 'Single-Leg Bodyweight Glute Bridge', 'hinge', 'legs', 'lower_back', 'bodyweight', 36, 'beginner'),
                (38, 'Dumbbell Romanian Deadlift (RDL)', 'hinge', 'legs', 'lower_back', 'dumbbell', 37, 'beginner'),
                (39, 'Barbell Romanian Deadlift (RDL)', 'hinge', 'legs', 'lower_back', 'barbell', 38, 'intermediate'),
                (40, 'Conventional Barbell Deadlift', 'hinge', 'back', 'lower_back', 'barbell', 39, 'advanced'),
                (41, 'Hyperextension (Spine Friendly)', 'hinge', 'back', 'lower_back', 'bodyweight', 36, 'beginner'),
                (42, 'Standing Dumbbell Bicep Curl', 'isolation_arm', 'arms', 'elbow', 'dumbbell', NULL, 'beginner'),
                (43, 'Barbell Bicep Curl', 'isolation_arm', 'arms', 'wrist', 'barbell', 42, 'beginner'),
                (44, 'Incline Dumbbell Bicep Curl', 'isolation_arm', 'arms', 'elbow', 'dumbbell', 42, 'intermediate'),
                (45, 'Bench Triceps Dip (Bodyweight)', 'isolation_arm', 'arms', 'shoulder', 'bodyweight', NULL, 'beginner'),
                (46, 'Dumbbell Overhead Triceps Extension', 'isolation_arm', 'arms', 'elbow', 'dumbbell', 45, 'beginner'),
                (47, 'Cable Triceps Pushdown', 'isolation_arm', 'arms', 'elbow', 'cable', 45, 'beginner'),
                (48, 'Standing Dumbbell Lateral Raise', 'isolation_shoulder', 'shoulders', 'shoulder', 'dumbbell', NULL, 'beginner'),
                (49, 'Dumbbell Bent-Over Rear Delt Fly', 'isolation_shoulder', 'shoulders', 'shoulder', 'dumbbell', NULL, 'beginner'),
                (50, 'Knee Plank (Duration)', 'core', 'core', 'lower_back', 'bodyweight', NULL, 'absolute_beginner'),
                (51, 'Standard Front Plank (Duration)', 'core', 'core', 'lower_back', 'bodyweight', 50, 'beginner'),
                (52, 'Dead Bug', 'core', 'core', 'lower_back', 'bodyweight', 50, 'beginner'),
                (53, 'Hanging Knee Raise', 'core', 'core', 'shoulder', 'bodyweight', 51, 'intermediate'),
                (54, 'Cable Woodchopper', 'core', 'core', 'lower_back', 'cable', 52, 'intermediate');

                INSERT INTO exercise_joint_stress (exercise_id, joint_area, stress_level) VALUES
                (7, 'shoulder', 'primary'), (7, 'elbow', 'secondary'), (7, 'wrist', 'secondary'),
                (9, 'shoulder', 'primary'), (9, 'elbow', 'secondary'),
                (15, 'shoulder', 'primary'), (15, 'lower_back', 'secondary'), (15, 'wrist', 'secondary'),
                (21, 'lower_back', 'primary'), (21, 'elbow', 'secondary'),
                (20, 'lower_back', 'secondary'), (20, 'elbow', 'secondary'),
                (27, 'shoulder', 'primary'), (27, 'elbow', 'secondary'),
                (26, 'elbow', 'primary'), (26, 'shoulder', 'secondary'),
                (32, 'knee', 'primary'), (32, 'lower_back', 'secondary'),
                (33, 'knee', 'primary'), (33, 'wrist', 'secondary'),
                (34, 'knee', 'primary'), (34, 'lower_back', 'secondary'),
                (35, 'knee', 'primary'),
                (39, 'lower_back', 'primary'),
                (40, 'lower_back', 'primary'), (40, 'knee', 'secondary'),
                (45, 'shoulder', 'primary'), (45, 'elbow', 'secondary'),
                (48, 'shoulder', 'primary'),
                (51, 'lower_back', 'secondary'), (51, 'shoulder', 'secondary');
            ");
        }
    }
}
