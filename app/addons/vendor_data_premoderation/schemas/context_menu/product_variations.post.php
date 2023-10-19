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

use Tygh\ContextMenu\Items\ComponentItem;

defined('BOOTSTRAP') or die('Access denied!');

/** @var array $schema */
$schema['items']['vendor_data_premoderation.product_approval'] = [
    'name'                => ['template' => 'product_approval'],
    'type'                => ComponentItem::class,
    'template'            => 'addons/vendor_data_premoderation/components/context_menu/products/product_approval.tpl',
    'permission_callback' => static function () {
        return fn_check_permissions('premoderation', 'm_approve', 'admin');
    },
    'position'            => 40,
];

return $schema;
