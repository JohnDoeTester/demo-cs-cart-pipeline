<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\Addons\Stripe;

use Tygh\Registry;
use Tygh\Tools\Formatter;

/**
 * Class PriceFormatter formats prices for Stripe payments.
 *
 * @package Tygh\Addons\Stripe
 */
class PriceFormatter
{
    /**
     * @var \Tygh\Tools\Formatter
     */
    protected $formatter;

    /**
     * @var array<string, array<string>> $currencies_exceptions
     */
    private $currencies_exceptions = [
        'zero_decimal' => [
            'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'
        ],
        'three_decimal' => [
            'BHD', 'JOD', 'KWD', 'OMR', 'TND'
        ]
    ];

    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Formats payment amount by currency.
     *
     * @param float  $amount   Payment amount
     * @param string $currency Currency code
     *
     * @return int
     */
    public function asCents($amount, $currency)
    {
        $amount = $this->formatter->asPrice($amount, $currency, false, false);

        return $this->convertToCents($amount, $currency);
    }

    /**
     * Converts amount to smallest currency unit.
     *
     * @param float  $amount        Monetary amount
     * @param string $currency_code Currency code
     *
     * @return int Amount in cents
     */
    protected function convertToCents($amount, $currency_code = null)
    {
        $amount = $this->convertByDecimals($amount, $currency_code);

        $amount = preg_replace('/\D/', '', $amount);

        return (int) ltrim($amount, '0');
    }

    /**
     * Converts amount to smallest currency unit.
     *
     * @param float  $amount        Monetary amount
     * @param string $currency_code Currency code
     *
     * @return string|float Amount in cents
     */
    protected function convertByDecimals($amount, $currency_code = null)
    {
        if (
            $currency_code
            && !in_array($currency_code, $this->currencies_exceptions['zero_decimal'], true)
        ) {
            $currency = Registry::get('currencies.' . $currency_code);
            if (in_array($currency_code, $this->currencies_exceptions['three_decimal'], true)) {
                if ((int) $currency['decimals'] < 3) {
                    switch ((int) $currency['decimals']) {
                        case 0:
                            $amount .= '000';
                            break;
                        case 1:
                            $amount .= '00';
                            break;
                        case 2:
                            $amount .= '0';
                            break;
                    }
                }
            } else {
                if ((int) $currency['decimals'] < 2) {
                    $amount .= ((2 - (int) $currency['decimals']) === 1) ? '0' : '00';
                }
            }
        }

        return $amount;
    }
}
