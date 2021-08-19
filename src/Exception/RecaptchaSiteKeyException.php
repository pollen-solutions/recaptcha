<?php

declare(strict_types=1);

namespace Pollen\Recaptcha\Exception;

use Throwable;

class RecaptchaSiteKeyException extends RecaptchaConfigException
{
    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        if ($message === '') {
            $message = sprintf(
                'Recaptcha v2 or V3 Site Key required, please create and configure : %s.',
                $this->onlineConfig
            );
        }

        parent::__construct($message, $code, $previous);
    }
}