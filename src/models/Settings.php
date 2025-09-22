<?php

namespace craftunit\craftstripeexpresscheckout\models;

use craft\base\Model;
use craft\commerce\base\GatewayInterface;
use craft\commerce\Plugin as Commerce;
use craft\errors\DeprecationException;
use craft\errors\SiteNotFoundException;
use craftunit\craftstripeexpresscheckout\enums\ApplePayTheme;
use craftunit\craftstripeexpresscheckout\enums\ApplePayType;
use craftunit\craftstripeexpresscheckout\enums\GooglePayTheme;
use craftunit\craftstripeexpresscheckout\enums\GooglePayType;
use craftunit\craftstripeexpresscheckout\enums\Overflow;
use craftunit\craftstripeexpresscheckout\enums\PaypalTheme;
use craftunit\craftstripeexpresscheckout\enums\PaypalType;
use craftunit\craftstripeexpresscheckout\enums\ShowWallet;
use yii\base\InvalidConfigException;

/**
 * Settings model
 *
 * @property-read array $buttonTypes
 * @property-read array $showWallets
 * @property-read array $buttonThemes
 * @property-read null|GatewayInterface $gateway
 */
class Settings extends Model
{
    /* GENERAL SETTINGS */
    public ?string $gatewayId = null;
    public bool $shippingAddressRequired = false;
    public bool $phoneNumberRequired = false;
    public bool $restrictCountries = false;
    public ?string $successUrl = null;
    public ?string $loaderTemplate = null;

    /* APPEARANCE */
    public int $buttonHeight = 44;
    public string $applePayTheme = ApplePayTheme::Black->value;
    public string $googlePayTheme = GooglePayTheme::Black->value;
    public string $paypalTheme = PaypalTheme::Blue->value;
    public string $applePayType = ApplePayType::Plain->value;
    public string $googlePayType = GooglePayType::Buy->value;
    public string $paypalType = PaypalType::Paypal->value;
    public int $maxColumns = 0;
    public int $maxRows = 0;
    public string $overflow = Overflow::Auto->value;
    public array $paymentMethodOrder = [];
    public string $showApplePay = ShowWallet::Auto->value;
    public string $showGooglePay = ShowWallet::Auto->value;
    public string $showPayPal = ShowWallet::Auto->value;
    public string $showLink = ShowWallet::Auto->value;
    public string $showAmazonPay = ShowWallet::Auto->value;

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['gatewayId', 'successUrl'], 'required'],
            [['gatewayId', 'successUrl', 'loaderTemplate'], 'string'],
            [['shippingAddressRequired', 'phoneNumberRequired', 'restrictCountries'], 'boolean'],
            [['maxColumns', 'maxRows'], 'integer', 'min' => 0],
            [['buttonHeight'], 'integer', 'min' => 40, 'max' => 55],
            [['overflow'], 'in', 'range' => Overflow::asValues()],
            [['applePayTheme'], 'in', 'range' => ApplePayTheme::asValues()],
            [['googlePayTheme'], 'in', 'range' => GooglePayTheme::asValues()],
            [['paypalTheme'], 'in', 'range' => PaypalTheme::asValues()],
            [['applePayType'], 'in', 'range' => ApplePayType::asValues()],
            [['googlePayType'], 'in', 'range' => GooglePayType::asValues()],
            [['paypalType'], 'in', 'range' => PaypalType::asValues()],
            [['showApplePay'], 'in', 'range' => ShowWallet::asValues()],
            [['showGooglePay'], 'in', 'range' => ShowWallet::asValues()],
            [['restrictCountries'], 'validateCountries', 'when' => fn() => $this->restrictCountries],
        ]);
    }

    /**
     * @throws InvalidConfigException
     * @throws DeprecationException
     */
    public function getGateway(): ?GatewayInterface
    {
        return Commerce::getInstance()?->getGateways()->getGatewayById($this->gatewayId);
    }

    /**
     * @throws SiteNotFoundException
     * @throws InvalidConfigException
     */
    public function validateCountries($attribute, $params, $validator): void {
        if ($this->$attribute && !$this->hasEnabledCountries()) {
            $this->addError($attribute, 'The store needs to have at least one country enabled when restricting countries.');
        }
    }

    /**
     * @throws SiteNotFoundException
     * @throws InvalidConfigException
     */
    private function hasEnabledCountries(): bool {
        return Commerce::getInstance()?->getStores()->getCurrentStore()->settings->countriesList !== [];
    }

    public function getButtonThemes(): array
    {
        return [
            'paypal' => $this->paypalTheme,
            'applePay' => $this->applePayTheme,
            'googlePay' => $this->googlePayTheme,
        ];
    }

    public function getButtonTypes(): array
    {
        return [
            'paypal' => $this->paypalType,
            'applePay' => $this->applePayType,
            'googlePay' => $this->googlePayType,
        ];
    }

    public function getShowWallets(): array
    {
        return [
            'applePay' => $this->showApplePay,
            'googlePay' => $this->showGooglePay,
            'paypal' => $this->showPayPal,
            'link' => $this->showLink,
            'amazonPay' => $this->showAmazonPay,
        ];
    }
}
