<?php

namespace craftunit\craftstripeexpresscheckout\eventhandlers;

use Craft;
use craft\base\Event;
use craft\commerce\elements\Order;
use craft\commerce\errors\OrderStatusException;
use craft\commerce\Plugin as Commerce;
use craft\commerce\stripe\base\Gateway;
use craft\commerce\stripe\gateways\PaymentIntents;
use craft\elements\Address;
use craft\errors\ElementNotFoundException;
use craft\fields\PlainText;
use craftunit\craftstripeexpresscheckout\enums\AddressType;
use craftunit\craftstripeexpresscheckout\events\ModifyOrderDetailsEvent;
use craftunit\craftstripeexpresscheckout\events\ReceiveStripeWebhookEvent;
use craftunit\craftstripeexpresscheckout\events\UpdateAddressEvent;
use craftunit\craftstripeexpresscheckout\events\UpdateOrderCustomerEvent;
use craftunit\craftstripeexpresscheckout\events\UpdateOrderEvent;
use craftunit\craftstripeexpresscheckout\events\WebhookFailedEvent;
use craftunit\craftstripeexpresscheckout\helpers\StripeLogger;
use craftunit\craftstripeexpresscheckout\interfaces\EventHandlerInterface;
use craftunit\craftstripeexpresscheckout\Plugin as StripeExpressCheckout;
use JsonException;
use Throwable;
use yii\base\Exception;

class ProcessStripeWebhook implements EventHandlerInterface
{
    public const EVENT_MODIFY_ORDER_DETAILS = 'modifyOrderDetails';
    public const EVENT_BEFORE_ORDER_COMPLETE = 'beforeOrderComplete';
    public const EVENT_AFTER_ORDER_COMPLETE = 'afterOrderComplete';
    public const EVENT_BEFORE_SAVE_SHIPPING_ADDRESS = 'beforeSaveShippingAddress';
    public const EVENT_AFTER_SAVE_SHIPPING_ADDRESS = 'afterSaveShippingAddress';
    public const EVENT_BEFORE_SAVE_BILLING_ADDRESS = 'beforeSaveBillingAddress';
    public const EVENT_AFTER_SAVE_BILLING_ADDRESS = 'afterSaveBillingAddress';
    public const EVENT_BEFORE_SET_ORDER_CUSTOMER = 'beforeSetOrderCustomer';
    public const EVENT_WEBHOOK_FAILED = 'webhookFailed';
    public const EVENT_RECEIVED_WEBHOOK = 'receiveWebhook';

    public function triggers(): array
    {
        return [
            [PaymentIntents::class => Gateway::EVENT_RECEIVE_WEBHOOK],
        ];
    }

