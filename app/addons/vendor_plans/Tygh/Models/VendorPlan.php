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

namespace Tygh\Models;

use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Models\Components\AModel;
use Tygh\Models\Components\Relation;
use Tygh\Registry;

/**
 * Class VendorPlan
 *
 * @property string $plan_id
 * @property string $lang_code
 * @property string $plan
 * @property string $description
 * @property string $status
 * @property string $position
 * @property string $price
 * @property string $periodicity
 * @property string $commission
 * @property string $fixed_commission
 * @property string $products_limit
 * @property string $revenue_limit
 * @property string $is_default
 * @property null|string $companies_count
 * @property string $usergroups
 * @property int[] $usergroup_ids
 * @property int[] $category_ids
 * @property int[] $storefront_ids
 *
 * @package Tygh\Models
 */
class VendorPlan extends AModel
{
    public function getTableName()
    {
        return '?:vendor_plans';
    }

    public function getPrimaryField()
    {
        return 'plan_id';
    }

    public function getDescriptionTableName()
    {
        return '?:vendor_plan_descriptions';
    }

    public function getFields($params)
    {
        $fields = array(
            '?:vendor_plan_descriptions.*',
            '?:vendor_plans.*',
        );

        /**
         * Change fields list for main SQL query
         *
         * @param object $instance Current model instance
         * @param array  $fields   Fields list
         * @param array  $params   Params array
         */
        fn_set_hook('vendor_plan_get_fields', $this, $fields, $params);

        return $fields;
    }

    public function getSearchFields()
    {
        $search_fields = array(
            'number' => array(
                'is_default',
            ),
            'string' => array(
                'status',
            ),
            'text' => array(
                'plan',
            ),
            'range' => array(
                'price',
            ),
            'in' => array(
                'periodicity'
            ),
        );

        /**
         * Setting search fields schema
         *
         * @param object $instance      Current model instance
         * @param array  $search_fields Fields list
         */
        fn_set_hook('vendor_plan_get_search_fields', $this, $search_fields);

        return $search_fields;
    }

    public function getSortFields()
    {
        $sort_fields = array(
            'position' => 'position',
            'plan'     => 'plan',
            'status'   => 'status',
            'price'    => 'price',
        );

        /**
         * Setting sorting fields schema
         *
         * @param object $instance    Current model instance
         * @param array  $sort_fields Sorting fields schema
         */
        fn_set_hook('vendor_plan_get_sort_fields', $this, $sort_fields);

        return $sort_fields;
    }

    public function getRelations()
    {
        $relations = array(
            'companies' => array(Relation::HAS_MANY, 'Tygh\Models\Company', 'plan_id'),
            'companiesCount' => array(Relation::HAS_MANY, 'Tygh\Models\Company', 'plan_id', null, array(
                'get_count' => true,
            )),
        );

        /**
         * Setting relations schema
         *
         * @param object $instance  Current model instance
         * @param array  $relations Relations schema
         */
        fn_set_hook('vendor_plan_get_relations', $this, $relations);

        return $relations;
    }

    public function getExtraCondition($params)
    {
        $condition = [];
        $is_admin = SiteArea::isAdmin(AREA) && !Registry::get('runtime.company_id');

        // Getting plan by company_id
        if (isset($params['company_id'])) {
            $company_plan_id = db_get_field(
                "SELECT plan_id FROM ?:companies WHERE company_id = ?i", $params['company_id']
            );
            $condition['company_id'] = db_quote("?:vendor_plans.plan_id = ?i", $company_plan_id);
        }

        // Getting plans available for selected company
        if (isset($params['allowed_for_company_id'])) {
            $statuses = [ObjectStatuses::ACTIVE];
            if ($is_admin) {
                $statuses[] = ObjectStatuses::HIDDEN;
            }
            $status_conditions = db_quote('status IN(?a)', $statuses);

            $storefront_conditions = '';
            if (!$is_admin && $params['allowed_for_company_id']) {
                $storefront_sub_conditions = ['storefronts IS NULL OR storefronts = ""'];
                $storefront_ids = db_get_fields(
                    'SELECT storefront_id FROM ?:storefronts LEFT JOIN ?:storefronts_companies USING(storefront_id)
                     WHERE company_id = ?i OR company_id IS NULL',
                    $params['allowed_for_company_id']
                );

                if ($storefront_ids) {
                    foreach ($storefront_ids as $storefront_id) {
                        $storefront_sub_conditions[] = db_quote('FIND_IN_SET(?i, storefronts)', $storefront_id);
                    }
                }

                $storefront_conditions = sprintf('(%s)', implode(' OR ', $storefront_sub_conditions));
            }

            $sub_conditions = [
                $storefront_conditions ? db_quote('(?p AND ?p)', $status_conditions, $storefront_conditions) : $status_conditions
            ];

            if ($params['allowed_for_company_id']) {
                $company_plan_id = db_get_field(
                    "SELECT plan_id FROM ?:companies WHERE company_id = ?i", $params['allowed_for_company_id']
                );
                if ($company_plan_id) {
                    $sub_conditions[] = db_quote("?:vendor_plans.plan_id = ?i", $company_plan_id);
                }
            }

            $condition['allowed_for_company_id'] = sprintf('(%s)', implode(' OR ', $sub_conditions));
        }

        if (isset($params['storefront_id'])) {
            $condition['storefront_id'] = db_quote("(FIND_IN_SET(?i, storefronts) OR storefronts IS NULL OR storefronts = '')", $params['storefront_id']);
        }

        // Getting plans depending on the availability of the vendor store
        if (isset($params['vendor_store'])) {
            $condition['vendor_store'] = db_quote('?:vendor_plans.vendor_store = ?i', $params['vendor_store']);
        }

        // Getting plans that are not disabled.
        if (isset($params['can_be_default']) && $params['can_be_default']) {
            $condition['can_be_default'] = db_quote('?:vendor_plans.status <> ?s', ObjectStatuses::DISABLED);
        }

        if (isset($params['is_fulfillment_by_marketplace'])) {
            $condition['fulfillment'] = db_quote('?:vendor_plans.is_fulfillment_by_marketplace = ?s', $params['is_fulfillment_by_marketplace']);
        }

        return $condition;
    }

