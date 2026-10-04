<?php
declare(strict_types=1);

namespace App\Config;

/**
 * Gemini API Configuration
 */
class GeminiConfig
{
    public static function getApiKey(): string
    {
        return getenv('GEMINI_API_KEY') ?: '';
    }

    public static function getModel(): string
    {
        return getenv('GEMINI_MODEL') ?: 'gemini-1.5-flash';
    }

    public static function getApiUrl(): string
    {
        $model = self::getModel();
        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
    }

    public static function getTimeoutSeconds(): float
    {
        // Strictly mandated 5.0 seconds cURL timeout
        return 5.0;
    }
}
