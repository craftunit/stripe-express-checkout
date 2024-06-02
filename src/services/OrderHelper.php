<?php

namespace craftunit\craftstripeexpresscheckout\services;

use Craft;
use craft\commerce\elements\Order as OrderElement;
use craft\commerce\Plugin as Commerce;
use craft\errors\ElementNotFoundException;
use craft\helpers\Json;
use Craft\web\Request;
use RuntimeException;
use Throwable;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Order service
 */
class OrderHelper extends Component
{
    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function fromItems(array $items): OrderElement
    {
        // throw error if no items are provided
        if (empty($items)) {
            throw new RuntimeException('No items provided');
        }

        // Create a new order from the request
        $order = Craft::createObject(
            OrderElement::class,
            [
                // TODO: Order number does not get set for some reason; maybe not save?
                'number' => Commerce::getInstance()?->getCarts()->generateCartNumber(),
                'lastIp' => Craft::$app->getRequest()->userIP,
                'orderLanguage' => Craft::$app->getSites()->getCurrentSite()->language,
                'currency' => Commerce::getInstance()?->getCarts()->getCart()->currency,
            ],
        );
        $order->number = Commerce::getInstance()?->getCarts()->generateCartNumber();

        // Save order
        if (!Craft::$app->getElements()->saveElement($order)) {
            throw new RuntimeException('Unable to save order');
        }

        // Add items to order
        foreach ($items as $item) {
            $purchasable = Commerce::getInstance()?->getPurchasables()->getPurchasableById($item['id']);
            if (!$purchasable) {
                throw new ElementNotFoundException('Purchasable not found');
            }
            $lineItem = Commerce::getInstance()?->getLineItems()->createLineItem($order, $purchasable->id, []);
            $order->addLineItem($lineItem);
        }

        return $order;
    }

    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function fromRequest(Request $request): OrderElement
    {
        $items = Json::decodeIfJson($request->getBodyParam('items'));
        $orderNumber = $request->getBodyParam('orderNumber');

        if ($orderNumber) {
            $order = Commerce::getInstance()?->getOrders()->getOrderByNumber($orderNumber);
        } elseif (!empty($items)) {
            $order = $this->fromItems($items);
        } else {
            $order = Commerce::getInstance()?->getCarts()->getCart();
        }

        return $order;
    }
}