    public function prepareQuery(&$params, &$fields, &$sorting, &$joins, &$condition)
    {
        /**
         * Change SQL parameters for vendor plans select
         *
         * @param object $instance   Current model instance
         * @param array  $params     Params array
         * @param array  $fields     Fields list
         * @param array  $sortings   Sortings list
         * @param array  $joins      Joins list
         * @param array  $condition  Conditions list
         */
        fn_set_hook('vendor_plan_get_list', $this, $params, $fields, $sorting, $joins, $condition);
    }

    public function gatherAdditionalItemsData(&$items, $params)
    {
        $plan_ids = array();
        foreach ($items as $item) {
            $plan_ids[] = $item['plan_id'];
        }

        if (!empty($params['get_companies_count']) && $items) {
            $companies = db_get_hash_single_array(
                "SELECT plan_id, COUNT(company_id) as companies FROM ?:companies WHERE plan_id IN(?n) GROUP BY plan_id",
                array('plan_id', 'companies'), $plan_ids
            );
        }

        $current_usage = array();
        if (!empty($params['check_availability']) && !empty($params['allowed_for_company_id'])) {
            $company = Company::model()->find($params['allowed_for_company_id']);
            $current_usage = array(
                'products' => $company->getCurrentProductsCount(),
                'revenue'  => $company->getCurrentRevenue(),
            );
        }

        foreach ($items as &$item) {
            $item['category_ids'] = !empty($item['categories']) ? explode(',', $item['categories']) : [];
            $item['storefront_ids'] = !empty($item['storefronts']) ? explode(',', $item['storefronts']) : [];
            $item['usergroup_ids'] = !empty($item['usergroups']) ? explode(',', $item['usergroups']) : [];
            if (!empty($params['get_companies_count'])) {
                $item['companies_count'] = isset($companies[$item['plan_id']]) ? $companies[$item['plan_id']] : 0;
            }
            if ($current_usage) {
                $item['avail_errors'] = array();
                if ($item['products_limit'] && $item['products_limit'] < $current_usage['products']) {
                    $item['avail_errors'][] = __('vendor_plans.many_products_text', array(
                        '[actual]' => intval($current_usage['products']),
                        '[allowed]' => intval($item['products_limit']),
                    ));
                }
                if (floatval($item['revenue_limit']) && $item['revenue_limit'] < $current_usage['revenue']) {
                    $item['avail_errors'][] = __('vendor_plans.much_revenue_text', array(
                        '[actual]' => self::formatPrice($current_usage['revenue']),
                        '[allowed]' => self::formatPrice($item['revenue_limit']),
                    ));
                }
            }
        }

        /**
         * Process selected vendor plans data
         *
         * @param object $instance Current model instance
         * @param array  $items    Items
         * @param array  $params   Params array
         */
        fn_set_hook('vendor_plan_get_list_post', $this, $items, $params);
    }

