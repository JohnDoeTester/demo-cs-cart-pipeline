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

use Tygh\Addons\VendorRating\Service\VendorPlanService;
use Tygh\Addons\VendorRating\ServiceProvider;
use Tygh\Application;

/**
 * Class VendorPlansHookHandler contains vendor plan-specific hook processors.
 *
 * @package Tygh\Addons\VendorRating\HookHandlers
 */
class VendorPlansHookHandler
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
     * The "vendor_plan_get_list" hook handler.
     *
     * Actions performed:
     *     - Loads manual vendor rating for plans.
     *
     * @see \Tygh\Models\VendorPlan::prepareQuery()
     */
    public function onGetVendorPlans($instance, $params, &$fields, $sorting, &$joins, $condition)
    {
        /** @var \Tygh\Database\Connection $db */
        $db = $this->application['db'];

        $joins['manual_rating'] = $db->quote(
            'LEFT JOIN ?:manual_rating AS manual_rating'
            . ' ON manual_rating.object_id = ?:vendor_plans.plan_id'
            . ' AND manual_rating.object_type = ?s',
            VendorPlanService::RATING_STORAGE_OBJECT_TYPE
        );

        $fields['manual_rating'] = 'manual_rating.rating AS manual_rating';
    }

    /**
     * The "vendor_plan_update" hook handler.
     *
     * Actions performed:
     *     - Saves manually set vendor plan rating.
     *
     * @see \Tygh\Models\VendorPlan::update()
     */
    public function onUpdate($instance)
    {
        if (!isset($instance['manual_rating'])) {
            return;
        }

        /** @var \Tygh\Models\VendorPlan $instance */
        $service = ServiceProvider::getVendorPlanService();

        $service->setManualRating($instance['plan_id'], $instance['manual_rating']);
    }

    /**
     * The "vendor_plan_after_delete" hook handler.
     *
     * Actions performed:
     *     - Removes manual vendor plan rating.
     *
     * @see \Tygh\Models\VendorPlan::afterDelete()
     */
    public function onDelete($instance)
    {
        $service = ServiceProvider::getVendorPlanService();
        $service->deleteManualRating($instance['plan_id']);
    }
}
