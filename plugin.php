<?php

/**
 * Plugin Name: OpenAI-compatible AI Connector
 * Plugin URI: https://github.com/WordPress/ai-provider-for-openai
 * Description: OpenAI-compatible AI connector for the WordPress AI Client.
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Version: 1.0.4
 * Author: WordPress AI Team
 * Author URI: https://make.wordpress.org/ai/
 * License: GPL-2.0-or-later
 * License URI: https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain: ai-provider-for-openai
 *
 * @package WordPress\OpenAiAiProvider
 */

declare(strict_types=1);

namespace WordPress\OpenAiAiProvider;

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\OpenAiAiProvider\Provider\OpenAiProvider;
use WordPress\OpenAiAiProvider\Settings\OpenAiSettings;

if (!defined('ABSPATH')) {
    return;
}

require_once __DIR__ . '/src/autoload.php';

/**
 * Registers the OpenAI-compatible AI Connector with the AI Client.
 *
 * @since 1.0.0
 *
 * @return void
 */
function register_provider(): void
{
    if (!class_exists(AiClient::class)) {
        return;
    }

    $registry = AiClient::defaultRegistry();

    if ($registry->hasProvider(OpenAiProvider::class)) {
        return;
    }

    $registry->registerProvider(OpenAiProvider::class);
}

add_action('init', __NAMESPACE__ . '\\register_provider', 5);

/**
 * Applies API key settings to the AI Client registry.
 *
 * @since 1.0.4
 *
 * @return void
 */
function configure_provider_request_authentication(): void
{
    if (!class_exists(AiClient::class) || !class_exists(ApiKeyRequestAuthentication::class)) {
        return;
    }

    $apiKey = OpenAiSettings::getApiKey();
    if ($apiKey === '') {
        return;
    }

    $registry = AiClient::defaultRegistry();
    if (!method_exists($registry, 'setProviderRequestAuthentication')) {
        return;
    }

    $providerId = OpenAiProvider::PROVIDER_ID;
    if (!$registry->hasProvider($providerId) && !$registry->hasProvider(OpenAiProvider::class)) {
        return;
    }

    $registry->setProviderRequestAuthentication(
        $registry->hasProvider($providerId) ? $providerId : OpenAiProvider::class,
        new ApiKeyRequestAuthentication($apiKey)
    );
}

add_action('init', __NAMESPACE__ . '\\configure_provider_request_authentication', 30);

/**
 * Reports whether the OpenAI-compatible connector has usable credentials.
 *
 * This keeps the WordPress AI plugin's connector check in sync with credentials
 * configured through this provider's settings or OPENAI_COMPATIBLE_API_KEY.
 *
 * @since 1.0.4
 *
 * @param mixed $hasCredentials Whether AI credentials have already been detected.
 * @return bool Whether usable AI credentials are available.
 */
function has_ai_credentials($hasCredentials): bool
{
    if ($hasCredentials) {
        return true;
    }

    return OpenAiSettings::getApiKey() !== '';
}

add_filter('wpai_has_ai_credentials', __NAMESPACE__ . '\\has_ai_credentials');

/**
 * Prepends the configured OpenAI-compatible text model to AI plugin defaults.
 *
 * @since 1.0.4
 *
 * @param array<int, mixed> $preferredModels Existing preferred models.
 * @return array<int, mixed> Updated preferred models.
 */
function add_preferred_text_model(array $preferredModels): array
{
    $modelId = OpenAiSettings::getDefaultModel();
    if ($modelId === '') {
        return $preferredModels;
    }

    return prepend_provider_model_preference($preferredModels, $modelId);
}

add_filter('wpai_preferred_text_models', __NAMESPACE__ . '\\add_preferred_text_model');

/**
 * Prepends the configured OpenAI-compatible image model to AI plugin defaults.
 *
 * @since 1.0.4
 *
 * @param array<int, mixed> $preferredModels Existing preferred models.
 * @return array<int, mixed> Updated preferred models.
 */
function add_preferred_image_model(array $preferredModels): array
{
    $modelId = OpenAiSettings::getDefaultImageModel();
    if ($modelId === '') {
        return $preferredModels;
    }

    return prepend_provider_model_preference($preferredModels, $modelId);
}

add_filter('wpai_preferred_image_models', __NAMESPACE__ . '\\add_preferred_image_model');

/**
 * Prepends a provider/model preference without duplicating an existing entry.
 *
 * @since 1.0.4
 *
 * @param array<int, mixed> $preferredModels Existing preferred models.
 * @param string            $modelId         Model ID.
 * @return array<int, mixed> Updated preferred models.
 */
function prepend_provider_model_preference(array $preferredModels, string $modelId): array
{
    $preference = [OpenAiProvider::PROVIDER_ID, $modelId];

    $preferredModels = array_values(
        array_filter(
            $preferredModels,
            static function ($item) use ($preference): bool {
                return !is_array($item)
                    || count($item) !== 2
                    || $item[0] !== $preference[0]
                    || $item[1] !== $preference[1];
            }
        )
    );

    array_unshift($preferredModels, $preference);

    return $preferredModels;
}

