=== OpenAI-compatible AI Connector ===
Contributors: wordpressdotorg
Tags: ai, openai, gpt, artificial-intelligence, connector
Requires at least: 6.9
Tested up to: 7.0
Stable tag: 1.0.4
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

OpenAI-compatible AI connector for the PHP AI Client SDK.

== Description ==

This plugin provides an OpenAI-compatible connector for the PHP AI Client SDK. It enables WordPress sites to use GPT-style text models, image generation models, and other compatible AI capabilities through a configurable API endpoint.

**Features:**

* Text generation with GPT models
* Image generation with DALL-E models
* Function calling support
* Web search support
* Custom OpenAI-compatible API URL, API key, default text model, image model, and reasoning effort settings
* Automatic provider registration

Available models are dynamically discovered from the OpenAI API or a compatible API. When connected to a gateway such as sub2api, the remote model list is exposed to the WordPress AI plugin so users can select text and image models per feature.

If a compatible gateway uses a custom image model ID, configure it in the Image Generation Model setting. That model is added to the model list and exposed with image generation capability.

**Requirements:**

* PHP 7.4 or higher
* For WordPress 6.9, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed
* For WordPress 7.0 and above, no additional changes are required
* API key for your OpenAI-compatible endpoint

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/ai-provider-for-openai/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your API URL, API key, default text model, image model, and reasoning effort under Settings > OpenAI-compatible AI Connector. You may also use the `OPENAI_COMPATIBLE_API_KEY` environment variable or constant.

== Frequently Asked Questions ==

= How do I get an API key? =

Use the API key from your OpenAI-compatible provider. For OpenAI, visit the [OpenAI Platform](https://platform.openai.com/).

= Does this plugin work without the PHP AI Client? =

No, this plugin requires the PHP AI Client plugin to be installed and activated. It provides the OpenAI-compatible implementation that the PHP AI Client uses.

== Changelog ==

= 1.0.4 =

* Rename the provider metadata to `openai-compatible` so it no longer conflicts with the official OpenAI connector.
* Add a settings page for custom API URL, API key, default text model, image model, and reasoning effort.
* Store the API key in a separate WordPress AI Connector option for this connector.
* Use `/responses` by default for text generation requests, while retrying legacy `/response` endpoints as `/responses` when unavailable.
* Add the configured text and image models to the model list when compatible APIs do not return them.
* Expose the configured image model with image generation capability for custom gateway model IDs.
* Document that compatible gateway model lists, such as sub2api `/models`, are exposed for per-feature model selection in the WordPress AI plugin.
* Send `reasoning.effort` with Responses API requests when configured.
* Omit sampling controls for fixed-sampling Responses models such as GPT-5 and o-series models.
* Treat unknown non-media models from OpenAI-compatible APIs as text generation models unless they look non-generative.

= 1.0.3 =

* Add a provider logo to the metadata if the client version > 1.3.0 ([#19](https://github.com/WordPress/ai-provider-for-openai/pull/19)).
* Fix mapping of models that support multimodal inputs ([#22](https://github.com/WordPress/ai-provider-for-openai/pull/22)).

= 1.0.2 =

* Add plugin directory assets by @shaunandrews in https://github.com/WordPress/ai-provider-for-openai/pull/7
* Update tags in readme.txt by @jeffpaul in https://github.com/WordPress/ai-provider-for-openai/pull/9
* Fix missing input and output modality combinations. by @felixarntz in https://github.com/WordPress/ai-provider-for-openai/pull/11
* Add provider description by @felixarntz in https://github.com/WordPress/ai-provider-for-openai/pull/12

= 1.0.1 =

* Initial release of the plugin
* Support for GPT text generation models
* Support for DALL-E image generation models
* Function calling support
* Web search support

= 1.0.0 =

* Initial release of the Composer package
