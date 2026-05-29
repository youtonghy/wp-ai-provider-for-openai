<?php

declare(strict_types=1);

namespace WordPress\OpenAiAiProvider\Settings;

/**
 * Settings access helpers for the OpenAI provider.
 *
 * @since 1.0.4
 */
class OpenAiSettings
{
    public const OPTION_NAME = 'ai_provider_for_openai_settings';
    public const DEFAULT_API_URL = 'https://api.openai.com/v1';
    public const CONNECTOR_API_KEY_OPTION_NAME = 'connectors_ai_openai_compatible_api_key';
    public const ENV_API_KEY_NAME = 'OPENAI_COMPATIBLE_API_KEY';
    public const LEGACY_ENV_API_KEY_NAME = 'OPENAI_API_KEY';
    private const CONNECTOR_API_KEY_OPTION_ALIASES = [
        'connectors_ai_provider_openai_compatible_api_key',
        'connectors_ai_provider_openai-compatible_api_key',
    ];

    /**
     * Gets all plugin settings.
     *
     * @since 1.0.4
     *
     * @return array<string, string> Settings array.
     */
    public static function getSettings(): array
    {
        $settings = [];
        if (function_exists('get_option')) {
            $storedSettings = get_option(self::OPTION_NAME, []);
            if (is_array($storedSettings)) {
                $settings = $storedSettings;
            }
        }

        $settings = array_merge(
            self::defaultSettings(),
            array_filter(
                $settings,
                static function ($value): bool {
                    return is_scalar($value);
                }
            )
        );

        $connectorApiKey = self::getConnectorApiKeyFromOption();
        if ($connectorApiKey !== '') {
            $settings['api_key'] = $connectorApiKey;
        } else {
            $legacyApiKey = self::getLegacyStoredApiKey();
            if ($legacyApiKey !== '') {
                self::updateConnectorApiKeyOption($legacyApiKey);
                self::deleteLegacyApiKey();
                $settings['api_key'] = $legacyApiKey;
            }
        }

        return $settings;
    }

    /**
     * Sanitizes settings before persisting them.
     *
     * @since 1.0.4
     *
     * @param mixed $input Raw input from the settings form.
     * @return array<string, string> Sanitized settings.
     */
    public static function sanitizeSettings($input): array
    {
        static $clearApiKey = false;

        if (!is_array($input)) {
            return self::defaultSettings();
        }

        $settings = self::defaultSettings();
        $currentSettings = self::getSettings();

        $settings['api_url'] = self::sanitizeApiUrl($input['api_url'] ?? '');
        if (($input['clear_api_key'] ?? '') === '1') {
            $clearApiKey = true;
        }
        if ($clearApiKey) {
            self::deleteConnectorApiKeyOption();
            $settings['api_key'] = '';
        } else {
            $apiKey = self::sanitizeApiKey($input['api_key'] ?? '');
            $apiKey = $apiKey !== '' ? $apiKey : $currentSettings['api_key'];
            if ($apiKey !== '') {
                self::updateConnectorApiKeyOption($apiKey);
            }
            $settings['api_key'] = '';
        }
        $settings['model'] = self::sanitizeString($input['model'] ?? '');
        $settings['image_model'] = self::sanitizeString($input['image_model'] ?? '');

        $reasoningEffort = self::sanitizeString($input['reasoning_effort'] ?? '');
        $settings['reasoning_effort'] = in_array($reasoningEffort, self::reasoningEffortValues(), true)
            ? $reasoningEffort
            : '';

        return $settings;
    }

    /**
     * Gets the configured API base URL.
     *
     * @since 1.0.4
     *
     * @return string API base URL.
     */
    public static function getApiBaseUrl(): string
    {
        $settings = self::getSettings();

        return self::normalizeApiUrl($settings['api_url']);
    }

    /**
     * Gets the configured Responses API endpoint URL.
     *
     * @since 1.0.4
     *
     * @return string Responses API endpoint URL.
     */
    public static function getResponsesApiUrl(): string
    {
        $settings = self::getSettings();
        $apiUrl = self::trimTrailingSlashes($settings['api_url']);

        if (self::endsWith($apiUrl, '/responses') || self::endsWith($apiUrl, '/response')) {
            return $apiUrl;
        }

        return self::getApiBaseUrl() . '/responses';
    }

