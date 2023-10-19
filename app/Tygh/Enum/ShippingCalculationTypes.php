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

namespace Tygh\Enum;


/**
 * Class ShippingCalculationTypes
 *
 * @package Tygh\Enum
 */
class ShippingCalculationTypes
{
    const SKIP_CALCULATION = 'S';

    const CALCULATE_ALL_SHIPPING_METHODS = 'A';

    const CALCULATE_SELECTED_SHIPPING_METHODS = 'E';
}
