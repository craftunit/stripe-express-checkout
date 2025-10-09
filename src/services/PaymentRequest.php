<?php

namespace craftunit\craftstripeexpresscheckout\services;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
use craft\commerce\Plugin as Commerce;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Payment Request service
 */
class PaymentRequest extends Component
{
    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function fromOrder(Order $order): array
    {
        $shippingMethods = Commerce::getInstance()?->getShippingMethods()->getAllShippingMethods();
        $shippingRates = [];
        foreach ($shippingMethods as $shippingMethod) {
            if (!$shippingMethod->enabled) {
                continue;
            }

            if (!$shippingMethod->matchOrder($order)) {
                continue;
            }

            $shippingRates[] = [
                'id' => $shippingMethod->handle,
                'displayName' => $shippingMethod->name,
                'amount' => round($shippingMethod->getPriceForOrder($order) * 100),
            ];
        }

        $order->recalculate();

        $lineItems = [];
        $totalAmount = 0;
        $filteredAdjustments = array_filter($order->adjustments, static fn($adj) => $adj->included === false);
        $items = [
            ...$order->lineItems,
            ...$filteredAdjustments,
        ];

        foreach ($items as $item) {
            if ($item instanceof LineItem) {
                $price = $item->salePrice * $item->qty;
                $name = $item->purchasable->title;
            } else {
                $price = $item->amount;
                $name = $item->name;
            }
            $amount = round($price * 100);
            $lineItems[] = compact('name', 'amount');
            $totalAmount += $amount;
        }

        // TODO: Maybe add shipping options? If for example the country changes on the order, we maybe need new shipping options cause of the new language
        return [
            'orderNumber' => $order->number,
            'lineItems' => $lineItems,
            'total' => [
                'label' => Craft::t('commerce', 'Total'),
                'amount' => $totalAmount,
            ],
            'shippingRates' => $shippingRates,
        ];
    }
}
