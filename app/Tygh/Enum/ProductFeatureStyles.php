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
 * ProductFeatureStyles contains possible values for feature style
 *
 * @package Tygh\Enum
 */
class ProductFeatureStyles
{
    const MULTIPLE_CHECKBOX = 'multiple_checkbox';
    const DROP_DOWN = 'dropdown';
    const DROP_DOWN_IMAGES = 'dropdown_images';
    const DROP_DOWN_LABELS = 'dropdown_labels';
    const CHECKBOX = 'checkbox';
    const NUMBER = 'number';
    const BRAND = 'brand';
    const COLOR = 'color';
    const TEXT = 'text';

    public static function getAllStyles()
    {
        return [
            self::MULTIPLE_CHECKBOX,
            self::DROP_DOWN,
            self::DROP_DOWN_IMAGES,
            self::DROP_DOWN_LABELS,
            self::CHECKBOX,
            self::NUMBER,
            self::BRAND,
            self::COLOR,
            self::TEXT,
        ];
    }
}