/**
 * Registers the plugin settings.
 *
 * @since 1.0.4
 *
 * @return void
 */
function register_settings(): void
{
    register_setting(
        'ai_provider_for_openai',
        OpenAiSettings::OPTION_NAME,
        [
            'type' => 'array',
            'sanitize_callback' => [OpenAiSettings::class, 'sanitizeSettings'],
            'default' => [
                'api_url' => OpenAiSettings::DEFAULT_API_URL,
                'api_key' => '',
                'model' => '',
                'image_model' => '',
                'reasoning_effort' => '',
            ],
        ]
    );

    add_settings_section(
        'ai_provider_for_openai_api',
        __('OpenAI-compatible API', 'ai-provider-for-openai'),
        '__return_false',
        'ai-provider-for-openai'
    );

    add_settings_field(
        'ai_provider_for_openai_api_url',
        __('API URL', 'ai-provider-for-openai'),
        __NAMESPACE__ . '\\render_api_url_field',
        'ai-provider-for-openai',
        'ai_provider_for_openai_api',
        ['label_for' => 'ai_provider_for_openai_api_url']
    );

    add_settings_field(
        'ai_provider_for_openai_api_key',
        __('API Key', 'ai-provider-for-openai'),
        __NAMESPACE__ . '\\render_api_key_field',
        'ai-provider-for-openai',
        'ai_provider_for_openai_api',
        ['label_for' => 'ai_provider_for_openai_api_key']
    );

    add_settings_field(
        'ai_provider_for_openai_model',
        __('Default Text Model', 'ai-provider-for-openai'),
        __NAMESPACE__ . '\\render_model_field',
        'ai-provider-for-openai',
        'ai_provider_for_openai_api',
        ['label_for' => 'ai_provider_for_openai_model']
    );

    add_settings_field(
        'ai_provider_for_openai_image_model',
        __('Image Generation Model', 'ai-provider-for-openai'),
        __NAMESPACE__ . '\\render_image_model_field',
        'ai-provider-for-openai',
        'ai_provider_for_openai_api',
        ['label_for' => 'ai_provider_for_openai_image_model']
    );

    add_settings_field(
        'ai_provider_for_openai_reasoning_effort',
        __('Reasoning Effort', 'ai-provider-for-openai'),
        __NAMESPACE__ . '\\render_reasoning_effort_field',
        'ai-provider-for-openai',
        'ai_provider_for_openai_api',
        ['label_for' => 'ai_provider_for_openai_reasoning_effort']
    );
}

add_action('admin_init', __NAMESPACE__ . '\\register_settings');

/**
 * Adds the settings page.
 *
 * @since 1.0.4
 *
 * @return void
 */
function add_settings_page(): void
{
    add_options_page(
        __('OpenAI-compatible AI Connector', 'ai-provider-for-openai'),
        __('OpenAI-compatible AI Connector', 'ai-provider-for-openai'),
        'manage_options',
        'ai-provider-for-openai',
        __NAMESPACE__ . '\\render_settings_page'
    );
}

add_action('admin_menu', __NAMESPACE__ . '\\add_settings_page');

/**
 * Adds a Settings link to the plugins list.
 *
 * @since 1.0.4
 *
 * @param list<string> $links Plugin action links.
 * @return list<string> Updated plugin action links.
 */
function add_plugin_action_links(array $links): array
{
    $settingsLink = sprintf(
        '<a href="%s">%s</a>',
        esc_url(admin_url('options-general.php?page=ai-provider-for-openai')),
        esc_html__('Settings', 'ai-provider-for-openai')
    );

    array_unshift($links, $settingsLink);

    return $links;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), __NAMESPACE__ . '\\add_plugin_action_links');

/**
 * Renders the settings page.
 *
 * @since 1.0.4
 *
 * @return void
 */
function render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('OpenAI-compatible AI Connector', 'ai-provider-for-openai'); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('ai_provider_for_openai');
            do_settings_sections('ai-provider-for-openai');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Renders the API URL field.
 *
 * @since 1.0.4
 *
 * @return void
 */
function render_api_url_field(): void
{
    $settings = OpenAiSettings::getSettings();
    ?>
    <input
        type="text"
        class="regular-text code"
        id="ai_provider_for_openai_api_url"
        name="<?php echo esc_attr(OpenAiSettings::OPTION_NAME); ?>[api_url]"
        value="<?php echo esc_attr($settings['api_url']); ?>"
        placeholder="<?php echo esc_attr(OpenAiSettings::DEFAULT_API_URL); ?>"
    />
    <p class="description">
        <?php
        echo esc_html__('Use an OpenAI-compatible base URL, such as the default value.', 'ai-provider-for-openai');
        echo ' ';
        echo esc_html__('Responses API requests use /responses by default.', 'ai-provider-for-openai');
        echo ' ';
        echo esc_html__(
            'Full /responses endpoints are accepted; legacy /response retries as /responses when unavailable.',
            'ai-provider-for-openai'
        );
        ?>
    </p>
    <?php
}

