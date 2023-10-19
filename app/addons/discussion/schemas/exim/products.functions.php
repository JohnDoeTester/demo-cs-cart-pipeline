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

use Tygh\Enum\Addons\Discussion\DiscussionTypes;
use Tygh\Registry;

function fn_exim_products_discussion_export($product_id)
{

    $data = fn_get_discussion($product_id, 'P');

    if (!empty($data['type'])) {
        $return = $data['type'];
    } else {
        $return = false;
    }

    return $return;
}

function fn_exim_products_discussion_import($product_id, $discussion_type, $data, $is_new_product)
{
    if (isset($data['Discussion'])) { // field exists in the importing file
        $allowed_discussion_types = array_keys(DiscussionTypes::getAll());

        if (!in_array($discussion_type, $allowed_discussion_types)) {
            $discussion_type = DiscussionTypes::TYPE_DISABLED;
        }
    } elseif ($is_new_product) {
        $discussion_type = Registry::get('settings.discussion.products.product_discussion_type');
    }

    if (!empty($discussion_type)) {
        $product_company_id = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);

        if (empty($product_company_id)
            && $company_id = Registry::get('runtime.company_id')
        ) {
            $product_company_id = $company_id;
        }

        $discussion = array(
            'object_type' => 'P',
            'object_id' => $product_id,
            'type' => $discussion_type,
            'company_id' => $product_company_id
        );

        fn_update_discussion($discussion);

    }

    return true;
}
