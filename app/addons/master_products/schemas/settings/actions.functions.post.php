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

use Tygh\Enum\NotificationSeverity;
use Tygh\Addons\ProductVariations\Product\Type\Type;

defined('BOOTSTRAP') or die('Access denied');

function fn_settings_actions_general_show_out_of_stock_products(&$new_value, $old_value)
{
    fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('master_products.resave_after_show_out_of_stock_products_changed', [
        '[url]' => fn_url('products.manage?product_type=' . Type::PRODUCT_TYPE_SIMPLE)
    ]));

    return true;
}
