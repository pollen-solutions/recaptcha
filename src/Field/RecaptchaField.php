<?php

declare(strict_types=1);

namespace Pollen\Recaptcha\Field;

use Exception;
use Pollen\Field\FieldDriver;
use Pollen\Field\FieldManagerInterface;
use Pollen\Recaptcha\RecaptchaInterface;

class RecaptchaField extends FieldDriver
{
    /**
     * Recaptcha manager instance.
     * @var RecaptchaInterface
     */
    protected RecaptchaInterface $recaptchaManager;

    /**
     * @param RecaptchaInterface $recaptchaManager
     * @param FieldManagerInterface $fieldManager
     */
    public function __construct(RecaptchaInterface $recaptchaManager, FieldManagerInterface $fieldManager)
    {
        $this->recaptchaManager = $recaptchaManager;

        parent::__construct($fieldManager);
    }

    /**
     * @inheritDoc
     */
    public function boot(): void
    {
        if (!$this->isBooted()) {
            $this->recaptchaManager->checkConfig();

            parent::boot();
        }
    }

    /**
     * Recaptcha manager instance.
     *
     * @return RecaptchaInterface
     */
    public function recaptchaManager(): RecaptchaInterface
    {
        return $this->recaptchaManager;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function render(): string
    {
        if (!$this->get('attrs.id')) {
            $this->set('attrs.id', 'FieldRecaptcha--' . $this->getIndex());
        }

        if ($tabindex = $this->pull('attrs.tabindex')) {
            $this->set('attrs.data-tabindex', $tabindex);
        }

        $this->set('version', $this->recaptchaManager()->getVersion());

        $this->recaptchaManager()->addWidgetRender($this->get('attrs.id'));

        $this->recaptchaManager()->assetsAutoloader();

        return parent::render();
    }

    /**
     * @inheritDoc
     */
    public function viewDirectory(): string
    {
        return $this->recaptchaManager()->resources('/views/field/recaptcha');
    }
}