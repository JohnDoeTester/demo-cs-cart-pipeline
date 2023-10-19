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

/**
 * Class AbstractCriteria is a base class that can be used for rating criteria provider.
 *
 * @package Tygh\Addons\VendorRating\Criteria
 */
abstract class AbstractCriteria implements CriteriaInterface
{
    /**
     * @var int
     */
    protected $company_id;

    /**
     * @var int
     */
    protected $start_rating_period;

    /** @inheritDoc */
    public function init($company_id, $start_rating_period)
    {
        $this->company_id = (int) $company_id;
        $this->start_rating_period = (int) $start_rating_period;
    }
}
