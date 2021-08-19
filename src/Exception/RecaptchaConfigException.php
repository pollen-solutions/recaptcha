<?php

declare(strict_types=1);

namespace Pollen\Recaptcha\Exception;

use LogicException;

class RecaptchaConfigException extends LogicException
{
    /**
     * Online config url.
     * @var string
     */
    protected string $onlineConfig = 'https://www.google.com/recaptcha/about/';
}