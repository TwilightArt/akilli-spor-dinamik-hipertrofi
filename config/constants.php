<?php
declare(strict_types=1);

namespace App\Config;

/**
 * System Constants & Scientific Mathematical Hypertrophy Coefficients
 */
final class Constants
{
    // =========================================================================
    // NSCA %1RM Percentage Table
    // Reps to Estimated 1RM percentage mapping
    // =========================================================================
    public const NSCA_PERCENTAGE_TABLE = [
        1  => 1.00,
        2  => 0.95,
        3  => 0.93,
        4  => 0.90,
        5  => 0.87,
        6  => 0.85,
        7  => 0.83,
        8  => 0.80,
        9  => 0.77,
        10 => 0.75,
        11 => 0.72,
        12 => 0.70,
        15 => 0.65,
    ];

    // =========================================================================
    // RFI (Starting Relative Force Index) Initial Formulas
    // Movement pattern base load formulas (Bodyweight and Onboarding Reps)
    // =========================================================================
    public const RFI_COEFFICIENTS = [
        'push_horizontal' => [
            'weight_factor' => 0.30,
            'rep_factor'    => 0.25, // pushups
            'rep_type'      => 'pushup'
        ],
        'pull_horizontal' => [
            'weight_factor' => 0.35,
            'rep_factor'    => 0.60, // chinups
            'rep_type'      => 'chinup'
        ],
        'pull_vertical' => [
            'weight_factor' => 0.35,
            'rep_factor'    => 0.60, // chinups
            'rep_type'      => 'chinup'
        ],
        'isolation_arm' => [
            'weight_factor' => 0.18,
            'rep_factor'    => 0.45, // chinups
            'rep_type'      => 'chinup'
        ],
        'squat' => [
            'weight_factor' => 0.50,
            'rep_factor'    => 0.20, // squats
            'rep_type'      => 'squat'
        ],
        'hinge' => [
            'weight_factor' => 0.40,
            'rep_factor'    => 0.30, // chinups
            'rep_type'      => 'chinup'
        ],
        'isolation_shoulder' => [
            'weight_factor' => 0.08,
            'rep_factor'    => 0.10, // pushups
            'rep_type'      => 'pushup'
        ],
        'push_vertical' => [
            'weight_factor' => 0.20,
            'rep_factor'    => 0.15, // pushups
            'rep_type'      => 'pushup'
        ],
        'core' => [
            'weight_factor' => 0.00,
            'rep_factor'    => 1.00,
            'rep_type'      => 'plank'
        ]
    ];

    // Calibration limit for first 2 sessions
    public const CALIBRATION_MAX_PCT = 0.15; // ±15%

    // =========================================================================
    // Hypertrophy Engine Rules & Safety Thresholds
    // =========================================================================
    // Single set increase combined ceiling: min(actual_weight * 0.10, 2.50 kg)
    public const MAX_SINGLE_SET_KG = 2.50;
    public const MAX_SINGLE_SET_PCT = 0.10;

    // Session-wide cumulative safety ceiling: Max 3 increases and +20% over first set
    public const MAX_SESSION_INCREASES_COUNT = 3;
    public const MAX_SESSION_INCREASE_PCT = 0.20;

    // Emergency Drop when form breakdowns
    public const FORM_BREAKDOWN_DROP_PCT = 0.15;

    // RIR = 0 Adaptation
    public const RIR0_REST_EXTENSION_SECONDS = 30;
    public const RIR0_CONSECUTIVE_DROP_PCT = 0.10;

    // =========================================================================
    // Deload & Volume Optimization (nightly_sync.php)
    // =========================================================================
    public const SENIOR_AGE_THRESHOLD = 40;
    public const STANDARD_DELOAD_CYCLE_WEEKS = 6;
    public const SENIOR_DELOAD_CYCLE_WEEKS = 4;

    public const DELOAD_WEIGHT_REDUCTION_PCT = 0.35; // 35% reduction (30-40% range)
    public const DELOAD_SET_REDUCTION_FACTOR = 0.50; // Halve sets
    public const DELOAD_MIN_RIR = 3;

    public const WEIGHT_LOSS_RATE_THRESHOLD = 0.01; // Weekly > 1% weight loss
    public const VOLUME_REDUCTION_PCT = 0.25;       // 25% set reduction
    public const VOLUME_INCREASE_PCT = 0.05;        // 5% weekly volume increase
    public const MIN_TARGET_SETS = 2;

    // =========================================================================
    // Security & Rate Limiting
    // =========================================================================
    public const MAX_LOGIN_ATTEMPTS = 5;
    public const LOGIN_LOCKOUT_MINUTES = 15;
    public const MAX_SET_LOGS_PER_MINUTE = 3;
    public const PASSWORD_RESET_EXPIRY_HOURS = 1;

    // =========================================================================
    // AI Retries
    // =========================================================================
    public const MAX_AI_RETRIES = 6;
}