    /**
     * @param $event
     * @throws Throwable
     */
    public function handle($event): void
    {
        StripeLogger::create('stripe', 'stripe');

        Event::trigger(
            self::class,
            self::EVENT_RECEIVED_WEBHOOK,
            new ReceiveStripeWebhookEvent($event->webhookData)
        );

        // TODO: handle unsupported webhook types; use event hooks for supporting all types
        if ($event->webhookData['type'] !== 'charge.succeeded') {
            // Craft::error('Webhook type not supported: ' . $event->webhookData['type'], 'stripe');
            // RUN EVENT HOOKS
            return;
        }

        // TODO: Maybe better than name for orderDetails (e.g. stripeResponse? Or class with name OrderDetails?)
        $orderDetails = $event->webhookData['data']['object'];

        if (Event::hasHandlers(self::class, self::EVENT_MODIFY_ORDER_DETAILS)) {
            Event::trigger(self::class, self::EVENT_MODIFY_ORDER_DETAILS, new ModifyOrderDetailsEvent($orderDetails));
        }

        $metadata = $orderDetails['metadata'];

        try {
            $orderNumber = $metadata['order_number'];
            if (!$orderNumber) {
                Craft::error('Order number not found in metadata: ' . json_encode($metadata, JSON_THROW_ON_ERROR), 'stripe');
                return;
            }

            $order = Order::find()->number($orderNumber)->one();

            if (!$this->updateOrderCustomer($order, $orderDetails)) {
                $message = 'Error saving order customer: ' . json_encode($order->getErrors(), JSON_THROW_ON_ERROR);
                Event::trigger(self::class, self::EVENT_WEBHOOK_FAILED, new WebhookFailedEvent($order, $message));
                Craft::error($message, 'stripe');
                return;
            }

            if (!$this->updateOrderAddresses($order, $orderDetails)) {
                $message = 'Error saving order addresses: ' . json_encode($order->getErrors(), JSON_THROW_ON_ERROR);
                Event::trigger(self::class, self::EVENT_WEBHOOK_FAILED, new WebhookFailedEvent($order, $message));
                Craft::error($message, 'stripe');
                return;
            }

            if (!$this->updateOrderDetails($order, $orderDetails)) {
                $message = 'Error saving order details: ' . json_encode($order->getErrors(), JSON_THROW_ON_ERROR);
                Event::trigger(self::class, self::EVENT_WEBHOOK_FAILED, new WebhookFailedEvent($order, $message));
                Craft::error($message, 'stripe');
                return;
            }

            if (Event::hasHandlers(self::class, self::EVENT_BEFORE_ORDER_COMPLETE)) {
                Event::trigger(self::class, self::EVENT_BEFORE_ORDER_COMPLETE, new UpdateOrderEvent($order));
            }

            if (!$this->completeOrder($order, $metadata)) {
                $message = 'Error completing order: ' . json_encode($order->getErrors(), JSON_THROW_ON_ERROR);
                Event::trigger(self::class, self::EVENT_WEBHOOK_FAILED, new WebhookFailedEvent($order, $message));
                Craft::error($message, 'stripe');
                return;
            }

            if (Event::hasHandlers(self::class, self::EVENT_AFTER_ORDER_COMPLETE)) {
                Event::trigger(self::class, self::EVENT_AFTER_ORDER_COMPLETE, new UpdateOrderEvent($order));
            }
        } catch (Exception $e) {
            $message = 'Error processing Stripe webhook: ' . $e->getMessage();
            Event::trigger(self::class, self::EVENT_WEBHOOK_FAILED, new WebhookFailedEvent($order ?? null, $message, $e));
            Craft::error("Error processing Stripe webhook: {$e->getMessage()}", 'stripe');
        }
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    private function updateOrderCustomer(Order $order, array $orderDetails): bool
    {
        $email = $orderDetails['billing_details']['email'];
        $user = Craft::$app->getUsers()->ensureUserByEmail($email);

        if (Event::hasHandlers(self::class, self::EVENT_BEFORE_SET_ORDER_CUSTOMER)) {
            Event::trigger(
                self::class,
                self::EVENT_BEFORE_SET_ORDER_CUSTOMER,
                new UpdateOrderCustomerEvent($user)
            );
        }

        $order->setCustomer($user);
        // TODO: Don't validate here; address is probably not complete (e.g. line 1 missing; only gets added on confirm?)
        return Craft::$app->elements->saveElement($order, false);
    }

    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    private function updateOrderDetails(Order $order, array $orderDetails): bool
    {
        $settings = StripeExpressCheckout::getInstance()->settings;
        if ($settings->phoneField && !empty($orderDetails['billing_details']['phone'])) {
            $fieldUid = $settings->phoneField;
            /* @var $field PlainText */
            $field = Craft::$app->fields->getFieldByUid($fieldUid);
            $order->setFieldValue($field->handle, $orderDetails['billing_details']['phone']);
        }

        return Craft::$app->elements->saveElement($order);
    }

    /**
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws JsonException
     * @throws Throwable
     */
    private function updateOrderAddresses(Order $order, array $orderDetails): bool
    {
        $settings = StripeExpressCheckout::getInstance()->settings;

        $billingDetails = $orderDetails['billing_details'];
        $billingDetails['title'] = 'Rechnungsadresse';

        $shippingDetails = $orderDetails['shipping'];
        $shippingDetails['title'] = 'Lieferadresse';

        if (empty($billingDetails['address']['line1']) && !empty($shippingDetails['address']['line1'])) {
            $billingDetails['address']['line1'] = $shippingDetails['address']['line1'];
        }

        if (empty($billingDetails['address']['line2']) && !empty($shippingDetails['address']['line2'])) {
            $billingDetails['address']['line2'] = $shippingDetails['address']['line2'];
        }

        if (empty($billingDetails['address']['state']) && !empty($shippingDetails['address']['state'])) {
            $billingDetails['address']['state'] = $shippingDetails['address']['state'];
        }

        if (empty($billingDetails['address']['postal_code']) && !empty($shippingDetails['address']['postal_code'])) {
            $billingDetails['address']['postal_code'] = $shippingDetails['address']['postal_code'];
        }

        if (empty($billingDetails['address']['city']) && !empty($shippingDetails['address']['city'])) {
            $billingDetails['address']['city'] = $shippingDetails['address']['city'];
        }

        if (empty($billingDetails['address']['country']) && !empty($shippingDetails['address']['country'])) {
            $billingDetails['address']['country'] = $shippingDetails['address']['country'];
        }

        $billingAddress = $this->updateAddress($order, $billingDetails, AddressType::Billing);
        if ($billingAddress === null) {
            return false;
        }
        $order->setBillingAddress($billingAddress);

        if ($settings->shippingAddressRequired && !empty($shippingDetails['address'])) {
            $shippingAddress = $this->updateAddress($order, $shippingDetails, AddressType::Shipping);
            $order->setShippingAddress($shippingAddress);
        } elseif ($settings->shippingAddressRequired){
            $order->setShippingAddress($billingAddress);
        }

        return Craft::$app->elements->saveElement($order);
    }

    /**
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    private function updateAddress(Order $order, array $addressData, AddressType $addressType): ?Address
    {
        $address = match ($addressType) {
            AddressType::Billing => $order->getBillingAddress(),
            AddressType::Shipping => $order->getShippingAddress(),
        };

        if ($address === null) {
            $address = new Address();
        }

        $address->title = $addressData['title'] ?? $address->title;
        $address->setAttributes([
            'fullName' => $addressData['name'] ?? $address->fullName,
            'firstName' => $addressData['name'] ?? $address->firstName,
            'lastName' => $addressData['name'] ?? $address->lastName,
            'addressLine1' => $addressData['address']['line1'] ?? $address->addressLine1,
            'addressLine2' => $addressData['address']['line2'] ?? null, // Set only if present
            'administrativeArea' => $addressData['address']['state'] ?? null, // Set only if present
            'postalCode' => $addressData['address']['postal_code'] ?? $address->postalCode,
            'locality' => $addressData['address']['city'] ?? $address->locality,
            'countryCode' => $addressData['address']['country'] ?? $address->countryCode,
        ]);

        $address->setOwner($order);

        $eventType = match ($addressType) {
            AddressType::Billing => self::EVENT_BEFORE_SAVE_BILLING_ADDRESS,
            AddressType::Shipping => self::EVENT_BEFORE_SAVE_SHIPPING_ADDRESS,
        };

        if (Event::hasHandlers(self::class, $eventType)) {
            Event::trigger(self::class, $eventType, new UpdateAddressEvent($address));
        }

        if (!Craft::$app->elements->saveElement($address)) {
            return null;
        }

        $eventType = match ($addressType) {
            AddressType::Billing => self::EVENT_AFTER_SAVE_BILLING_ADDRESS,
            AddressType::Shipping => self::EVENT_AFTER_SAVE_SHIPPING_ADDRESS,
        };

        if (Event::hasHandlers(self::class, $eventType)) {
            Event::trigger(self::class, $eventType, new UpdateAddressEvent($address));
        }

        return $address;
    }

    /**
     * @throws OrderStatusException
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    private function completeOrder(Order $order, array $metadata): bool
    {
        $commerce = Commerce::getInstance();
        if ($commerce === null) {
            throw new Exception('Commerce plugin not found');
        }

        $transactions = $commerce->transactions;
        $payments = $commerce->payments;

        $transaction = $transactions->getTransactionByHash($metadata['transaction_reference']);
        if (!$transaction) {
            // TODO: Add error handling when transaction is not found
            return false;
        }
        $transaction->hash = md5(uniqid((string)mt_rand(), true));

        $customerError = null;

        try {
            $payments->completePayment($transaction, $customerError);
        } catch (\Exception $e) {
            Craft::error("Error completing payment: $customerError" . $e->getMessage(), 'stripe');
        }

        return $order->markAsComplete();
    }
}
