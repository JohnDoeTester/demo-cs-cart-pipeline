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

use Tygh\Enum\ShopifyImportStatuses;

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */
$schema['shopify_import_success'] = static function () {
    return (bool) db_get_field(
        'SELECT import_id FROM ?:shopify_imports WHERE status = ?s LIMIT 1',
        ShopifyImportStatuses::SUCCESS
    );
};

return $schema;
