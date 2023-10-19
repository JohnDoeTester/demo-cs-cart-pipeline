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

use Tygh\Application;
use Tygh\Enum\ObjectStatuses;

/**
 * Class ProductsCriteria provides values of product-specific rating criteria.
 *
 * @package Tygh\Addons\VendorRating\Criteria
 */
class ProductsCriteria extends AbstractCriteria
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
    public function getActiveCount()
    {
        $count = (int) $this->getDb()->getField(
            'SELECT COUNT(*) FROM ?:products WHERE ?w',
            [
                'product_type' => 'P',
                'company_id'   => $this->company_id,
                'status'       => ObjectStatuses::ACTIVE,
                ['timestamp', '>=', $this->start_rating_period],
            ]
        );

        return $count;
    }

    /**
     * @return \Tygh\Database\Connection
     */
    protected function getDb()
    {
        return $this->application['db'];
    }
}
