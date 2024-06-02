<?php

namespace craftunit\craftstripeexpresscheckout\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\errors\MissingComponentException;
use craft\web\Controller;
use craftunit\craftstripeexpresscheckout\enums\ApplePayTheme;
use craftunit\craftstripeexpresscheckout\enums\ApplePayType;
use craftunit\craftstripeexpresscheckout\enums\GooglePayTheme;
use craftunit\craftstripeexpresscheckout\enums\GooglePayType;
use craftunit\craftstripeexpresscheckout\enums\Overflow;
use craftunit\craftstripeexpresscheckout\enums\PaypalTheme;
use craftunit\craftstripeexpresscheckout\enums\PaypalType;
use craftunit\craftstripeexpresscheckout\enums\ShowWallet;
use craftunit\craftstripeexpresscheckout\Plugin as StripeExpressCheckout;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Settings controller
 */
class SettingsController extends Controller
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    /**
     * stripe-express-checkout/settings action
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function actionIndex(): Response|string
    {
        $commerce = Commerce::getInstance();

        if ($commerce === null) {
            // TODO: render actual template
            return 'You must install Craft Commerce first.';
        }

        $enabledGateways = $commerce->getGateways()->getAllCustomerEnabledGateways();
        $gateways = [
            null => 'Please select a gateway',
        ];

        foreach ($enabledGateways as $gateway) {
            if (!str_contains($gateway::class, 'stripe')) {
                continue;
            }

            $gateways[$gateway->id] = $gateway->name;
        }

        $fieldLayout = Craft::$app->getFields()->getLayoutByType(Order::class);
        $orderFields = $fieldLayout->getCustomFields();

        $fields = [null => 'Not selected'];
        foreach ($orderFields as $field) {
            $fields[$field->uid] = $field->name;
        }

        $inventories = [
            null => 'Please select an inventory location',
        ];
        $inventoryOptions = Commerce::getInstance()?->getStores()->getCurrentStore()->getInventoryLocationsOptions();
        foreach ($inventoryOptions as $key => $value) {
            $inventories[$key] = $value;
        }

        return $this->renderTemplate('stripe-express-checkout', [
            'settings' => StripeExpressCheckout::getInstance()->settings,
            'gateways' => $gateways,
            'inventories' => $inventories,
            'fields' => $fields,
            'applePay' => [
                'themes' => ApplePayTheme::asOptions(),
                'types' => ApplePayType::asOptions(),
                'showWallet' => ShowWallet::asOptions(),
            ],
            'googlePay' => [
                'themes' => GooglePayTheme::asOptions(),
                'types' => GooglePayType::asOptions(),
                'showWallet' => ShowWallet::asOptions(),
            ],
            'paypal' => [
                'themes' => PaypalTheme::asOptions(),
                'types' => PaypalType::asOptions(),
            ],
            'overflow' => Overflow::asOptions(),
        ]);
    }

    /**
     * Saves a plugin’s settings.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     */
    public function actionSaveSettings(): ?Response
    {
        // Save the plugin settings
        $this->requirePostRequest();
        $settings = Craft::$app->getRequest()->getBodyParam('settings', []);
        $stripExpressCheckout = StripeExpressCheckout::getInstance();

        if (!Craft::$app->getPlugins()->savePluginSettings($stripExpressCheckout, $settings)) {
            Craft::$app->getSession()->setError('Couldn’t save plugin settings.');
            return null;
        }

        return $this->redirectToPostedUrl();
    }
}
