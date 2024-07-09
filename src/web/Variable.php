<?php

namespace craftunit\craftstripeexpresscheckout\web;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\models\InventoryLocation;
use craft\commerce\models\LineItem;
use craft\commerce\Plugin as Commerce;
use craft\helpers\App;
use craftunit\craftstripeexpresscheckout\Plugin;
use craftunit\craftstripeexpresscheckout\Plugin as StripeExpressCheckout;
use craftunit\craftstripeexpresscheckout\web\assets\expresscheckout\ExpressCheckoutAsset;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class Variable
{
    /**
     * @param array $options
     * @return string
     * @throws Exception
     * @throws InvalidConfigException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Throwable
     */
    public function buttons(array $options = []): string
    {
        if (!empty($options['cart'])) {
            $order = $options['cart'];
            $options['cart'] = true;
        } elseif ($options['items']) {
            $order = new Order();
        } else {
            throw new Exception('No cart or items provided');
        }

        /* @var Order|null $order */
        if (!empty($options['items'])) {
            foreach ($options['items'] as $item) {
                $lineItem = Commerce::getInstance()?->lineItems->createLineItem(
                    $order,
                    $item['id'],
                    [],
                    $item['qty']
                );
                $order->addLineItem($lineItem);
            }
        }


        $settings = StripeExpressCheckout::getInstance()->settings;
        $shippingRates = [];
        if ($settings->shippingAddressRequired) {
            $commerceShippingMethods = Commerce::getInstance()?->getShippingMethods()->getAllShippingMethods();
            foreach ($commerceShippingMethods as $shippingMethod) {
                $shippingRates[] = [
                    'id' => $shippingMethod->handle,
                    'name' => $shippingMethod->name,
                    'displayName' => $shippingMethod->name, // TODO: Add displayName to shipping method (translatable?)
                    'amount' => $shippingMethod->getPriceForOrder($order) * 100,
                ];
            }
        }

        if ($settings->shippingAddressRequired && empty($shippingRates)) {
            throw new Exception('No shipping methods found');
        }

        $view = Craft::$app->getView();

        $id = uniqid('stripe-express-checkout', true);

        $defaultOptions = $settings->toArray();
        $defaultOptions['buttonTheme'] = $settings->getButtonThemes();
        $defaultOptions['buttonType'] = $settings->getButtonTypes();
        $defaultOptions['wallets'] = $settings->getShowWallets();

        // TODO: Multi Store support
        $store = Commerce::getInstance()?->getStores()->getCurrentStore();

        if ($settings->restrictCountries || !empty($options['restrictCountries'])) {
            $allowedCountries = $store->settings->countriesList;
            $allowedCountries = array_keys($allowedCountries);
            $options['allowedCountries'] = array_map('strtolower', $allowedCountries);
        }

        if ($settings->shippingAddressRequired) {
            $defaultOptions['shippingRates'] = $shippingRates;
        }

        $lineItems = [];
        foreach ($order->getLineItems() as $lineItem) {
            /* @var LineItem $lineItem */
            $lineItems[] = [
                'name' => $lineItem->purchasable->title,
                'amount' => (int) ($lineItem->salePrice * 100),
            ];
        }

        $options = array_merge($defaultOptions, $options);

        $amountInCents = (int) ($order->getTotal() * 100);

        // dd($order->getTotal(), $amountInCents) ;
        $options = array_merge([
            'id' => $id,
            'amount' => $amountInCents,
            'lineItems' => $lineItems,
            'businessName' => $options['businessName'] ?? Craft::$app->getSystemName(),
            'country' => $store->settings->getLocationAddress()->countryCode,
            'currency' => strtolower(Commerce::getInstance()?->getCarts()->getCart()->currency),
            'stripeApiKey' => App::parseEnv($settings->gateway['publishableKey']),
            'style' => [],
        ], $options);

        $view->registerJsFile('https://js.stripe.com/v3/');
        $view->registerAssetBundle(ExpressCheckoutAsset::class);
        $view->registerJs("StripeExpressCheckout.init('$id', " . json_encode($options, JSON_THROW_ON_ERROR) . ");");

        return $view->renderTemplate('stripe-express-checkout/buttons', compact('id', 'options'));
    }
}
