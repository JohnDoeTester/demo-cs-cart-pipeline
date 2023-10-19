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

namespace Tygh\Addons\VendorRating\Criteria;

use Tygh\Addons\VendorRating\ServiceProvider;
use Tygh\Application;

/**
 * Class VendorPlansCriteria provides values of vendor plan-specific rating criteria.
 *
 * @package Tygh\Addons\VendorRating\Criteria
 */
class VendorPlansCriteria extends AbstractCriteria
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
     * @return int
     */
    public function getManualRating()
    {
        return $this->getPlanService()->getManualRating($this->getVendorPlanId());
    }

    /**
     * @return \Tygh\Addons\VendorRating\Service\VendorPlanService
     */
    protected function getPlanService()
    {
        return ServiceProvider::getVendorPlanService();
    }

    protected function getVendorPlanId()
    {
        $id = (int) $this->getDb()->getField(
            'SELECT plan_id FROM ?:companies WHERE company_id = ?i',
            $this->company_id
        );

        return $id;
    }

    /**
     * @return \Tygh\Database\Connection
     */
    protected function getDb()
    {
        return $this->application['db'];
    }
}
