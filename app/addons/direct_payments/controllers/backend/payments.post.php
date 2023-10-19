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

use Tygh\Enum\ObjectStatuses;
use Tygh\Registry;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendor_id = Registry::get('runtime.company_id');

    if ($vendor_id && defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('force_redirection', fn_url('payments.manage'));
    }

    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'manage') {
    $vendor_id = Registry::get('runtime.company_id');
    /** @var Tygh\SmartyEngine\Core $view */
    $view = &Tygh::$app['view'];
    /** @var array $payments */
    $payments = $view->getTemplateVars('payments');

    if ($vendor_id) {

        $load_admin_payments = array_reduce($payments, function ($carry, $item) {
            return $carry && ($item['status'] === ObjectStatuses::DISABLED || $item['processor_status'] === ObjectStatuses::DISABLED);
        }, true);

        $vendor_payments = $payments;

        $storefront_id = isset($_REQUEST['storefront_id']) ? $_REQUEST['storefront_id'] : 0;
        $admin_payments = fn_get_payments(['company_ids' => [0], 'lang_code' => DESCR_SL, 'storefront_id' => $storefront_id]);

        $admin_payments = array_filter($admin_payments, function ($item) {
            return $item['status'] === ObjectStatuses::ACTIVE
                && $item['processor_status'] === ObjectStatuses::ACTIVE
                && !$item['company_id'];
        });

        $view->assign([
            'show_admin_payments_notification' => $load_admin_payments && $admin_payments,
            'payments'                         => $admin_payments,
            'vendor_payments'                  => $vendor_payments,
        ]);
    }
}

return [CONTROLLER_STATUS_OK];