    /**
     * Gets a fallback Responses API endpoint URL for legacy singular paths.
     *
     * @since 1.0.4
     *
     * @param string $apiUrl Current Responses API URL.
     * @return string Fallback URL, or an empty string when no fallback applies.
     */
    public static function getResponsesApiFallbackUrl(string $apiUrl): string
    {
        $apiUrl = self::trimTrailingSlashes($apiUrl);

        if (self::endsWith($apiUrl, '/response')) {
            return substr($apiUrl, 0, -strlen('/response')) . '/responses';
        }

        return '';
    }

    /**
     * Gets the configured API key.
     *
     * @since 1.0.4
     *
     * @return string API key, or an empty string when none is configured.
     */
    public static function getApiKey(): string
    {
        $savedApiKey = self::getStoredApiKey();
        if ($savedApiKey !== '') {
            return $savedApiKey;
        }

        $envApiKey = getenv(self::ENV_API_KEY_NAME);
        if (is_string($envApiKey) && $envApiKey !== '') {
            return $envApiKey;
        }

        if (defined(self::ENV_API_KEY_NAME) && is_string(constant(self::ENV_API_KEY_NAME))) {
            $constantApiKey = constant(self::ENV_API_KEY_NAME);
            if ($constantApiKey !== '') {
                return $constantApiKey;
            }
        }

        $legacyEnvApiKey = getenv(self::LEGACY_ENV_API_KEY_NAME);
        if (is_string($legacyEnvApiKey) && $legacyEnvApiKey !== '') {
            return $legacyEnvApiKey;
        }

        if (defined(self::LEGACY_ENV_API_KEY_NAME) && is_string(constant(self::LEGACY_ENV_API_KEY_NAME))) {
            $constantApiKey = constant(self::LEGACY_ENV_API_KEY_NAME);
            if ($constantApiKey !== '') {
                return $constantApiKey;
            }
        }

        return '';
    }

    /**
     * Gets the API key saved in this plugin's settings.
     *
     * @since 1.0.4
     *
     * @return string Saved API key, or an empty string when none is configured.
     */
    public static function getStoredApiKey(): string
    {
        $connectorApiKey = self::getConnectorApiKeyFromOption();
        if ($connectorApiKey !== '') {
            return $connectorApiKey;
        }

        $legacyApiKey = self::getLegacyStoredApiKey();
        if ($legacyApiKey !== '') {
            self::updateConnectorApiKeyOption($legacyApiKey);
            self::deleteLegacyApiKey();
        }

        return $legacyApiKey;
    }

    /**
     * Gets the configured default text model.
     *
     * @since 1.0.4
     *
     * @return string Model ID, or an empty string when no default override is configured.
     */
    public static function getDefaultModel(): string
    {
        $settings = self::getSettings();

        return $settings['model'];
    }

    /**
     * Gets the configured default image generation model.
     *
     * @since 1.0.4
     *
     * @return string Model ID, or an empty string when no image override is configured.
     */
    public static function getDefaultImageModel(): string
    {
        $settings = self::getSettings();

        return $settings['image_model'];
    }

    /**
     * Gets the configured reasoning effort.
     *
     * @since 1.0.4
     *
     * @return string Reasoning effort, or an empty string to use the API default.
     */
    public static function getReasoningEffort(): string
    {
        $settings = self::getSettings();

        return $settings['reasoning_effort'];
    }

    /**
     * Checks whether the configured reasoning effort should be sent for the model ID.
     *
     * @since 1.0.4
     *
     * @param string $modelId The model ID.
     * @return bool True if reasoning effort should be sent.
     */
    public static function shouldSendReasoningEffort(string $modelId): bool
    {
        return self::getReasoningEffort() !== '' && self::modelSupportsReasoning($modelId);
    }

    /**
     * Checks whether sampling controls should be sent for the model ID.
     *
     * @since 1.0.4
     *
     * @param string $modelId The model ID.
     * @return bool True if temperature/top_p should be sent.
     */
    public static function shouldSendSamplingControls(string $modelId): bool
    {
        return !self::modelUsesFixedSampling($modelId);
    }

