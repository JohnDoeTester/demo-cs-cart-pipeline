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

namespace Tygh\Enum\Addons\Discussion;

/**
 * DiscussionTypes contains available discussion types for products, categories, etc.
 *
 * @package Tygh\Enum
 */
class DiscussionTypes
{
    const TYPE_COMMUNICATION_AND_RATING = 'B';
    const TYPE_COMMUNICATION = 'C';
    const TYPE_RATING = 'R';
    const TYPE_DISABLED = 'D';

    public static function getAll()
    {
        return array(
            self::TYPE_COMMUNICATION_AND_RATING => __('communication_and_rating'),
            self::TYPE_COMMUNICATION            => __('communication'),
            self::TYPE_RATING                   => __('rating'),
            self::TYPE_DISABLED                 => __('disabled'),
        );
    }
}