/**
 * Renders the API key field.
 *
 * @since 1.0.4
 *
 * @return void
 */
function render_api_key_field(): void
{
    $settings = OpenAiSettings::getSettings();
    $hasApiKey = $settings['api_key'] !== '';
    ?>
    <input
        type="password"
        class="regular-text code"
        id="ai_provider_for_openai_api_key"
        name="<?php echo esc_attr(OpenAiSettings::OPTION_NAME); ?>[api_key]"
        value=""
        placeholder="<?php echo esc_attr($hasApiKey ? __('Saved API key', 'ai-provider-for-openai') : ''); ?>"
        autocomplete="off"
    />
    <?php if ($hasApiKey) : ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr(OpenAiSettings::OPTION_NAME); ?>[clear_api_key]"
                value="1"
            />
            <?php echo esc_html__('Clear saved API key', 'ai-provider-for-openai'); ?>
        </label>
    <?php endif; ?>
    <p class="description">
        <?php
        echo esc_html__(
            'Leave empty to keep the saved key, or use OPENAI_COMPATIBLE_API_KEY.',
            'ai-provider-for-openai'
        );
        echo ' ';
        echo esc_html__(
            'The saved key is stored separately from the official OpenAI connector.',
            'ai-provider-for-openai'
        );
        ?>
    </p>
    <?php
}

/**
 * Renders the default model field.
 *
 * @since 1.0.4
 *
 * @return void
 */
function render_model_field(): void
{
    $settings = OpenAiSettings::getSettings();
    ?>
    <input
        type="text"
        class="regular-text code"
        id="ai_provider_for_openai_model"
        name="<?php echo esc_attr(OpenAiSettings::OPTION_NAME); ?>[model]"
        value="<?php echo esc_attr($settings['model']); ?>"
        list="ai_provider_for_openai_model_suggestions"
        placeholder="gpt-5.4"
    />
    <datalist id="ai_provider_for_openai_model_suggestions">
        <?php foreach (OpenAiSettings::getModelSuggestions() as $modelId) : ?>
            <option value="<?php echo esc_attr($modelId); ?>"></option>
        <?php endforeach; ?>
    </datalist>
    <p class="description">
        <?php
        echo esc_html__('Optional. The configured model is added to the model list.', 'ai-provider-for-openai');
        echo ' ';
        echo esc_html__(
            'Use this for text features; image generation has its own model setting below.',
            'ai-provider-for-openai'
        );
        ?>
    </p>
    <?php
}

/**
 * Renders the image generation model field.
 *
 * @since 1.0.4
 *
 * @return void
 */
function render_image_model_field(): void
{
    $settings = OpenAiSettings::getSettings();
    ?>
    <input
        type="text"
        class="regular-text code"
        id="ai_provider_for_openai_image_model"
        name="<?php echo esc_attr(OpenAiSettings::OPTION_NAME); ?>[image_model]"
        value="<?php echo esc_attr($settings['image_model']); ?>"
        list="ai_provider_for_openai_image_model_suggestions"
        placeholder="gpt-image-2"
    />
    <datalist id="ai_provider_for_openai_image_model_suggestions">
        <?php foreach (OpenAiSettings::getImageModelSuggestions() as $modelId) : ?>
            <option value="<?php echo esc_attr($modelId); ?>"></option>
        <?php endforeach; ?>
    </datalist>
    <p class="description">
        <?php
        echo esc_html__(
            'Optional. This model is added to the remote model list and exposed as an image generation model.',
            'ai-provider-for-openai'
        );
        echo ' ';
        echo esc_html__(
            'Use it when a compatible gateway returns custom model IDs for image generation.',
            'ai-provider-for-openai'
        );
        ?>
    </p>
    <?php
}

/**
 * Renders the reasoning effort field.
 *
 * @since 1.0.4
 *
 * @return void
 */
function render_reasoning_effort_field(): void
{
    $settings = OpenAiSettings::getSettings();
    ?>
    <select
        id="ai_provider_for_openai_reasoning_effort"
        name="<?php echo esc_attr(OpenAiSettings::OPTION_NAME); ?>[reasoning_effort]"
    >
        <?php foreach (OpenAiSettings::getReasoningEffortOptions() as $value => $label) : ?>
            <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['reasoning_effort'], $value); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description">
        <?php
        echo esc_html__(
            'When selected, this sends reasoning.effort on Responses API requests.',
            'ai-provider-for-openai'
        );
        ?>
    </p>
    <?php
}
