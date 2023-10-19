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

namespace Tygh\Enum\Addons\ProductReviews;

/**
 * Class MessageTypes
 *
 * @package Tygh\Enum\Addons\ProductReviews
 */
class ProductReviewsMessageTypes
{
    const ADVANTAGES    = 'advantages';
    const DISADVANTAGES = 'disadvantages';
    const COMMENT       = 'comment';

    /**
     * Gets types by mode
     *
     * @param string|null $mode Mode (see Registry::get('addons.product_reviews.review_fields'))
     *
     * @return string[]
     */
    public static function getTypes($mode = null)
    {
        if ($mode === null || $mode === 'advanced') {
            return [self::ADVANTAGES, self::DISADVANTAGES, self::COMMENT];
        }

        return [self::COMMENT];
    }
}
