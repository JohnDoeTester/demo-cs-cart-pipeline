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

function fn_block_get_blog_info(array $block, $lang_code = CART_LANGUAGE)
{
    $items = isset($block['content']['items']) ? $block['content']['items'] : [];
    $filling = isset($items['filling']) ? (string) $items['filling'] : '' ;
    $limit = isset($items['limit']) ? $items['limit'] : (isset($block['properties']['limit']) ? $block['properties']['limit'] : 0);
    $filling_text = fn_is_lang_var_exists($filling) ? __($filling, [], $lang_code) : '';
    $content = ($filling_text) ? sprintf('%s, %s', $filling_text, __('n_posts', [$limit], $lang_code)) : __('n_posts', [$limit], $lang_code);

    return [
        'content' => $content,
    ];
}
