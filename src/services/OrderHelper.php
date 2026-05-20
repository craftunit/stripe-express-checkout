<?php

namespace craftunit\craftstripeexpresscheckout\services;

use Craft;
use craft\commerce\elements\Order as OrderElement;
use craft\commerce\models\LineItem;
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
    public function fromItems(array $items, ?int $siteId = null): OrderElement
    {
        // throw error if no items are provided
        if (empty($items)) {
            throw new RuntimeException('No items provided');
        }

        $site = $siteId
            ? Craft::$app->getSites()->getSiteById($siteId)
            : Craft::$app->getSites()->getCurrentSite();

        // Create a new order from the request
        $order = Craft::createObject(
            OrderElement::class,
            [
                // TODO: Order number does not get set for some reason; maybe not save?
                'number' => Commerce::getInstance()?->getCarts()->generateCartNumber(),
                'lastIp' => Craft::$app->getRequest()->userIP,
                'orderLanguage' => $site->language,
                'orderSiteId' => $site->id,
                'currency' => Commerce::getInstance()?->getCarts()->getCart()->currency,
            ],
        );
        $order->number = Commerce::getInstance()?->getCarts()->generateCartNumber();

        // createObject($class, $params) takes positional args, not Yii config — site fields above don't stick, so set them here.
        $order->orderSiteId = $site->id;
        $order->orderLanguage = $site->language;

        // Save order
        if (!Craft::$app->getElements()->saveElement($order)) {
            throw new RuntimeException('Unable to save order');
        }

        // Add items to order
        foreach ($items as $item) {
            $qty = $item['qty'];
            $purchasable = Commerce::getInstance()?->getPurchasables()->getPurchasableById($item['id']);
            if (!$purchasable) {
                throw new ElementNotFoundException('Purchasable not found');
            }

            /** @var LineItem $lineItem */
            $lineItem = Commerce::getInstance()?->getLineItems()->create($order, [
                'purchasableId' => $purchasable->id
            ]);
            $lineItem->qty = $qty;
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
        $siteId = $request->getBodyParam('siteId');
        $siteId = $siteId !== null ? (int)$siteId : null;

        if ($orderNumber) {
            $order = Commerce::getInstance()?->getOrders()->getOrderByNumber($orderNumber);
        } elseif (!empty($items)) {
            $order = $this->fromItems($items, $siteId);
        } else {
            $order = Commerce::getInstance()?->getCarts()->getCart();
        }


        return $order;
    }
}
