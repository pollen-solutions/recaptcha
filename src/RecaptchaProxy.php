<?php

declare(strict_types=1);

namespace Pollen\Recaptcha;

use Pollen\Support\ProxyResolver;
use RuntimeException;

/**
 * @see \Pollen\Recaptcha\RecaptchaProxyInterface
 */
trait RecaptchaProxy
{
    /**
     * Recaptcha instance.
     * @var RecaptchaInterface|null
     */
    private ?RecaptchaInterface $recaptcha = null;

    /**
     * Resolves Recaptcha instance.
     *
     * @return RecaptchaInterface
     */
    public function recaptcha(): RecaptchaInterface
    {
        if ($this->recaptcha === null) {
            try {
                $this->recaptcha = Recaptcha::getInstance();
            } catch (RuntimeException $e) {
                $this->recaptcha = ProxyResolver::getInstance(
                    RecaptchaInterface::class,
                    Recaptcha::class,
                    method_exists($this, 'getContainer') ? $this->getContainer() : null
                );
            }
        }

        return $this->recaptcha;
    }

    /**
     * Sets Recaptcha instance.
     *
     * @param RecaptchaInterface $recaptcha
     *
     * @return void
     */
    public function setRecaptcha(RecaptchaInterface $recaptcha): void
    {
        $this->recaptcha = $recaptcha;
    }
}