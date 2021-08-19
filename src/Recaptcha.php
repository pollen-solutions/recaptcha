<?php

declare(strict_types=1);

namespace Pollen\Recaptcha;

use Pollen\Asset\AssetManagerInterface;
use Pollen\Asset\Assets\InlineAsset;
use Pollen\Asset\Queue;
use Pollen\Event\TriggeredEvent;
use Pollen\Recaptcha\Exception\RecaptchaSecretKeyException;
use Pollen\Recaptcha\Exception\RecaptchaSiteKeyException;
use Pollen\Recaptcha\Field\RecaptchaField;
use Pollen\Recaptcha\Form\RecaptchaFormField;
use Pollen\Support\Concerns\BootableTrait;
use Pollen\Support\Concerns\ConfigBagAwareTrait;
use Pollen\Support\Concerns\ResourcesAwareTrait;
use Pollen\Support\Exception\ManagerRuntimeException;
use Pollen\Support\Proxy\ContainerProxy;
use Pollen\Support\Proxy\EventProxy;
use Pollen\Support\Proxy\FieldProxy;
use Pollen\Support\Proxy\FormProxy;
use Pollen\Support\Proxy\HttpRequestProxy;
use Psr\Container\ContainerInterface as Container;
use ReCaptcha\ReCaptcha as ReCaptchaDriver;
use ReCaptcha\Response as ReCaptchaResponse;
use ReCaptcha\RequestMethod\SocketPost as ReCaptchaSocket;

class Recaptcha implements RecaptchaInterface
{
    use BootableTrait;
    use ConfigBagAwareTrait;
    use ResourcesAwareTrait;
    use ContainerProxy;
    use EventProxy;
    use FieldProxy;
    use FormProxy;
    use HttpRequestProxy;

    /**
     * Recaptcha main instance.
     * @var static|null
     */
    private static ?RecaptchaInterface $instance = null;

    /**
     * Recaptcha driver instance.
     * @var ReCaptchaDriver|null
     */
    private ?ReCaptchaDriver $reCaptchaDriver = null;

    /**
     * Assets autoload indicator.
     * @var bool
     */
    protected bool $assetsAutoloaded = false;

    /**
     * List of registered widget HTML IDS.
     * @type string[];
     */
    protected array $widgetIds = [];

    /**
     * @param array $config
     * @param Container|null $container
     *
     * @return void
     */
    public function __construct(array $config = [], ?Container $container = null)
    {
        $this->setConfig($config);

        if ($container !== null) {
            $this->setContainer($container);
        }

        $this->setResourcesBaseDir(dirname(__DIR__) . '/resources');

        $this->boot();

        if (!self::$instance instanceof static) {
            self::$instance = $this;
        }
    }

