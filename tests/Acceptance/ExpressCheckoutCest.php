<?php


namespace Tests\Acceptance;

use Craft;
use craftunit\craftstripeexpresscheckout\web\Variable;
use Tests\Support\AcceptanceTester;
use craftunit\craftstripeexpresscheckout\Plugin as StripeExpressCheckout;
use craftunit\craftstripeexpresscheckout\web\Variable as StripeExpressCheckoutVariable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class ExpressCheckoutCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests

    /**
     * @throws SyntaxError
     * @throws \Throwable
     * @throws InvalidConfigException
     * @throws RuntimeError
     * @throws Exception
     * @throws LoaderError
     */
    public function tryToTest(AcceptanceTester $I)
    {
        $var = new Variable();

        $template = $var->buttons([
            'items' => [
                ['id' => 1, 'qty' => 1],
                ['id' => 2, 'qty' => 1],
            ]
        ]);

        dump($template);

        $I->amOnPage('/');
    }
}
