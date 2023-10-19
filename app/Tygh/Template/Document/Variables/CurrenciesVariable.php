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

namespace Tygh\Template\Document\Variables;

use Tygh\Registry;
use Tygh\Template\IActiveVariable;
use Tygh\Template\IContext;

/**
 * Represents currencies variable for document editor.
 *
 * @package Tygh\Template\Document\Variables
 */
class CurrenciesVariable extends GenericVariable implements IActiveVariable
{
    /**
     * @inheritDoc
     */
    public function __construct(IContext $context, array $config)
    {
        $config['data'] = Registry::get('currencies');

        parent::__construct($context, $config);
    }

    /**
     * @inheritDoc
     */
    public static function attributes()
    {
        $currencies = Registry::get('currencies');

        $result = array();

        foreach ($currencies as $code => $currency) {
            $result[$code] = array_keys($currency);
        }

        return $result;
    }
}