    /**
     * Gets the reasoning effort options for the settings UI.
     *
     * @since 1.0.4
     *
     * @return array<string, string> Option value to label map.
     */
    public static function getReasoningEffortOptions(): array
    {
        return [
            '' => 'API default',
            'none' => 'None',
            'minimal' => 'Minimal',
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'xhigh' => 'Extra high',
        ];
    }

    /**
     * Gets model suggestions for the settings UI.
     *
     * @since 1.0.4
     *
     * @return list<string> Model suggestions.
     */
    public static function getModelSuggestions(): array
    {
        return [
            'gpt-5.5',
            'gpt-5.4',
            'gpt-5.4-mini',
            'gpt-5.3-codex',
            'gpt-5.3-codex-spark',
            'gpt-5.2',
            'gpt-5.1',
            'gpt-5.1-mini',
            'gpt-5',
            'gpt-5-mini',
            'gpt-4.1',
            'gpt-4.1-mini',
            'o4-mini',
            'o3-mini',
        ];
    }

    /**
     * Gets image model suggestions for the settings UI.
     *
     * @since 1.0.4
     *
     * @return list<string> Image model suggestions.
     */
    public static function getImageModelSuggestions(): array
    {
        return [
            'gpt-image-2',
            'gpt-image-1.5',
            'gpt-image-1',
            'dall-e-3',
            'dall-e-2',
        ];
    }

    /**
     * Gets the default settings.
     *
     * @since 1.0.4
     *
     * @return array<string, string> Default settings.
     */
    private static function defaultSettings(): array
    {
        return [
            'api_url' => self::DEFAULT_API_URL,
            'api_key' => '',
            'model' => '',
            'image_model' => '',
            'reasoning_effort' => '',
        ];
    }

    /**
     * Gets the API key from the WordPress AI Connector option.
     *
     * @since 1.0.4
     *
     * @return string Saved connector API key, or an empty string when none is configured.
     */
    private static function getConnectorApiKeyFromOption(): string
    {
        if (!function_exists('get_option')) {
            return '';
        }

        $apiKey = get_option(self::CONNECTOR_API_KEY_OPTION_NAME, '');
        if ($apiKey !== '') {
            return self::sanitizeApiKey($apiKey);
        }

        foreach (self::CONNECTOR_API_KEY_OPTION_ALIASES as $optionName) {
            $apiKey = get_option($optionName, '');
            if ($apiKey !== '') {
                return self::sanitizeApiKey($apiKey);
            }
        }

        return '';
    }

    /**
     * Stores the API key in the WordPress AI Connector option.
     *
     * @since 1.0.4
     *
     * @param string $apiKey API key.
     * @return void
     */
    private static function updateConnectorApiKeyOption(string $apiKey): void
    {
        if (!function_exists('update_option')) {
            return;
        }

        update_option(self::CONNECTOR_API_KEY_OPTION_NAME, $apiKey);
        foreach (self::CONNECTOR_API_KEY_OPTION_ALIASES as $optionName) {
            update_option($optionName, $apiKey);
        }
    }

    /**
     * Deletes the API key from the WordPress AI Connector option.
     *
     * @since 1.0.4
     *
     * @return void
     */
    private static function deleteConnectorApiKeyOption(): void
    {
        if (!function_exists('delete_option')) {
            return;
        }

        delete_option(self::CONNECTOR_API_KEY_OPTION_NAME);
        foreach (self::CONNECTOR_API_KEY_OPTION_ALIASES as $optionName) {
            delete_option($optionName);
        }
    }

    /**
     * Deletes an API key saved by earlier versions of this plugin.
     *
     * @since 1.0.4
     *
     * @return void
     */
    private static function deleteLegacyApiKey(): void
    {
        if (!function_exists('get_option') || !function_exists('update_option')) {
            return;
        }

        $storedSettings = get_option(self::OPTION_NAME, []);
        if (!is_array($storedSettings) || !isset($storedSettings['api_key'])) {
            return;
        }

        $storedSettings['api_key'] = '';
        update_option(self::OPTION_NAME, $storedSettings);
    }

    /**
     * Gets an API key saved by earlier versions of this plugin.
     *
     * @since 1.0.4
     *
     * @return string Legacy saved API key, or an empty string when none is configured.
     */
    private static function getLegacyStoredApiKey(): string
    {
        if (!function_exists('get_option')) {
            return '';
        }

        $storedSettings = get_option(self::OPTION_NAME, []);
        if (!is_array($storedSettings)) {
            return '';
        }

        return self::sanitizeApiKey($storedSettings['api_key'] ?? '');
    }

