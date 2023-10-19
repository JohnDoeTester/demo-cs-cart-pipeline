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
 * Class ReturnsCriteria provides values of return request-specific rating criteria.
 *
 * @package Tygh\Addons\VendorRating\Criteria
 */
class ReturnsCriteria extends AbstractCriteria
{
    /**
     * @var \Tygh\Application
     */
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function getCount()
    {
        list(, $params) = fn_rma_get_returns(
            [
                'company_id' => $this->company_id,
                'period'     => 'C',
                'time_from'  => $this->getFormatter()->asDatetime($this->start_rating_period),
            ],
            1
        );

        return (int) $params['total_items'];
    }

    /**
     * @return \Tygh\Tools\Formatter
     */
    protected function getFormatter()
    {
        return $this->application['formatter'];
    }
}
