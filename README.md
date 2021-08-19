# Recaptcha Component

[![Latest Stable Version](https://img.shields.io/packagist/v/pollen-solutions/recaptcha.svg?style=for-the-badge)](https://packagist.org/packages/pollen-solutions/recaptcha)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-green?style=for-the-badge)](LICENSE.md)
[![PHP Supported Versions](https://img.shields.io/badge/PHP->=7.4-8892BF?style=for-the-badge&logo=php)](https://www.php.net/supported-versions.php)

Pollen Solutions **Recaptcha** Component provides a recaptcha V2/V3 integration layer for PHP forms.

## Installation

```bash
composer require pollen-solutions/recaptcha
```

## Basic Usage with Pollen form component

1. Install Pollen form component with composer.

```bash
composer require pollen-solutions/form
```

2. Create a form, display it and send a submission.

```php
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Pollen\Form\FormManager;
use Pollen\Recaptcha\Recaptcha;
use Pollen\Recaptcha\Form\RecaptchaFormField;

$recaptcha = new Recaptcha();
$recaptcha->config(
    [
        /**
         * Recaptcha version or type (required).
         * @var int 2|3
         */
        'version'   => 3,
        /**
         * Recaptcha Site key (required).
         * @var string|null $sitekey
         */
        'sitekey'   => '===== sitekey =====',
        /**
         * Recaptcha secret key (required).
         * @var string|null $secretkey
         */
        'secretkey' => '===== secretkey =====',
        /**
         * Locale in ISO 15897 format. en_US if null.
         * @var string|null $locale
         */
        'locale'    => null,
    ]
);

$forms = new FormManager();

try {
    $forms->registerFormFieldDriver('recaptcha', new RecaptchaFormField($recaptcha));
} catch(Throwable $e) {
    // If the recaptcha form field is already registered.
    unset($e);
}

$form = $forms->buildForm(
    [
        'fields' => [
            'email'   => [
                'type' => 'text',
            ],
            'captcha' => [
                'type' => 'recaptcha',
            ],
        ],
    ]
)->get();

if ($response = $form->handle()->proceed()) {
    (new SapiEmitter())->emit($response->psr());
    exit;
}

echo <<< HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Test Recaptcha</title>
    </head>
    <body>
    $form
HTML;

// A Recaptcha widget was then automatically added by the form render.
$jsScripts = $recaptcha->getJsScripts();

echo <<< HTML
    <!-- Recaptcha Scripts -->
    <script type="text/javascript">/* <![CDATA[ */$jsScripts/* ]]> */</script>
    <!-- / Recaptcha Scripts -->
    </body>
    </html>
HTML;
```

## Asset autoloader

By default an asset autoloader places the JS in the HTML footer of all pages in the web application.
You might want to call these scripts manually.

## In Wordpress application

```php
use Pollen\Recaptcha\RecaptchaInstance;

/** @var RecaptchaInstance $recaptcha */
$recaptcha = $this->config(['asset.autoloader' => true]);

add_action('wp_print_footer_scripts', function () use ($recaptcha) {
    echo "<!-- Recaptcha Scripts -->" .
    "<script type=\"text/javascript\">/* <![CDATA[ */" . $recaptcha->getJsScripts() . "/* ]]> */</script>" .
    "<!-- / Recaptcha Scripts -->";
});
```