    public function beforeSave()
    {
        $result = true;

        if (empty($this->categories)) {
            $this->category_ids = [];
        } elseif (is_array($this->categories)) {
            $this->category_ids = $this->categories;
            $this->categories = implode(',', $this->categories);
        } elseif (is_string($this->categories)) {
            $this->category_ids = array_map('intval', explode(',', $this->categories));
        }

        if (empty($this->storefronts)) {
            $this->storefront_ids = [];
        } elseif (is_array($this->storefronts)) {
            $this->storefront_ids = $this->storefronts;
            $this->storefronts = implode(',', $this->storefronts);
        } elseif (is_string($this->storefronts)) {
            $this->storefront_ids = array_map('intval', explode(',', $this->storefronts));
        }

        if (empty($this->usergroups)) {
            $this->usergroup_ids = [];
        } elseif (is_array($this->usergroups)) {
            $this->usergroup_ids = $this->usergroups;
            $this->usergroups = implode(',', $this->usergroups);
        } elseif (is_string($this->usergroups)) {
            $this->usergroup_ids = array_map('intval', explode(',', $this->usergroups));
        }

        if (
            $this->status === ObjectStatuses::DISABLED
            && isset($this->current_attributes['status'])
            && $this->current_attributes['status'] !== ObjectStatuses::DISABLED
        ) {
            if ((int) $this->companiesCount) {
                $result = false;
                fn_set_notification(NotificationSeverity::ERROR, __('error'), __('vendor_plans.disable_plan_vendor_exists_text'));
            }
            if (isset($this->current_attributes['is_default']) && $this->current_attributes['is_default']) {
                $result = false;
                fn_set_notification(NotificationSeverity::ERROR, __('error'), __('vendor_plans.disable_plan_is_default_text'));
            }
        }

        /**
         * Actions before saving plan data
         *
         * @param object $plan   Instance of VendorPlan
         * @param bool   $result Can save flag
         */
        fn_set_hook('vendor_plan_before_save', $this, $result);

        return $result;
    }

    protected function update()
    {
        $result = parent::update();

        $companies = Company::model()->findAll(['plan_id' => $this->id]);
        $companies = array_column($companies, 'id');

        /**
         * Actions update plan data
         *
         * @param object $plan      Instance of VendorPlan
         * @param bool   $result    Can save flag
         * @param int[]  $companies Companies
         */
        fn_set_hook('vendor_plan_update', $this, $result, $companies);

        if (empty($this->params['storefront_repository'])) {
            return $result;
        }

        if ($companies && !empty($this->attributes['remove_vendors_from_old_storefronts'])) {
            $removed_storefronts = array_diff($this->current_attributes['storefront_ids'], $this->attributes['storefront_ids']);
            /** @var \Tygh\Storefront\Repository $storefront_repository */
            $storefront_repository = $this->params['storefront_repository'];
            $storefront_repository->removeCompaniesFromStorefronts($companies, $removed_storefronts);
        }

        if ($companies && !empty($this->attributes['add_vendors_to_new_storefronts'])) {
            $added_storefronts = array_diff($this->attributes['storefront_ids'], $this->current_attributes['storefront_ids']);
            /** @var \Tygh\Storefront\Repository $storefront_repository */
            $storefront_repository = $this->params['storefront_repository'];
            $storefront_repository->addCompaniesToStorefronts($companies, $added_storefronts);
        }

        return $result;
    }

    public function afterSave()
    {
        if (!empty($this->is_default)) {
            db_query("UPDATE ?:vendor_plans SET is_default = 0 WHERE plan_id <> ?i", $this->plan_id);
        }

        /**
         * Actions after saving plan data
         *
         * @param object $plan Instance of VendorPlan
         */
        fn_set_hook('vendor_plan_after_save', $this);
    }

    public function beforeDelete()
    {

        $result = true;

        $company_exist = db_get_field("SELECT company FROM ?:companies WHERE plan_id = ?i", $this->plan_id);
        if ($company_exist) {
            fn_set_notification('E', __('error'), __('vendor_plans.delete_plan_vendor_exists_text'));

            $result = false;
        }

        /**
         * Actions before deleting plan
         *
         * @param object $plan   Instance of VendorPlan
         * @param bool   $result Can delete flag
         */
        fn_set_hook('vendor_plan_before_delete', $this, $result);

        return $result;
    }

    public function afterDelete()
    {
        /**
         * Executes after a vendor plan is deleted, allows you to execute additional actions with the related entities
         *
         * @param \Tygh\Models\VendorPlan $this Instance of VendorPlan
         */
        fn_set_hook('vendor_plan_after_delete', $this);
    }

    /**
     * Gets vendor plan status text
     *
     * @return string
     */
    public function getStatusText()
    {
        switch ($this->status) {
            case ObjectStatuses::ACTIVE:
                return __('active');
                break;
            case ObjectStatuses::HIDDEN:
                return __('hidden');
                break;
            default:
            case ObjectStatuses::DISABLED:
                return __('disabled');
                break;
        }
    }

    /**
     * Get available plans for block
     * @return array
     */
    public static function getAvailablePlans()
    {
        return static::model()->findMany(array(
            'allowed_for_company_id' => Registry::get('runtime.company_id'),
        ));
    }

    public static function getPeriodicitiesList()
    {
        return db_get_list_elements('vendor_plans', 'periodicity', true, CART_LANGUAGE, 'vendor_plans.periodicity_');
    }

    public function commissionRound()
    {
        return floatval($this->commission);
    }

    public static function formatPrice($price)
    {
        $currency = Registry::get('currencies.' . CART_PRIMARY_CURRENCY);

        $price = fn_format_rate_value(
            $price,
            'F',
            $currency['decimals'],
            $currency['decimals_separator'],
            $currency['thousands_separator'],
            $currency['coefficient']
        );

        return $currency['after'] == 'Y' ? $price . $currency['symbol'] : $currency['symbol'] . $price;
    }

}
