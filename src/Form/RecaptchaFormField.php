<?php

declare(strict_types=1);

namespace Pollen\Recaptcha\Form;

use Pollen\Form\Exception\FieldValidateException;
use Pollen\Form\FormFieldDriver;
use Pollen\Form\FormFieldDriverInterface;
use Pollen\Recaptcha\RecaptchaInterface;

class RecaptchaFormField extends FormFieldDriver
{
    /**
     * Recaptcha manager instance.
     * @var RecaptchaInterface
     */
    private RecaptchaInterface $recaptchaManager;

    /**
     * List of supported features.
     * @var string[]|null
     */
    protected ?array $supports = ['label', 'request', 'wrapper'];

    /**
     * @param RecaptchaInterface $recaptchaManager
     */
    public function __construct(RecaptchaInterface $recaptchaManager)
    {
        $this->recaptchaManager = $recaptchaManager;
    }

    /**
     * @inheritDoc
     */
    public function boot(): FormFieldDriverInterface
    {
        if (!$this->isBooted()) {
            $this->recaptchaManager->checkConfig();

            parent::boot();
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(): string
    {
        return $this->recaptchaManager->field(
            'recaptcha',
            array_merge(
                $this->getExtras(),
                [
                    'name'  => $this->getName(),
                    'attrs' => array_merge(
                        [
                            'id' => str_replace("-", '_', sanitize_key($this->form()->getAlias())),
                        ],
                        $this->params('attrs', [])
                    ),
                    'label' => false
                ]
            )
        )->render();
    }

    /**
     * @inheritDoc
     */
    public function validate($value = null): void
    {
        if (!$this->recaptchaManager->isResponseValid($value)) {
            throw new FieldValidateException(
                $this, 'Invalid Recaptcha response.', ['recaptcha']
            );
        }
    }
}