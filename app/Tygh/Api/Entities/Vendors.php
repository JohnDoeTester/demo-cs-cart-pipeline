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

namespace Tygh\Api\Entities;

use Tygh\Api\Response;
use Tygh\Enum\VendorStatuses;
use Tygh\Enum\YesNo;

class Vendors extends Stores
{
    /**
     * Returns privileges for Vendors entity
     *
     * @return array<string, string>
     */
    public function privileges()
    {
        return [
            'create' => 'manage_vendors',
            'update' => 'manage_vendors',
            'delete' => 'manage_vendors',
            'index'  => 'view_vendors'
        ];
    }

    /**
     * Creates vendor via API.
     *
     * @param array<string, string|bool> $params Request parameters
     *
     * @return array<string, int|array<string, int|string>>
     */
    public function create($params)
    {
        $status = Response::STATUS_BAD_REQUEST;
        $data = [];

        $this->normalizeParams($params);

        list($valid_params, $data['message']) = $this->checkRequiredParams($params, 'add');

        if ($valid_params) {
            $company_id = fn_update_company($params);

            if ($company_id) {
                $status = Response::STATUS_OK;
                $data = [
                    'company_id' => $company_id,
                ];

                list(, $data['message']) = $this->createVendorAdmin($company_id, $params);
            }
        }

        return [
            'status' => $status,
            'data' => $data,
        ];
    }

    /**
     * @param array<string, string|bool> $params Request params
     * @param string                     $mode   A flag defining the added or updated company.
     *
     * @return array{bool, string}
     */
    protected function checkRequiredParams($params, $mode = 'update')
    {
        $result = [
            'valid_params' => true,
            'message' => '',
        ];

        if ($mode === 'add') {
            if (empty($params['company'])) {
                $result['message'] = __('api_required_field', [
                    '[field]' => 'company'
                ]);
                $result['valid_params'] = false;
            }

            if (empty($params['email'])) {
                $result['message'] = __('api_required_field', [
                    '[field]' => 'email'
                ]);
                $result['valid_params'] = false;
            }
        }

        return [$result['valid_params'], $result['message']];
    }

    /**
     * @param int                        $company_id Company ID
     * @param array<string, string|bool> $params     Request params
     *
     * @return array{bool, string}
     */
    private function createVendorAdmin($company_id, array $params)
    {
        $result = [
            'vendor_admin_created' => false,
            'message' => '',
        ];

        if (!$params['is_create_vendor_admin']) {
            return [$result['vendor_admin_created'], $result['message']];
        }

        if (db_get_field('SELECT COUNT(*) FROM ?:users WHERE email = ?s', $params['email']) > 0) {
            $result['message'] = __('error_admin_not_created_email_already_used');
            return [$result['vendor_admin_created'], $result['message']];
        }

        $company_data = $params;
        $company_data['company_id'] = $company_id;
        $company_data['is_root'] = YesNo::NO;
        $company_data['lang_code'] = $this->getLanguageCode($params);

        if (isset($params['notify_vendor_admin']) && YesNo::toBool($params['notify_vendor_admin'])) {
            fn_create_company_admin($company_data, [], true);
        } else {
            fn_create_company_admin($company_data, []);
        }

        $result['vendor_admin_created'] = true;

        return [$result['vendor_admin_created'], $result['message']];
    }

    /**
     * Gets allowed vendor status.
     *
     * @param array<string, string|bool> $params Request params
     *
     * @return bool|string
     */
    private function getCorrectStatus(array $params)
    {
        $allowed_statuses = VendorStatuses::getStatusesTo();

        return !isset($params['status']) || !in_array($params['status'], $allowed_statuses)
            ? VendorStatuses::ACTIVE
            : $params['status'];
    }

    /**
     * Gets the normalized params.
     *
     * @param array<string, string|bool> $params Request params
     *
     * @return void
     */
    protected function normalizeParams(array &$params)
    {
        unset($params['company_id']);
        $params['status'] = $this->getCorrectStatus($params);
        $params['is_create_vendor_admin'] =
            isset($params['create_vendor_admin']) && YesNo::toBool($params['create_vendor_admin'])
            || isset($params['is_create_vendor_admin']) && YesNo::toBool($params['is_create_vendor_admin']);
        unset($params['create_vendor_admin']);
    }
}
