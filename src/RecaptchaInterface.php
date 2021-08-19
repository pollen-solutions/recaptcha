<?php

declare(strict_types=1);

namespace Pollen\Recaptcha;

use Pollen\Support\Concerns\BootableTraitInterface;
use Pollen\Support\Concerns\ConfigBagAwareTraitInterface;
use Pollen\Support\Concerns\ResourcesAwareTraitInterface;
use Pollen\Support\Proxy\ContainerProxyInterface;
use Pollen\Support\Proxy\EventProxyInterface;
use Pollen\Support\Proxy\FieldProxyInterface;
use Pollen\Support\Proxy\FormProxyInterface;
use Pollen\Support\Proxy\HttpRequestProxyInterface;
use Pollen\Recaptcha\Exception\RecaptchaSiteKeyException;
use Pollen\Recaptcha\Exception\RecaptchaSecretKeyException;
use ReCaptcha\ReCaptcha as ReCaptchaDriver;
use ReCaptcha\Response as ReCaptchaResponse;

interface RecaptchaInterface extends
    BootableTraitInterface,
    ConfigBagAwareTraitInterface,
    ResourcesAwareTraitInterface,
    ContainerProxyInterface,
    EventProxyInterface,
    FieldProxyInterface,
    FormProxyInterface,
    HttpRequestProxyInterface
{
    /**
     * Add a widget render.
     *
     * @param string $id
     *
     * @return static
     */
    public function addWidgetRender(string $id): RecaptchaInterface;

    /**
     * Assets autoloading.
     *
     * @return void
     */
    public function assetsAutoloader(): void;

    /**
     * Booting.
     *
     * @return static
     */
    public function boot(): RecaptchaInterface;

    /**
     * Checking the validity of the configuration.
     *
     * @return bool
     *
     * @throws RecaptchaSiteKeyException
     * @throws RecaptchaSecretKeyException
     */
    public function checkConfig(): bool;

    /**
     * Get Handle HTTP response.
     *
     * @param string|null $value
     * @param string|null $ip empty string to disable remoteIP verify
     *
     * @return ReCaptchaResponse
     */
    public function getHandleResponse(?string $value = null, ?string $ip = null): ReCaptchaResponse;

    /**
     * Get Js scripts.
     *
     * @return string
     */
    public function getJsScripts(): string;

    /**
     * Get language.
     *
     * @return string
     */
    public function getLanguage(): string;

    /**
     * Get site key.
     *
     * @return string|null
     */
    public function getSiteKey(): ?string;

    /**
     * Check if HTTP Response is valid.
     *
     * @param string $value
     *
     * @return bool
     */
    public function isResponseValid(string $value): bool;

    /**
     * Recaptcha driver instance.
     *
     * @return ReCaptchaDriver
     */
    public function reCaptchaDriver(): ReCaptchaDriver;
}