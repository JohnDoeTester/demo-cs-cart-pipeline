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

function fn_block_get_rss_object_info(array $block, $lang_code = CART_LANGUAGE)
{
    $filling = isset($block['content']['filling']) ? (string) $block['content']['filling'] : '' ;
    $schema_values = isset($block['schema']['content']['filling']['values']) ? (array) $block['schema']['content']['filling']['values'] : [];
    $content = __($schema_values[$filling], [], $lang_code);

    return [
        'content' => $content,
    ];
}