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
 * ProductOptionTypes contains possible values for option type
 *
 * @package Tygh\Enum
 */
class ProductOptionTypes
{
    const SELECTBOX = 'S';
    const RADIO_GROUP = 'R';
    const CHECKBOX = 'C';
    const INPUT = 'I';
    const TEXT = 'T';
    const FILE = 'F';

    /**
     * Returns a value indicating whether the option type is selectable.
     *
     * @param string $type Option type
     *
     * @return bool
     */
    public static function isSelectable($type)
    {
        return in_array($type, self::getSelectable(), true);
    }

    /**
     * Gets list of selectable option types.
     *
     * @return array
     */
    public static function getSelectable()
    {
        return array(self::SELECTBOX, self::RADIO_GROUP, self::CHECKBOX);
    }
}