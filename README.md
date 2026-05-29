# OpenAI-compatible AI Connector

An OpenAI-compatible AI connector for the [PHP AI Client](https://github.com/WordPress/php-ai-client) SDK. Works as both a Composer package and a WordPress plugin.

## Requirements

- PHP 7.4 or higher
- When using with WordPress, requires WordPress 7.0 or higher
    - If using an older WordPress release, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed

## Installation

### As a Composer Package

```bash
composer require wordpress/ai-provider-for-openai
```

### As a WordPress Plugin

1. Download the plugin files
2. Upload to `/wp-content/plugins/ai-provider-for-openai/`
3. Ensure the PHP AI Client plugin is installed and activated
4. Activate the plugin through the WordPress admin
5. Configure the API URL, API key, default text model, image model, and reasoning effort under Settings > OpenAI-compatible AI Connector

## Usage

### With WordPress

The provider automatically registers itself with the PHP AI Client on the `init` hook. Ensure both plugins are active and configure your API key in Settings > OpenAI-compatible AI Connector, or via `OPENAI_COMPATIBLE_API_KEY`:

```php
// Set your API key (or use the OPENAI_COMPATIBLE_API_KEY environment variable)
putenv('OPENAI_COMPATIBLE_API_KEY=your-api-key');

// Use the provider
$result = AiClient::prompt('Hello, world!')
    ->usingProvider('openai-compatible')
    ->generateTextResult();
```

### As a Standalone Package

```php
use WordPress\AiClient\AiClient;
use WordPress\OpenAiAiProvider\Provider\OpenAiProvider;

// Register the provider
$registry = AiClient::defaultRegistry();
$registry->registerProvider(OpenAiProvider::class);

// Set your API key
putenv('OPENAI_COMPATIBLE_API_KEY=your-api-key');

// Generate text
$result = AiClient::prompt('Explain quantum computing')
    ->usingProvider('openai-compatible')
    ->generateTextResult();

echo $result->toText();
```

## Supported Models

Available models are dynamically discovered from the OpenAI API or a compatible API. Unknown non-media models from compatible APIs are exposed as text generation models.

When connected to a gateway such as sub2api, the provider reads the remote `/models` response and exposes those model IDs to the WordPress AI plugin. The AI plugin can then filter models by capability, so text features can choose text models while image generation can choose GPT Image or DALL-E models from the same remote list.

If a compatible gateway uses a custom image model ID, configure it in the Image Generation Model setting. That model is added to the model list and exposed with image generation capability even when its ID does not start with `gpt-image-` or `dall-e-`.

## Configuration

The WordPress plugin settings page supports:

- Custom OpenAI-compatible API URL, defaulting to `https://api.openai.com/v1`
- API key, stored separately from the official OpenAI connector
- Optional default text model to add to the discovered model list
- Optional image generation model to add to the discovered model list
- Reasoning effort for Responses API requests

Text generation requests use `/responses` by default. A full `/responses` endpoint may also be entered directly; legacy `/response` paths are retried as `/responses` when unavailable.
Sampling controls such as `temperature` and `top_p` are omitted for fixed-sampling Responses models such as GPT-5 and o-series models.

The provider supports `OPENAI_COMPATIBLE_API_KEY` for authentication. It also accepts `OPENAI_API_KEY` as a legacy fallback.

```php
putenv('OPENAI_COMPATIBLE_API_KEY=your-api-key');
```

## License

GPL-2.0-or-later
