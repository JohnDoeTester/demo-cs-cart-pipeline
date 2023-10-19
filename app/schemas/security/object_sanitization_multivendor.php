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

use Tygh\Tools\SecurityHelper;

defined('BOOTSTRAP') or die('Access denied');

if (fn_allowed_for('MULTIVENDOR:ULTIMATE') && !empty(Tygh::$app['session']['auth']['storefront_id'])) {
    /** @var array $schema */
    $schema['category'][SecurityHelper::SCHEMA_SECTION_FIELD_RULES]['storefront_id'] = SecurityHelper::ACTION_SET_STOREFRONT_ID;
    $schema['shipping'][SecurityHelper::SCHEMA_SECTION_FIELD_RULES]['storefront_ids'] = SecurityHelper::ACTION_SET_STOREFRONT_ID;
    $schema['payment'][SecurityHelper::SCHEMA_SECTION_FIELD_RULES]['storefront_ids'] = SecurityHelper::ACTION_SET_STOREFRONT_ID;
    $schema['user'][SecurityHelper::SCHEMA_SECTION_FIELD_RULES]['storefront_id'] = SecurityHelper::ACTION_SET_STOREFRONT_ID;
}

return $schema;
