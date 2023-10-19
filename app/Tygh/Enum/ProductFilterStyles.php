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
 * ProductFilterStyles contains possible values for filter style
 *
 * @package Tygh\Enum
 */
class ProductFilterStyles
{
    const CHECKBOX = 'checkbox';
    const SLIDER = 'slider';
    const COLOR = 'color';
    const DATE = 'date';

    public static function getAllStyles()
    {
        return [
            self::CHECKBOX,
            self::SLIDER,
            self::COLOR,
            self::DATE,
        ];
    }
}
