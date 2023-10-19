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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'view' || $mode == 'quick_view') {

    /** @var array $product */
    $product = Tygh::$app['view']->getTemplateVars('product');

    $product['discussion'] = fn_get_discussion($product['product_id'], "P", true, $_REQUEST);

    $product['discussion_type'] = empty($product['discussion']) ? DiscussionTypes::TYPE_DISABLED : $product['discussion']['type'];

    Tygh::$app['view']->assign('product', $product);
}
