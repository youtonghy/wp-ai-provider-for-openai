# OpenAI-compatible AI Connector / OpenAI 兼容 AI 连接器

An OpenAI API format-compatible connector for the [PHP AI Client](https://github.com/WordPress/php-ai-client) SDK. It works as both a Composer package and a WordPress plugin.

这是一个面向 [PHP AI Client](https://github.com/WordPress/php-ai-client) SDK 的 OpenAI API 格式兼容连接器，可作为 Composer 包或 WordPress 插件使用。

This project is not OpenAI itself and is not the official OpenAI connector. It provides a connector layer for services that expose OpenAI-compatible API endpoints, while keeping its settings and credentials separate from any official OpenAI integration.

本项目不是 OpenAI 本身，也不是 OpenAI 官方连接器。它提供的是一个兼容 OpenAI API 格式的接入层，用于连接支持 OpenAI 兼容接口的模型服务，并与官方 OpenAI 集成的设置和凭据保持独立。

## Features / 功能

- English and Chinese bilingual documentation and usage guidance
- WordPress plugin and standalone Composer package support
- Configurable OpenAI-compatible base URL and API key
- GPT-style text generation through compatible Responses API endpoints
- Image generation through compatible image generation endpoints
- Function calling and web search support when the connected model supports them
- Dynamic model discovery from compatible `/models` endpoints
- Optional default text and image model settings for gateways that do not expose full model metadata

- 提供中英文双语说明与使用指引
- 同时支持 WordPress 插件和独立 Composer 包两种使用方式
- 可配置 OpenAI 兼容接口的 Base URL 与 API Key
- 通过兼容 Responses API 的接口调用 GPT 风格文本生成模型
- 通过兼容图像生成接口调用图片生成模型
- 在所连接模型支持时，可使用函数调用与联网搜索能力
- 可从兼容 `/models` 接口动态发现模型
- 当网关未返回完整模型元数据时，可手动设置默认文本模型与图像模型

## Requirements / 环境要求

- PHP 7.4 or higher
- When using with WordPress, requires WordPress 7.0 or higher
    - If using an older WordPress release, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed

- PHP 7.4 或更高版本
- 作为 WordPress 插件使用时，建议 WordPress 7.0 或更高版本
    - 如果使用较旧的 WordPress 版本，需要安装 [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) 包

## Installation / 安装

### As a Composer Package / 作为 Composer 包

```bash
composer require wordpress/ai-provider-for-openai
```

### As a WordPress Plugin / 作为 WordPress 插件

1. Download the plugin files
2. Upload them to `/wp-content/plugins/ai-provider-for-openai/`
3. Ensure the PHP AI Client plugin is installed and activated
4. Activate this plugin through the WordPress admin
5. Configure the API URL, API key, default text model, image model, and reasoning effort under Settings > OpenAI-compatible AI Connector

1. 下载插件文件
2. 上传到 `/wp-content/plugins/ai-provider-for-openai/`
3. 确认 PHP AI Client 插件已安装并启用
4. 在 WordPress 后台启用本插件
5. 在 Settings > OpenAI-compatible AI Connector 中配置 API URL、API Key、默认文本模型、图像模型和 reasoning effort

## Usage / 使用

### With WordPress / 在 WordPress 中使用

The provider automatically registers itself with the PHP AI Client on the `init` hook. Ensure both plugins are active and configure the API key from your OpenAI-compatible provider in Settings > OpenAI-compatible AI Connector, or via `OPENAI_COMPATIBLE_API_KEY`:

连接器会在 WordPress 的 `init` 钩子中自动注册到 PHP AI Client。请确认两个插件均已启用，并在 Settings > OpenAI-compatible AI Connector 中配置你的 OpenAI 兼容服务 API Key，或通过 `OPENAI_COMPATIBLE_API_KEY` 设置：

```php
// Set your API key for the OpenAI-compatible endpoint.
putenv('OPENAI_COMPATIBLE_API_KEY=your-api-key');

$result = AiClient::prompt('Hello, world!')
    ->usingProvider('openai-compatible')
    ->generateTextResult();
```

### As a Standalone Package / 作为独立包使用

```php
use WordPress\AiClient\AiClient;
use WordPress\OpenAiAiProvider\Provider\OpenAiProvider;

// Register the OpenAI-compatible provider.
$registry = AiClient::defaultRegistry();
$registry->registerProvider(OpenAiProvider::class);

// Set your API key for the compatible endpoint.
putenv('OPENAI_COMPATIBLE_API_KEY=your-api-key');

$result = AiClient::prompt('Explain quantum computing')
    ->usingProvider('openai-compatible')
    ->generateTextResult();

echo $result->toText();
```

## Supported Models / 支持的模型

Available models are dynamically discovered from an OpenAI-compatible `/models` API. Unknown non-media models from compatible APIs are exposed as text generation models.

可用模型会从兼容 OpenAI 格式的 `/models` API 动态发现。来自兼容接口的未知非媒体模型会作为文本生成模型暴露给 PHP AI Client。

When connected to a gateway such as sub2api, the provider reads the remote `/models` response and exposes those model IDs to the WordPress AI plugin. The AI plugin can then filter models by capability, so text features can choose text models while image generation can choose GPT Image or DALL-E compatible models from the same remote list.

当连接 sub2api 等兼容网关时，连接器会读取远程 `/models` 响应，并将模型 ID 暴露给 WordPress AI 插件。AI 插件随后可以按能力筛选模型，因此文本功能会选择文本模型，图像生成功能会从同一远程模型列表中选择 GPT Image 或 DALL-E 兼容模型。

If a compatible gateway uses a custom image model ID, configure it in the Image Generation Model setting. That model is added to the model list and exposed with image generation capability even when its ID does not start with `gpt-image-` or `dall-e-`.

如果兼容网关使用自定义图像模型 ID，可在 Image Generation Model 设置中配置。即使该模型 ID 不以 `gpt-image-` 或 `dall-e-` 开头，也会被加入模型列表并标记为支持图像生成。

## Configuration / 配置

The WordPress plugin settings page supports:

- Custom OpenAI-compatible API URL, defaulting to `https://api.openai.com/v1`
- API key, stored separately from the official OpenAI connector
- Optional default text model to add to the discovered model list
- Optional image generation model to add to the discovered model list
- Reasoning effort for Responses API requests

WordPress 插件设置页支持：

- 自定义 OpenAI 兼容 API URL，默认值为 `https://api.openai.com/v1`
- API Key，并与官方 OpenAI 连接器的凭据分开存储
- 可选默认文本模型，可加入动态发现的模型列表
- 可选图像生成模型，可加入动态发现的模型列表
- Responses API 请求的 reasoning effort 设置

Text generation requests use `/responses` by default. A full `/responses` endpoint may also be entered directly; legacy `/response` paths are retried as `/responses` when unavailable.

文本生成请求默认使用 `/responses`。也可以直接填写完整的 `/responses` endpoint；如果旧版 `/response` 路径不可用，插件会自动重试 `/responses`。

Sampling controls such as `temperature` and `top_p` are omitted for fixed-sampling Responses models such as GPT-5 and o-series models.

对于 GPT-5、o-series 等固定采样 Responses 模型，插件会省略 `temperature`、`top_p` 等采样参数。

The provider supports `OPENAI_COMPATIBLE_API_KEY` for authentication. It also accepts `OPENAI_API_KEY` as a legacy fallback.

连接器支持通过 `OPENAI_COMPATIBLE_API_KEY` 进行认证，也兼容 `OPENAI_API_KEY` 作为旧版回退。

```php
putenv('OPENAI_COMPATIBLE_API_KEY=your-api-key');
```

## License / 许可证

GPL-2.0-or-later
