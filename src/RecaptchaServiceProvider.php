<?php

declare(strict_types=1);

namespace Pollen\Recaptcha;

use Pollen\Container\BootableServiceProvider;
use Pollen\Field\FieldManagerInterface;
use Pollen\Form\FormManagerInterface;
use Pollen\Recaptcha\Field\RecaptchaField;
use Pollen\Recaptcha\Form\RecaptchaFormField;

class RecaptchaServiceProvider extends BootableServiceProvider
{
    /**
     * @var string[]
     */
    protected $provides = [
        RecaptchaInterface::class,
        RecaptchaField::class,
        RecaptchaFormField::class,
    ];

    /**
     * @inheritDoc
     */
    public function boot(): void
    {
        if ($this->getContainer()->has(FormManagerInterface::class)) {
            $this->getContainer()->get(RecaptchaInterface::class);
        }
    }

    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->getContainer()->share(
            RecaptchaInterface::class,
            function () {
                return new Recaptcha([], $this->getContainer());
            }
        );

        $this->getContainer()->add(
            RecaptchaField::class,
            function () {
                return new RecaptchaField(
                    $this->getContainer()->get(RecaptchaInterface::class),
                    $this->getContainer()->get(FieldManagerInterface::class)
                );
            }
        );

        $this->getContainer()->add(
            RecaptchaFormField::class,
            function () {
                return new RecaptchaFormField($this->getContainer()->get(RecaptchaInterface::class));
            }
        );
    }
}
