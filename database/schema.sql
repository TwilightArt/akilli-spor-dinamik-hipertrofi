-- ============================================================================
-- Akıllı Spor & Dinamik Hipertrofi Platformu (v2.1 — Full Production)
-- MySQL 8.0+ / MariaDB 10.5+ Production Schema
-- Engine: InnoDB | Character Set: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================================

-- Production Safety: DROP TABLE statements are commented out to prevent accidental data loss.
-- Uncomment ONLY when doing a complete database rebuild in a local/staging environment.
--
-- SET FOREIGN_KEY_CHECKS = 0;
-- DROP TABLE IF EXISTS `workout_logs`;
-- DROP TABLE IF EXISTS `daily_weigh_ins`;
-- DROP TABLE IF EXISTS `workout_sessions`;
-- DROP TABLE IF EXISTS `program_exercises`;
-- DROP TABLE IF EXISTS `program_days`;
-- DROP TABLE IF EXISTS `user_programs`;
-- DROP TABLE IF EXISTS `injury_reports`;
-- DROP TABLE IF EXISTS `user_calibration_log`;
-- DROP TABLE IF EXISTS `exercise_joint_stress`;
-- DROP TABLE IF EXISTS `exercises`;
-- DROP TABLE IF EXISTS `user_equipment`;
-- DROP TABLE IF EXISTS `password_resets`;
-- DROP TABLE IF EXISTS `login_attempts`;
-- DROP TABLE IF EXISTS `users`;
-- SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. users
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `gender` ENUM('male', 'female') NOT NULL,
  `birth_date` DATE NOT NULL,
  `height_cm` DECIMAL(5,2) NOT NULL,
  `fitness_level` ENUM('absolute_beginner','beginner','intermediate','advanced') DEFAULT 'beginner',
  `pushups_count` INT DEFAULT 0,
  `chinups_count` INT DEFAULT 0,
  `squats_count` INT DEFAULT 0,
  `plank_seconds` INT DEFAULT 30,
  `calisthenic_score` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. login_attempts (IP & Brute-Force Protection)
-- ----------------------------------------------------------------------------
CREATE TABLE `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `email` VARCHAR(150) NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `success` BOOLEAN DEFAULT FALSE,
  INDEX `idx_login_ip_time` (`ip_address`, `attempted_at`),
  INDEX `idx_login_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. password_resets
