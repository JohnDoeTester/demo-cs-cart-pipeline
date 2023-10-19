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

/**
 * Fetches current product id for blocks with variations_filling
 *
 * @param array $block_data
 *
 * @return int
 */
function fn_product_variations_blocks_get_current_product_id($block_data)
{
    if (
        !isset($block_data['content']['items']['filling'])
        || $block_data['content']['items']['filling'] !== 'product_variations.variations_filling'
    ) {
        return 0;
    }

    return isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;
}