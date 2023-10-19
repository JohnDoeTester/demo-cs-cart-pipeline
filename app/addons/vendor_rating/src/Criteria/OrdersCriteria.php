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

/**
 * Class OrdersCriteria provides values of order-specific rating criteria.
 *
 * @package Tygh\Addons\VendorRating\Criteria
 */
class OrdersCriteria extends AbstractCriteria
{
    /**
     * @var \Tygh\Application
     */
    protected $application;

    /**
     * @var string[]
     */
    protected $paid_statuses;

    public function __construct(Application $application, array $paid_statuses)
    {
        $this->application = $application;
        $this->paid_statuses = $paid_statuses;
    }

    /**
     * @return int
     */
    public function getPaidCount()
    {
        $count = (int) $this->getDb()->getField(
            'SELECT COUNT(*) FROM ?:orders WHERE ?w',
            [
                'company_id' => $this->company_id,
                'status'     => $this->paid_statuses,
                ['timestamp', '>=', $this->start_rating_period],
            ]
        );

        return $count;
    }

    /**
     * @return float
     */
    public function getPaidTotal()
    {
        $total = (float) $this->getDb()->getField(
            'SELECT SUM(total) FROM ?:orders WHERE ?w',
            [
                'company_id' => $this->company_id,
                'status'     => $this->paid_statuses,
                ['timestamp', '>=', $this->start_rating_period],
            ]
        );

        return $total;
    }

    /**
     * @return \Tygh\Database\Connection
     */
    protected function getDb()
    {
        return $this->application['db'];
    }
}