-- ----------------------------------------------------------------------------
CREATE TABLE `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL,
  `token_hash` VARCHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pwd_reset_email_token` (`email`, `token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. user_equipment
-- ----------------------------------------------------------------------------
CREATE TABLE `user_equipment` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `has_barbell` BOOLEAN DEFAULT FALSE,
  `has_dumbbells` BOOLEAN DEFAULT FALSE,
  `barbell_weight_kg` DECIMAL(4,2) DEFAULT 20.00,
  `dumbbell_handle_weight_kg` DECIMAL(4,2) DEFAULT 2.50,
  `available_plates` JSON NOT NULL, -- e.g. {"20kg":2,"10kg":4,"5kg":4,"2.5kg":4,"1.25kg":4}
  `workout_location` ENUM('bodyweight','home_weights','commercial_gym') NOT NULL,
  CONSTRAINT `fk_user_equipment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_equipment_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. exercises
-- ----------------------------------------------------------------------------
CREATE TABLE `exercises` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `movement_pattern` ENUM(
    'push_horizontal',
    'push_vertical',
    'pull_horizontal',
    'pull_vertical',
    'squat',
    'hinge',
    'isolation_arm',
    'isolation_shoulder',
    'core'
  ) NOT NULL,
  `primary_muscle_group` ENUM('chest','back','legs','shoulders','arms','core') NOT NULL,
  `primary_joint_stress` ENUM('shoulder','elbow','lower_back','knee','wrist','neck','other') NULL,
  `required_equipment` ENUM('bodyweight','barbell','dumbbell','machine','cable') NOT NULL,
  `is_regression_of` INT NULL,
  `difficulty_level` ENUM('absolute_beginner','beginner','intermediate','advanced') DEFAULT 'beginner',
  CONSTRAINT `fk_exercises_regression` FOREIGN KEY (`is_regression_of`) REFERENCES `exercises` (`id`) ON DELETE SET NULL,
  INDEX `idx_exercise_pattern` (`movement_pattern`),
  INDEX `idx_exercise_equipment` (`required_equipment`),
  INDEX `idx_exercise_muscle` (`primary_muscle_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. exercise_joint_stress (Multi-joint stress mapping)
-- ----------------------------------------------------------------------------
CREATE TABLE `exercise_joint_stress` (
  `exercise_id` INT NOT NULL,
  `joint_area` ENUM('shoulder','elbow','lower_back','knee','wrist','neck','other') NOT NULL,
  `stress_level` ENUM('primary','secondary') DEFAULT 'primary',
  PRIMARY KEY (`exercise_id`, `joint_area`),
  CONSTRAINT `fk_joint_stress_exercise` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. user_calibration_log
-- ----------------------------------------------------------------------------
CREATE TABLE `user_calibration_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `movement_pattern` VARCHAR(50) NOT NULL,
  `base_coefficient` DECIMAL(5,3) NOT NULL,
  `adjusted_coefficient` DECIMAL(5,3) NOT NULL,
  `reason` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_calibration_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_calibration_user_pattern` (`user_id`, `movement_pattern`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. injury_reports
-- ----------------------------------------------------------------------------
CREATE TABLE `injury_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `joint_area` ENUM('shoulder','elbow','lower_back','knee','wrist','neck','other') NOT NULL,
  `severity` ENUM('mild','moderate','severe') DEFAULT 'mild',
  `reported_date` DATE NOT NULL,
  `notes` TEXT NULL,
  CONSTRAINT `fk_injury_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_injury_user_date` (`user_id`, `reported_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. user_programs
-- ----------------------------------------------------------------------------
CREATE TABLE `user_programs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `program_type` ENUM('full_body','split_upper_lower','split_ppl') NOT NULL,
  `week_start_date` DATE NOT NULL,
  `is_deload_week` BOOLEAN DEFAULT FALSE,
  `ai_generated_raw_json` JSON NULL,
  `generation_source` ENUM('ai_generated','fallback_template') DEFAULT 'ai_generated',
  `generation_retry_count` TINYINT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_program_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_program_user_week` (`user_id`, `week_start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 10. program_days
-- ----------------------------------------------------------------------------
CREATE TABLE `program_days` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `program_id` INT NOT NULL,
  `day_of_week` TINYINT NOT NULL, -- 1=Pzt, 2=Sal, 3=Car, 4=Per, 5=Cum, 6=Cts, 7=Paz
  `focus_area` VARCHAR(100) NOT NULL,
  CONSTRAINT `fk_days_program` FOREIGN KEY (`program_id`) REFERENCES `user_programs` (`id`) ON DELETE CASCADE,
  INDEX `idx_days_program` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 11. program_exercises
-- ----------------------------------------------------------------------------
CREATE TABLE `program_exercises` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `program_day_id` INT NOT NULL,
  `exercise_id` INT NOT NULL,
  `order_in_session` TINYINT NOT NULL,
  `target_sets` TINYINT NOT NULL,
  `target_reps_min` TINYINT NOT NULL,
  `target_reps_max` TINYINT NOT NULL,
  CONSTRAINT `fk_prog_ex_day` FOREIGN KEY (`program_day_id`) REFERENCES `program_days` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prog_ex_exercise` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`) ON DELETE RESTRICT,
  INDEX `idx_prog_ex_day` (`program_day_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 12. workout_sessions
-- ----------------------------------------------------------------------------
CREATE TABLE `workout_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `program_day_id` INT NULL,
  `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `ended_at` TIMESTAMP NULL,
  CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_session_day` FOREIGN KEY (`program_day_id`) REFERENCES `program_days` (`id`) ON DELETE SET NULL,
  INDEX `idx_session_user_time` (`user_id`, `started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 13. daily_weigh_ins
-- ----------------------------------------------------------------------------
CREATE TABLE `daily_weigh_ins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `weight_kg` DECIMAL(5,2) NOT NULL,
  `logged_date` DATE NOT NULL,
  `notes` TEXT NULL,
  CONSTRAINT `fk_weigh_in_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `uniq_user_date` (`user_id`, `logged_date`),
  INDEX `idx_weigh_in_user_date` (`user_id`, `logged_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 14. workout_logs
-- ----------------------------------------------------------------------------
CREATE TABLE `workout_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `session_id` INT NOT NULL,
  `exercise_id` INT NOT NULL,
  `set_number` TINYINT NOT NULL,
  `set_type` ENUM('reps','duration','warmup') DEFAULT 'reps',
  `target_weight` DECIMAL(5,2) NULL,
  `actual_weight` DECIMAL(5,2) NOT NULL,
  `target_reps` TINYINT NULL,
  `actual_reps` TINYINT NULL,
  `duration_seconds` SMALLINT NULL,
  `rpe` TINYINT NULL,
  `rir_bucket` ENUM('0','1-2','3+') NOT NULL,
  `form_breakdown` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_log_session` FOREIGN KEY (`session_id`) REFERENCES `workout_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_log_exercise` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`) ON DELETE RESTRICT,
  INDEX `idx_log_user_time` (`user_id`, `created_at`),
  INDEX `idx_log_session` (`session_id`),
  INDEX `idx_log_user_exercise` (`user_id`, `exercise_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DATA: 50 Real-World Exercises with Regressions & Multi-Joint Stress
-- ============================================================================

INSERT INTO `exercises` (`id`, `name`, `movement_pattern`, `primary_muscle_group`, `primary_joint_stress`, `required_equipment`, `is_regression_of`, `difficulty_level`) VALUES
-- PUSH HORIZONTAL (Göğüs / Ön Omuz / Triceps)
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

-- PUSH VERTICAL (Omuz / Triceps)
(11, 'Pike Push-Up with Knees', 'push_vertical', 'shoulders', 'wrist', 'bodyweight', NULL, 'absolute_beginner'),
(12, 'Pike Push-Up', 'push_vertical', 'shoulders', 'shoulder', 'bodyweight', 11, 'beginner'),
(13, 'Elevated Feet Pike Push-Up', 'push_vertical', 'shoulders', 'shoulder', 'bodyweight', 12, 'intermediate'),
(14, 'Seated Dumbbell Overhead Press', 'push_vertical', 'shoulders', 'shoulder', 'dumbbell', 12, 'beginner'),
(15, 'Standing Barbell Overhead Press (OHP)', 'push_vertical', 'shoulders', 'lower_back', 'barbell', 14, 'intermediate'),
(16, 'Machine Shoulder Press', 'push_vertical', 'shoulders', 'shoulder', 'machine', 14, 'beginner'),

-- PULL HORIZONTAL (Sırt / Biceps / Arka Omuz)
(17, 'Door Towel Row', 'pull_horizontal', 'back', 'elbow', 'bodyweight', NULL, 'absolute_beginner'),
(18, 'Incline Bodyweight Australian Row (High Bar)', 'pull_horizontal', 'back', 'elbow', 'bodyweight', 17, 'beginner'),
(19, 'Horizontal Inverted Row (Low Bar)', 'pull_horizontal', 'back', 'elbow', 'bodyweight', 18, 'intermediate'),
(20, 'One-Arm Dumbbell Row', 'pull_horizontal', 'back', 'lower_back', 'dumbbell', 19, 'beginner'),
(21, 'Barbell Bent-Over Row', 'pull_horizontal', 'back', 'lower_back', 'barbell', 20, 'intermediate'),
(22, 'Seated Cable Row', 'pull_horizontal', 'back', 'lower_back', 'cable', 20, 'beginner'),
(23, 'Chest Supported Machine Row', 'pull_horizontal', 'back', 'elbow', 'machine', 21, 'beginner'),

-- PULL VERTICAL (Lat / Biceps)
(24, 'Band-Assisted Negative Chin-Up', 'pull_vertical', 'back', 'elbow', 'bodyweight', NULL, 'absolute_beginner'),
(25, 'Negative Chin-Up (Slow Eccentric)', 'pull_vertical', 'back', 'elbow', 'bodyweight', 24, 'beginner'),
(26, 'Chin-Up (Underhand Grip)', 'pull_vertical', 'back', 'elbow', 'bodyweight', 25, 'intermediate'),
(27, 'Pull-Up (Overhand Grip)', 'pull_vertical', 'back', 'shoulder', 'bodyweight', 26, 'advanced'),
(28, 'Lat Pulldown (Cable/Machine)', 'pull_vertical', 'back', 'shoulder', 'cable', 26, 'beginner'),

-- SQUAT (Quadriceps / Gluteus)
(29, 'Chair Assisted Bodyweight Squat', 'squat', 'legs', 'knee', 'bodyweight', NULL, 'absolute_beginner'),
(30, 'Bodyweight Air Squat', 'squat', 'legs', 'knee', 'bodyweight', 29, 'beginner'),
(31, 'Goblet Squat (Dumbbell)', 'squat', 'legs', 'knee', 'dumbbell', 30, 'beginner'),
(32, 'Barbell Back Squat', 'squat', 'legs', 'lower_back', 'barbell', 31, 'intermediate'),
(33, 'Barbell Front Squat', 'squat', 'legs', 'knee', 'barbell', 32, 'advanced'),
(34, 'Leg Press (Machine)', 'squat', 'legs', 'knee', 'machine', 31, 'beginner'),
(35, 'Bulgarian Split Squat (Dumbbell)', 'squat', 'legs', 'knee', 'dumbbell', 31, 'intermediate'),

-- HINGE (Hamstrings / Gluteus / Bel)
(36, 'Glute Bridge (Bodyweight)', 'hinge', 'legs', 'lower_back', 'bodyweight', NULL, 'absolute_beginner'),
(37, 'Single-Leg Bodyweight Glute Bridge', 'hinge', 'legs', 'lower_back', 'bodyweight', 36, 'beginner'),
(38, 'Dumbbell Romanian Deadlift (RDL)', 'hinge', 'legs', 'lower_back', 'dumbbell', 37, 'beginner'),
(39, 'Barbell Romanian Deadlift (RDL)', 'hinge', 'legs', 'lower_back', 'barbell', 38, 'intermediate'),
(40, 'Conventional Barbell Deadlift', 'hinge', 'back', 'lower_back', 'barbell', 39, 'advanced'),
(41, 'Hyperextension (Spine Friendly)', 'hinge', 'back', 'lower_back', 'bodyweight', 36, 'beginner'),

-- ISOLATION ARM (Biceps & Triceps)
(42, 'Standing Dumbbell Bicep Curl', 'isolation_arm', 'arms', 'elbow', 'dumbbell', NULL, 'beginner'),
(43, 'Barbell Bicep Curl', 'isolation_arm', 'arms', 'wrist', 'barbell', 42, 'beginner'),
(44, 'Incline Dumbbell Bicep Curl', 'isolation_arm', 'arms', 'elbow', 'dumbbell', 42, 'intermediate'),
(45, 'Bench Triceps Dip (Bodyweight)', 'isolation_arm', 'arms', 'shoulder', 'bodyweight', NULL, 'beginner'),
(46, 'Dumbbell Overhead Triceps Extension', 'isolation_arm', 'arms', 'elbow', 'dumbbell', 45, 'beginner'),
(47, 'Cable Triceps Pushdown', 'isolation_arm', 'arms', 'elbow', 'cable', 45, 'beginner'),

-- ISOLATION SHOULDER (Lateral & Rear Delts)
(48, 'Standing Dumbbell Lateral Raise', 'isolation_shoulder', 'shoulders', 'shoulder', 'dumbbell', NULL, 'beginner'),
(49, 'Dumbbell Bent-Over Rear Delt Fly', 'isolation_shoulder', 'shoulders', 'shoulder', 'dumbbell', NULL, 'beginner'),

-- CORE (Abdominals & Lumbar Stabilizers)
(50, 'Knee Plank (Duration)', 'core', 'core', 'lower_back', 'bodyweight', NULL, 'absolute_beginner'),
(51, 'Standard Front Plank (Duration)', 'core', 'core', 'lower_back', 'bodyweight', 50, 'beginner'),
(52, 'Dead Bug', 'core', 'core', 'lower_back', 'bodyweight', 50, 'beginner'),
(53, 'Hanging Knee Raise', 'core', 'core', 'shoulder', 'bodyweight', 51, 'intermediate'),
(54, 'Cable Woodchopper', 'core', 'core', 'lower_back', 'cable', 52, 'intermediate');

-- ----------------------------------------------------------------------------
-- SEED DATA: Multi-Joint Stress Mapping (exercise_joint_stress)
-- ----------------------------------------------------------------------------
INSERT INTO `exercise_joint_stress` (`exercise_id`, `joint_area`, `stress_level`) VALUES
-- Flat Bench Press: Primary Shoulder, Secondary Elbow & Wrist
(7, 'shoulder', 'primary'),
(7, 'elbow', 'secondary'),
(7, 'wrist', 'secondary'),
-- Incline DB Press: Primary Shoulder, Secondary Elbow
(9, 'shoulder', 'primary'),
(9, 'elbow', 'secondary'),
-- Standing OHP: Primary Shoulder, Secondary Lower Back & Wrist
(15, 'shoulder', 'primary'),
(15, 'lower_back', 'secondary'),
(15, 'wrist', 'secondary'),
-- Bent-Over Barbell Row: Primary Lower Back, Secondary Elbow
(21, 'lower_back', 'primary'),
(21, 'elbow', 'secondary'),
-- One-Arm DB Row: Secondary Lower Back
(20, 'lower_back', 'secondary'),
(20, 'elbow', 'secondary'),
-- Pull-Up: Primary Shoulder, Secondary Elbow
(27, 'shoulder', 'primary'),
(27, 'elbow', 'secondary'),
-- Chin-Up: Primary Elbow, Secondary Shoulder
(26, 'elbow', 'primary'),
(26, 'shoulder', 'secondary'),
-- Barbell Back Squat: Primary Knee, Secondary Lower Back
(32, 'knee', 'primary'),
(32, 'lower_back', 'secondary'),
-- Front Squat: Primary Knee, Secondary Wrist
(33, 'knee', 'primary'),
(33, 'wrist', 'secondary'),
-- Leg Press: Primary Knee, Secondary Lower Back
(34, 'knee', 'primary'),
(34, 'lower_back', 'secondary'),
-- Bulgarian Split Squat: Primary Knee
(35, 'knee', 'primary'),
-- Barbell Romanian Deadlift: Primary Lower Back
(39, 'lower_back', 'primary'),
-- Conventional Deadlift: Primary Lower Back, Secondary Knee
(40, 'lower_back', 'primary'),
(40, 'knee', 'secondary'),
-- Bench Triceps Dip: Primary Shoulder, Secondary Elbow
(45, 'shoulder', 'primary'),
(45, 'elbow', 'secondary'),
-- Lateral Raise: Primary Shoulder
(48, 'shoulder', 'primary'),
-- Plank: Secondary Lower Back, Secondary Shoulder
(51, 'lower_back', 'secondary'),
(51, 'shoulder', 'secondary');