    /**
     * Get Recaptcha main instance.
     *
     * @return static
     */
    public static function getInstance(): RecaptchaInterface
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }
        throw new ManagerRuntimeException(sprintf('Unavailable [%s] instance', __CLASS__));
    }

    /**
     * @inheritDoc
     */
    public function addWidgetRender(string $id): RecaptchaInterface
    {
        $this->widgetIds[] = $id;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function assetsAutoloader(): void
    {
        if (!$this->assetsAutoloaded &&
            ($this->config('asset.autoloader', true) === true) &&
            ($jsScripts = $this->getJsScripts())
        ) {
            if (defined('WPINC') && function_exists('add_action')) {
                add_action(
                    'wp_print_footer_scripts',
                    function () use ($jsScripts) {
                        echo "<!-- Recaptcha Scripts -->" .
                            "<script type=\"text/javascript\">/* <![CDATA[ */$jsScripts/* ]]> */</script>" .
                            "<!-- / Recaptcha Scripts -->";
                    }
                );
            }

            $this->event()->one(
                'asset.handle-head.before',
                function (TriggeredEvent $event, AssetManagerInterface $assetManager) use ($jsScripts) {
                    $assetManager->enqueueJs(
                        new InlineAsset('recaptcha-js', $jsScripts),
                        true,
                        [],
                        Queue::LOW
                    )
                        ->setBefore('<!-- Recaptcha Scripts -->')
                        ->setAfter('<!-- / Recaptcha Scripts -->');
                }
            );

            $this->assetsAutoloaded = true;
        }
    }

    /**
     * @inheritDoc
     */
    public function boot(): RecaptchaInterface
    {
        if (!$this->isBooted()) {
            $this->event()->trigger('recaptcha.booting', [&$this]);

            $this->field()->register(
                'recaptcha',
                $this->containerHas(RecaptchaField::class)
                    ? RecaptchaField::class : new RecaptchaField($this, $this->field())
            );

            $this->form()->registerFormFieldDriver(
                'recaptcha',
                $this->containerHas(RecaptchaFormField::class)
                    ? RecaptchaFormField::class : new RecaptchaFormField($this)
            );

            $this->setBooted();

            $this->event()->trigger('recaptcha.booted', [&$this]);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function checkConfig(): bool
    {
        if (!$this->config('sitekey')) {
            throw new RecaptchaSiteKeyException();
        }

        if (!$this->config('secretkey')) {
            throw new RecaptchaSecretKeyException();
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function defaultConfig(): array
    {
        return [
            /**
             * Recaptcha version or type (required).
             * @var int 2|3
             */
            'version'   => 3,
            /**
             * Recaptcha site key (required).
             * @var string|null $sitekey
             */
            'sitekey'   => null,
            /**
             * Recaptcha secret key (required).
             * @var string|null $secretkey
             */
            'secretkey' => null,
            /**
             * Locale in ISO 15897 format. en_US if null.
             * @var string|null $locale
             */
            'locale'    => null,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getHandleResponse(?string $value = null, ?string $ip = null): ReCaptchaResponse
    {
        $response = $value ?? $this->httpRequest()->get('g-recaptcha-response');

        if ($ip === null) {
            $remoteIP = $this->httpRequest()->getClientIp();
        } elseif (empty($ip)) {
            $remoteIP = null;
        } else {
            $remoteIP = $ip;
        }

        return $this->reCaptchaDriver()->verify($response, $remoteIP);
    }

    /**
     * @inheritDoc
     */
    public function getJsScripts(): string
    {
        if ($ids = $this->widgetIds) {
            $this->checkConfig();

            $sitekey = $this->getSiteKey();
            $lang = $this->getLanguage();
            $theme = $this->getTheme();
            $js = "";

            switch ($this->getVersion()) {
                case 3:
                    $js .= "function reCaptchaCallback() {";
                    $js .= "grecaptcha.ready(function () {";
                    $js .= "grecaptcha.execute('$sitekey').then(function (token) {";
                    foreach ($ids as $id) {
                        $js .= "var recaptchaResponse = document.getElementById('$id');";
                        $js .= "recaptchaResponse.value = token;";
                    }
                    $js .= "});";
                    $js .= "});";
                    $js .= "};";
                    break;
                case 2:
                    $js .= "function reCaptchaCallback() {";
                    foreach ($ids as $id) {
                        $js .= "var reCaptchaEl=document.getElementById('$id');";
                        $js .= "if(reCaptchaEl){";
                        $js .= "try{grecaptcha.render('$id',{sitekey:'$sitekey',theme:'$theme'});} catch(error){console.log(error);}";
                        $js .= "}";
                    }
                    $js .= "};";
                    break;
            }

            if (!empty($js)) {
                $js .= "var recaptchaScriptInitialized = false;";
                $js .= "var recaptchaObserver;";
                $js .= "window.addEventListener('load', function(event) {";
                $js .= "recaptchaObserver = new IntersectionObserver(recaptchaWidgetHandleIntersect, {";
                $js .= "root: null,";
                $js .= "rootMargin: \"0px\",";
                $js .= "threshold: 1.0";
                $js .= "});";
                $js .= "function recaptchaWidgetHandleIntersect(entries, observer) {";
                $js .= "entries.forEach(function(entry) {";
                $js .= "if (recaptchaScriptInitialized) {";
                $js .= "recaptchaObserver.unobserve(entry.target);";
                $js .= "return;";
                $js .= "}";
                $js .= "if (entry.isIntersecting) {";
                $js .= "var recaptchaScript = document.createElement('script');";
                switch ($this->getVersion()) {
                    case 3:
                        $js .= "recaptchaScript.src = 'https://www.google.com/recaptcha/api.js?hl=$lang&onload=reCaptchaCallback&render=$sitekey';";
                        break;
                    case 2:
                        $js .= "recaptchaScript.src = 'https://www.google.com/recaptcha/api.js?hl=$lang&onload=reCaptchaCallback&render=explicit';";
                        break;
                }
                $js .= "recaptchaScript.defer = true;";
                $js .= "document.getElementsByTagName('head')[0].appendChild(recaptchaScript);";
                $js .= "recaptchaScriptInitialized = true;";
                if ($this->config('debug', false) === true) {
                    $js .= "console.log('Recaptcha script is initialized');";
                }
                $js .= "}";
                $js .= "});";
                $js .= "};";
                foreach ($ids as $id) {
                    $js .= "recaptchaObserver.observe(document.getElementById('$id-intersectionObserver'));";
                }
                $js .= "}, false);";
            }

            return $js;
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    public function getLanguage(): string
    {
        switch ($locale = (string)$this->config('locale', 'en_US')) {
            default :
                [$lang] = explode("_", $locale, 1);
                break;
            case 'zh_CN':
                $lang = 'zh-CN';
                break;
            case 'zh_TW':
                $lang = 'zh-TW';
                break;
            case 'en_GB' :
                $lang = 'en-GB';
                break;
            case 'fr_CA' :
                $lang = 'fr-CA';
                break;
            case 'de_AT' :
                $lang = 'de-AT';
                break;
            case 'de_CH' :
                $lang = 'de-CH';
                break;
            case 'pt_BR' :
                $lang = 'pt-BR';
                break;
            case 'pt_PT' :
                $lang = 'pt-PT';
                break;
            case 'es_AR' :
            case 'es_CL' :
            case 'es_CO' :
            case 'es_MX' :
            case 'es_PE' :
            case 'es_PR' :
            case 'es_VE' :
                $lang = 'es-419';
                break;
        }
        return $lang;
    }

    /**
     * @inheritDoc
     */
    public function getSiteKey(): ?string
    {
        return $this->config('sitekey');
    }

    /**
     * @inheritDoc
     */
    public function getTheme()
    {
        return 'light';
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): int
    {
        $version = (int)$this->config('version', 3);

        return in_array($version, [2, 3], true) ? $version : 3;
    }

    /**
     * @inheritDoc
     */
    public function isResponseValid(string $value): bool
    {
        return $this->getHandleResponse($value)->isSuccess();
    }

    /**
     * @inheritDoc
     */
    public function reCaptchaDriver(): ReCaptchaDriver
    {
        if ($this->reCaptchaDriver === null) {
            $this->reCaptchaDriver = new ReCaptchaDriver(
                $this->config('secretkey'), (ini_get('allow_url_fopen') ? null : new ReCaptchaSocket())
            );
        }

        return $this->reCaptchaDriver;
    }
}