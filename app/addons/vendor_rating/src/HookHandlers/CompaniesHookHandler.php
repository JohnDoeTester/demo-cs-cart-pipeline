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

namespace Tygh\Addons\VendorRating\HookHandlers;

use Tygh\Addons\VendorRating\Service\VendorService;
use Tygh\Addons\VendorRating\ServiceProvider;
use Tygh\Application;

/**
 * Class CompaniesHookHandler contains company-specific hook processors.
 *
 * @package Tygh\Addons\VendorRating\HookHandlers
 */
class CompaniesHookHandler
{
    /**
     * @var \Tygh\Application
     */
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * The "companies_sorting" hook handler.
     *
     * Actions performed:
     *     - Adds the "Vendor rating" to the list of possible products sort criteria.
     *
     * @see \fn_get_companies_sorting()
     */
    public function onGetSorting(&$sorting)
    {
        $sorting['absolute_vendor_rating'] = [
            'description'   => __('vendor_rating.vendor_rating'),
            'default_order' => 'desc',
        ];
    }

    /**
     * The "get_companies" hook handler.
     *
     * Actions performed:
     *     - Adds query conditions to implement the "Vendor rating" sorting criteria.
     *
     * @see \fn_get_companies()
     */
    public function onGetCompanies(
        $params,
        &$fields,
        &$sortings,
        $condition,
        &$join,
        $auth,
        $lang_code,
        $group
    ) {
        /** @var \Tygh\Database\Connection $db */
        $db = $this->application['db'];

        $fields[] = 'absolute_rating.rating AS absolute_vendor_rating';
        $join .= $db->quote(
            ' LEFT JOIN ?:absolute_rating AS absolute_rating'
            . ' ON absolute_rating.object_id = ?:companies.company_id'
            . ' AND absolute_rating.object_type = ?s',
            VendorService::RATING_STORAGE_OBJECT_TYPE
        );

        $sortings['absolute_vendor_rating'] = 'absolute_rating.rating';
    }

    /**
     * The "get_companies_post" hook handler.
     *
     * Actions performed:
     * - Loads relative vendor rating for companies.
     *
     * @see \fn_get_companies
     */
    public function onAfterGetCompanies(
        $params,
        $auth,
        $items_per_page,
        $lang_code,
        &$companies
    ) {
        $service = ServiceProvider::getVendorService();

        foreach ($companies as &$company) {
            $company['relative_vendor_rating'] = $service->getRelativeRating($company['company_id']);
        }
        unset($company);
    }

    /**
     * The "get_company_data_post" hook handler.
     *
     * Action performed:
     *     -  Adds absolute and relative vendor rating to the company data.
     *
     * @see \fn_get_company_data()
     */
    public function afterGetCompany($company_id, $lang_code, $extra, &$company_data)
    {
        if (!$company_data) {
            return;
        }

        $service = ServiceProvider::getVendorService();

        $company_data['absolute_vendor_rating'] = $service->getAbsouluteRating($company_id);
        $company_data['relative_vendor_rating'] = $service->getRelativeRating($company_id);
        $company_data['absolute_vendor_rating_updated_timestamp'] = $service->getAbsouluteRatingUpdatedAt($company_id);
        $company_data['manual_vendor_rating'] = $service->getManualRating($company_id);
    }

    /**
     * The "update_company" hook handler.
     *
     * Action performed:
     *     - Saves manually set vendor rating.
     *
     * @see \fn_update_company()
     */
    public function onUpdate($company_data, $company_id, $lang_code, $action)
    {
        if (fn_get_runtime_company_id()) {
            return;
        }

        if (isset($company_data['manual_vendor_rating'])) {
            $service = ServiceProvider::getVendorService();
            $service->setManualRating($company_id, $company_data['manual_vendor_rating']);
        }
    }

    /**
     * The "delete_company" hook handler.
     *
     * Actions performed:
     *     - Removes absolute vendor rating.
     *
     * @see \fn_delete_company()
     */
    public function onDelete($company_id, $result, $storefronts)
    {
        $service = ServiceProvider::getVendorService();
        $service->deleteManualRating($company_id);
        $service->deleteAbsoluteRating($company_id);
    }
}
