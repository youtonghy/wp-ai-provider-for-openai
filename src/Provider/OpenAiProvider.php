<?php

declare(strict_types=1);

namespace WordPress\OpenAiAiProvider\Provider;

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\OpenAiAiProvider\Metadata\OpenAiModelMetadataDirectory;
use WordPress\OpenAiAiProvider\Models\OpenAiImageGenerationModel;
use WordPress\OpenAiAiProvider\Models\OpenAiTextGenerationModel;
use WordPress\OpenAiAiProvider\Settings\OpenAiSettings;

/**
 * Class for the OpenAI-compatible AI Connector.
 *
 * @since 1.0.0
 */
class OpenAiProvider extends AbstractApiProvider
{
    public const PROVIDER_ID = 'openai-compatible';

    /**
     * {@inheritDoc}
     *
     * @since 1.0.0
     */
    protected static function baseUrl(): string
    {
        return OpenAiSettings::getApiBaseUrl();
    }

    /**
     * Gets the URL for the Responses API endpoint.
     *
     * @since 1.0.4
     *
     * @return string Responses API URL.
     */
    public static function responsesUrl(): string
    {
        return OpenAiSettings::getResponsesApiUrl();
    }

    /**
     * {@inheritDoc}
     *
     * @since 1.0.0
     */
    protected static function createModel(
        ModelMetadata $modelMetadata,
        ProviderMetadata $providerMetadata
    ): ModelInterface {
        $capabilities = $modelMetadata->getSupportedCapabilities();
        foreach ($capabilities as $capability) {
            if ($capability->isTextGeneration()) {
                return new OpenAiTextGenerationModel($modelMetadata, $providerMetadata);
            }
            if ($capability->isImageGeneration()) {
                return new OpenAiImageGenerationModel($modelMetadata, $providerMetadata);
            }
            if ($capability->isTextToSpeechConversion()) {
                // TODO: Implement OpenAiTextToSpeechConversionModel.
                throw new RuntimeException(
                    'OpenAI text to speech conversion model class is not yet implemented.'
                );
            }
        }

        throw new RuntimeException(
            'Unsupported model capabilities: ' . implode(', ', $capabilities)
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since 1.0.0
     */
    protected static function createProviderMetadata(): ProviderMetadata
    {
        $providerMetadataArgs = [
            self::PROVIDER_ID,
            'OpenAI-compatible',
            ProviderTypeEnum::cloud(),
            'https://platform.openai.com/api-keys',
            RequestAuthenticationMethod::apiKey()
        ];
        // Provider description support was added in 1.2.0.
        if (version_compare(AiClient::VERSION, '1.2.0', '>=')) {
            // For WordPress, we should translate the description.
            if (function_exists('__')) {
                $providerMetadataArgs[] = __(
                    'Text and image generation through OpenAI-compatible APIs.',
                    'ai-provider-for-openai'
                );
            } else {
                $providerMetadataArgs[] = 'Text and image generation through OpenAI-compatible APIs.';
            }
        }
        return new ProviderMetadata(...$providerMetadataArgs);
    }

    /**
     * {@inheritDoc}
     *
     * @since 1.0.0
     */
    protected static function createProviderAvailability(): ProviderAvailabilityInterface
    {
        // Check valid API access by attempting to list models.
        return new ListModelsApiBasedProviderAvailability(
            static::modelMetadataDirectory()
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since 1.0.0
     */
    protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        return new OpenAiModelMetadataDirectory();
    }
}
