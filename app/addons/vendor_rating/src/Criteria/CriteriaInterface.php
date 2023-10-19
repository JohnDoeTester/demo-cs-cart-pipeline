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
 * Interface CriteriaInterface describes the vendor rating critiera provider.
 *
 * @package Tygh\Addons\VendorRating\Criteria
 */
interface CriteriaInterface
{
    /**
     * @param int $company_id
     * @param int $start_rating_period
     */
    public function init($company_id, $start_rating_period);
}
