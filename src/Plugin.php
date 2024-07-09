<?php

namespace craftunit\craftstripeexpresscheckout;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\web\Response;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use craftunit\craftstripeexpresscheckout\eventhandlers\ProcessStripeWebhook;
use craftunit\craftstripeexpresscheckout\models\Settings;
use craftunit\craftstripeexpresscheckout\services\OrderHelper;
use craftunit\craftstripeexpresscheckout\services\PaymentRequest;
use craftunit\craftstripeexpresscheckout\web\Variable;
use yii\base\Event;
use yii\base\InvalidRouteException;

/**
 * Stripe Express Checkout plugin
 *
 * @method static Plugin getInstance()
 * @author Craft-Unit <technik@craft-unit.de>
 * @copyright Craft-Unit
 * @license MIT
 * @property-read Settings $settings
 * @property-read PaymentRequest $paymentRequest
 * @property-read Response $settingsResponse
 * @property-read OrderHelper $order
 */
class Plugin extends BasePlugin
{
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;
    protected array $templateRoots = [
        'stripe-express-checkout' => __DIR__ . '/templates',
    ];
    private array $cpUrlRules = [
        'stripe-express-checkout' => 'stripe-express-checkout/settings',
    ];

    public function init(): void
    {
        parent::init();

        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
        });
    }

    public static function config(): array
    {
        return [
            'components' => [
                'paymentRequest' => PaymentRequest::class,
                'orderHelper' => OrderHelper::class,
            ],
        ];
    }

    private function attachEventHandlers(): void
    {
        /* REGISTER VARIABLE */
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            static function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('expressCheckout', Variable::class);
            }
        );

        $templateRoots = $this->templateRoots;
        /* REGISTER CP TEMPLATE ROOTS */
        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            static function(RegisterTemplateRootsEvent $e) use ($templateRoots) {
                foreach ($templateRoots as $handle => $dir) {
                    if (is_string($dir)) {
                        $dir = [$dir];
                    }

                    foreach ($dir as $d) {
                        if (is_dir($d .= DIRECTORY_SEPARATOR . 'cp')) {
                            $e->roots[$handle] = $d;
                        }
                    }
                }
            });

        /* REGISTER SITE TEMPLATE ROOTS */
        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            static function(RegisterTemplateRootsEvent $e) use ($templateRoots) {
                foreach ($templateRoots as $handle => $dir) {
                    if (is_string($dir)) {
                        $dir = [$dir];
                    }

                    foreach ($dir as $d) {
                        if (is_dir($d)) {
                            $e->roots[$handle] = $d;
                        }
                    }
                }
            });

        /* REGISTER CP URL RULES */
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules = array_merge($event->rules, $this->cpUrlRules);
            }
        );

        // TODO: Use eventhandlers; can't register template roots for some reason when using eventhandlers
        $eventHandlers = [
            ProcessStripeWebhook::class,
        ];

        foreach ($eventHandlers as $eventHandlerClass) {
            $eventHandler = new $eventHandlerClass();
            foreach ($eventHandler->triggers() as $trigger) {
                foreach ($trigger as $eventClass => $eventName) {
                    Event::on($eventClass, $eventName, [$eventHandler, 'handle']);
                }
            }
        }
    }

    /**
     * @throws InvalidRouteException
     */
    public function getSettingsResponse(): Response
    {
        return Craft::$app->getResponse()->redirect('stripe-express-checkout');
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }
}
