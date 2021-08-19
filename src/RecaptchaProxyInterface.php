<?php

declare(strict_types=1);

namespace Pollen\Recaptcha;

interface RecaptchaProxyInterface
{
    /**
     * Resolves Recaptcha instance.
     *
     * @return RecaptchaInterface
     */
    public function recaptcha(): RecaptchaInterface;

    /**
     * Sets Recaptcha instance.
     *
     * @param RecaptchaInterface $recaptcha
     *
     * @return void
     */
    public function setRecaptcha(RecaptchaInterface $recaptcha): void;
}