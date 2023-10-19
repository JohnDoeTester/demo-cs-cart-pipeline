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

defined('BOOTSTRAP') or die('Access denied');

function fn_settings_variants_addons_vendor_data_premoderation_product_premoderation_fields()
{
    $variants = [
        'product_prices:*'    => __('price'),
        'products:list_price' => __('list_price'),
        'products:amount'     => __('quantity'),
    ];

    /**
     * Executes when getting variants of the "Require approval for updates of" setting of Product for
     * the Vendor data premoderation add-on, allows you to add new variants or modify the existing ones
     *
     * @param string[] $variants Setting variants
     */
    fn_set_hook('settings_variants_addons_vendor_data_premoderation_product_premoderation_fields_post', $variants);

    return $variants;
}
