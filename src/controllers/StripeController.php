<?php

namespace craftunit\craftstripeexpresscheckout\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\models\Transaction;
use craft\commerce\Plugin as Commerce;
use craft\commerce\services\Payments;
use craft\commerce\stripe\errors\CustomerException;
use craft\commerce\stripe\models\forms\payment\PaymentIntent as PaymentIntentForm;
use craft\elements\Address;
use craft\errors\ElementNotFoundException;
use craft\errors\SiteNotFoundException;
use craft\helpers\Json;
use craft\web\Controller;
use craftunit\craftstripeexpresscheckout\events\UpdateOrderEvent;
use craftunit\craftstripeexpresscheckout\models\Settings;
use craftunit\craftstripeexpresscheckout\Plugin as StripeExpressCheckout;
use craftunit\craftstripeexpresscheckout\services\OrderHelper;
use craftunit\craftstripeexpresscheckout\services\PaymentRequest;
use Stripe\Exception\ApiErrorException;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Stripe controller
 */
class StripeController extends Controller
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    public const string EVENT_BEFORE_UPDATE_SHIPPING_RATE = 'beforeUpdateShippingRate';
    public const string EVENT_AFTER_UPDATE_SHIPPING_RATE = 'afterUpdateShippingRate';
    public const string EVENT_UPDATE_SHIPPING_ADDRESS_ORDER_BEFORE_SAVE = 'beforeUpdateShippingAddress';
    public const string EVENT_UPDATE_SHIPPING_ADDRESS_ORDER_AFTER_SAVE = 'afterUpdateShippingAddress';

    public function __construct($id,
                                $module,
                                private readonly Payments $payments,
                                private readonly OrderHelper $orderHelper,
                                private readonly PaymentRequest $paymentRequest,
                                $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }


    /**
     * stripe-express-checkout/stripe action
     *
     * @throws InvalidConfigException
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     *
     * @noinspection PhpUnused
     */
    public function actionCreateIntent(): Response
    {
        $this->requirePostRequest();

        $order = $this->orderHelper->fromRequest(Craft::$app->getRequest());

        /* @var Settings $settings */
        $settings = StripeExpressCheckout::getInstance()->getSettings();
        $order->setGatewayId($settings->gatewayId);
        $order->cancelUrl = $options['cancelUrl'] ?? null;

        $redirect = null;
        /** @var ?Transaction $transaction */
        $transaction = null;
        $redirectData = null;
        try {
            /* @var PaymentIntentForm $paymentForm */
            $paymentForm = $settings->gateway->getPaymentFormModel();

            $this->payments->processPayment(
                $order,
                $paymentForm,
                $redirect,
                $transaction,
                $redirectData
            );
            $clientSecret = $transaction->response['client_secret'];
            $order->recalculate();
            Craft::$app->getElements()->saveElement($order);

            return $this->asJson([
                'client_secret' => $clientSecret,
                'number' => $order->number,
            ])->setStatusCode(201);
        } catch (ApiErrorException|InvalidConfigException|CustomerException $e) {
            return $this->asJson(['error' => $e->getMessage()])->setStatusCode(400);
        }
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws SiteNotFoundException
     * @throws Throwable
     *
     * @noinspection PhpUnused
     */
    public function actionUpdateShippingRate(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $shippingRateHandle = $request->getRequiredBodyParam('shippingRateHandle');

        $order = $this->orderHelper->fromRequest($request);

        $shippingMethod = Commerce::getInstance()
            ?->getShippingMethods()
            ->getShippingMethodByHandle($shippingRateHandle);

        if ($shippingMethod !== null) {
            $order->shippingMethodHandle = $shippingMethod->handle;
            $order->shippingMethodName = $shippingMethod->name;

            Craft::$app->getElements()->saveElement($order, false);

            $order->recalculate();
        }

        $paymentRequest = $this->paymentRequest->fromOrder($order);
        return $this->asJson($paymentRequest);
    }

    /**
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     * @throws Exception|Throwable
     * @noinspection PhpUnused
     */
    public function actionUpdateShippingAddress(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $address = Json::decodeIfJson($request->getRequiredBodyParam('address'));

        $order = $this->orderHelper->fromRequest($request);

        $currentShippingAddress = $order->getShippingAddress();
        if ($currentShippingAddress === null) {
            $currentShippingAddress = new Address();
            $currentShippingAddress->ownerId = $order->id;
        }

        // We only receive city, country and postal_code from the stripe
        $currentShippingAddress->locality = $address['city'];
        $currentShippingAddress->countryCode = $address['country'];
        $currentShippingAddress->postalCode = $address['postal_code'];

        $order->setShippingAddress($currentShippingAddress);

        // After changing the shipping address in link, the shipping method is set in the frontend to the first available one
        // autoSetShippingMethod looks for the first available shipping method and sets it on the order
        // Set shipping method to null to trigger autoSetShippingMethod
        $order->shippingMethodHandle = null;
        $order->autoSetShippingMethod();

        if ($this->hasEventHandlers(self::EVENT_UPDATE_SHIPPING_ADDRESS_ORDER_BEFORE_SAVE)) {
            $this->trigger(self::EVENT_UPDATE_SHIPPING_ADDRESS_ORDER_BEFORE_SAVE, new UpdateOrderEvent($order));
        }

        // Do not run validation; we only have a partial address
        if (!Craft::$app->elements->saveElement($order, false)) {
            return $this->asJson(['error' => 'Could not save order', 'errors' => $order->getErrors()])->setStatusCode(400);
        }

        $order->recalculate();

        if ($this->hasEventHandlers(self::EVENT_UPDATE_SHIPPING_ADDRESS_ORDER_AFTER_SAVE)) {
            $this->trigger(self::EVENT_UPDATE_SHIPPING_ADDRESS_ORDER_AFTER_SAVE, new UpdateOrderEvent($order));
        }

        $paymentRequest = $this->paymentRequest->fromOrder($order);
        return $this->asJson($paymentRequest);
    }

    /**
     * @throws BadRequestHttpException
     * @throws Throwable
     * @noinspection PhpUnused
     */
    public function actionCancel(): Response
    {
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $orderNumber = $request->getRequiredBodyParam('orderNumber');

        // delete order
        $order = Order::find()->number($orderNumber)->one();
        if (!Craft::$app->elements->deleteElement($order)) {
            return $this->asJson([
                'status' => 'error',
                'message' => 'Could not delete order',
            ]);
        }

        return $this->asJson([
            'status' => 'success',
            'message' => 'Order deleted',
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     * @noinspection PhpUnused
     */
    public function actionResetAddresses(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $orderNumber = $request->getRequiredBodyParam('orderNumber');

        $order = Order::find()->number($orderNumber)->one();

        // TODO: Maybe reset the whole order?
        $order->setShippingAddress(null);
        $order->setBillingAddress(null);

        if (!Craft::$app->elements->saveElement($order, false)) {
            return $this->asJson([
                'status' => 'error',
                'message' => 'Could not reset addresses',
            ]);
        }

        return $this->asJson([
            'status' => 'success',
            'message' => 'Addresses reset',
        ]);
    }
}
