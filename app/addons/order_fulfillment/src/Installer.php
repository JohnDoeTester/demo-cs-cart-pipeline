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

namespace Tygh\Addons\OrderFulfillment;

use Tygh\Addons\InstallerInterface;
use Tygh\Core\ApplicationInterface;
use Tygh\Enum\NotificationSeverity;
use Tygh\Tygh;

class Installer implements InstallerInterface
{

    /**
     * @inheritDoc
     */
    public static function factory(ApplicationInterface $app)
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function onBeforeInstall()
    {
    }

    /**
     * @inheritDoc
     */
    public function onInstall()
    {
        $auth = Tygh::$app['session']['auth'];
        list($all_companies,) = fn_get_companies([], $auth);
        foreach ($all_companies as $company) {
            /** @var false|array<string, string|array<string>> $company_data */
            $company_data = fn_get_company_data($company['company_id']);
            if (!$company_data || empty($company_data['shippings_ids'])) {
                continue;
            }
            $company_data['saved_shippings_state'] = $company_data['shippings'];
            $company_data['shippings'] = [];
            fn_update_company($company_data, $company['company_id']);
        }
    }

    /**
     * @inheritDoc
     */
    public function onUninstall()
    {
        $auth = Tygh::$app['session']['auth'];
        list($all_companies,) = fn_get_companies([], $auth);
        foreach ($all_companies as $company) {
            /** @var false|array<string, string|array<string>> $company_data */
            $company_data = fn_get_company_data($company['company_id']);
            if (
                !$company_data
                || (empty($company_data['shippings_ids']) && empty($company_data['saved_shippings_state']))
            ) {
                continue;
            }
            $company_data['shippings'] = explode(',', $company_data['saved_shippings_state']);
            fn_update_company($company_data, $company['company_id']);
        }
    }
}