    /**
     * Gets allowed reasoning effort values.
     *
     * @since 1.0.4
     *
     * @return list<string> Allowed values.
     */
    private static function reasoningEffortValues(): array
    {
        return [
            '',
            'none',
            'minimal',
            'low',
            'medium',
            'high',
            'xhigh',
        ];
    }

    /**
     * Checks whether a model ID likely supports the Responses API reasoning field.
     *
     * @since 1.0.4
     *
     * @param string $modelId The model ID.
     * @return bool True if the model likely supports reasoning controls.
     */
    private static function modelSupportsReasoning(string $modelId): bool
    {
        $modelId = strtolower($modelId);

        return (bool) preg_match('/^(o1|o3|o4|gpt-5|gpt-oss)/', $modelId)
            || strpos($modelId, 'reason') !== false
            || strpos($modelId, 'thinking') !== false
            || strpos($modelId, 'deepseek-r1') !== false
            || strpos($modelId, 'gemini-2.5') !== false
            || strpos($modelId, 'claude-4') !== false;
    }

    /**
     * Checks whether a model uses fixed sampling parameters.
     *
     * @since 1.0.4
     *
     * @param string $modelId The model ID.
     * @return bool True if temperature/top_p should be omitted.
     */
    private static function modelUsesFixedSampling(string $modelId): bool
    {
        $modelId = strtolower($modelId);

        return (bool) preg_match('/^(o1|o3|o4|gpt-5|gpt-oss)/', $modelId);
    }

    /**
     * Normalizes an API URL to the provider base URL.
     *
     * @since 1.0.4
     *
     * @param string $apiUrl Raw API URL.
     * @return string Normalized API base URL.
     */
    private static function normalizeApiUrl(string $apiUrl): string
    {
        $apiUrl = self::trimTrailingSlashes($apiUrl);
        if ($apiUrl === '') {
            return self::DEFAULT_API_URL;
        }

        foreach (['/responses', '/response', '/chat/completions', '/images/generations', '/models'] as $suffix) {
            if (self::endsWith($apiUrl, $suffix)) {
                $apiUrl = substr($apiUrl, 0, -strlen($suffix));
                break;
            }
        }

        return self::trimTrailingSlashes($apiUrl);
    }

    /**
     * Sanitizes a generic string setting.
     *
     * @since 1.0.4
     *
     * @param mixed $value Raw value.
     * @return string Sanitized value.
     */
    private static function sanitizeString($value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $value = trim((string) $value);
        if (function_exists('sanitize_text_field')) {
            return sanitize_text_field($value);
        }

        return trim(strip_tags($value));
    }

    /**
     * Sanitizes an API URL while preserving optional endpoint paths.
     *
     * @since 1.0.4
     *
     * @param mixed $value Raw value.
     * @return string Sanitized API URL.
     */
    private static function sanitizeApiUrl($value): string
    {
        $apiUrl = self::trimTrailingSlashes(self::sanitizeString($value));

        return $apiUrl !== '' ? $apiUrl : self::DEFAULT_API_URL;
    }

    /**
     * Sanitizes an API key while preserving common key characters.
     *
     * @since 1.0.4
     *
     * @param mixed $value Raw value.
     * @return string Sanitized API key.
     */
    private static function sanitizeApiKey($value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        return preg_replace('/[\r\n]+/', '', trim((string) $value)) ?? '';
    }

    /**
     * Trims trailing slashes from a URL-like string.
     *
     * @since 1.0.4
     *
     * @param string $value Raw value.
     * @return string Trimmed value.
     */
    private static function trimTrailingSlashes(string $value): string
    {
        return rtrim(trim($value), "/ \t\n\r\0\x0B");
    }

    /**
     * Checks whether a string ends with the given suffix.
     *
     * @since 1.0.4
     *
     * @param string $value String to check.
     * @param string $suffix Suffix to look for.
     * @return bool True if the string ends with the suffix.
     */
    private static function endsWith(string $value, string $suffix): bool
    {
        if ($suffix === '') {
            return true;
        }

        return substr($value, -strlen($suffix)) === $suffix;
    }
}
