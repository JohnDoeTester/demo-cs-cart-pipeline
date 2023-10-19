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

use Tygh\Providers\StorefrontProvider;

defined('BOOTSTRAP') or die('Access denied');

/** @var string $mode */

if ($mode === 'generate') {
    fn_disable_live_editor_mode();

    $schema = fn_get_schema('price_list', 'schema');
    if (empty($_REQUEST['display']) || empty($schema['types'][$_REQUEST['display']])) {
        return [CONTROLLER_STATUS_DENIED];
    }

    $class_name = '\Tygh\PriceList\\' . fn_camelize($_REQUEST['display']);
    if (class_exists($class_name)) {
        $generator = new $class_name();
        if (isset($_REQUEST['storefront_id'])) {
            $generator->setStorefrontId($_REQUEST['storefront_id']);
        } elseif (fn_allowed_for('ULTIMATE')) {
            $company_id = fn_get_runtime_company_id();
            $repository = StorefrontProvider::getRepository();
            /** @var \Tygh\Storefront\Storefront|null $storefront */
            $storefront = $repository->findByCompanyId($company_id, true);
            if ($storefront) {
                $generator->setStoreFrontId($storefront->storefront_id);
            }
        }
        $generator->generate(true);
    }

    return [CONTROLLER_STATUS_NO_CONTENT];
}
