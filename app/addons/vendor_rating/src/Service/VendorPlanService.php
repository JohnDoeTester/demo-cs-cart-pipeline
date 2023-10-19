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

namespace Tygh\Addons\VendorRating\Service;

use Tygh\Addons\VendorRating\Rating\Storage;

/**
 * Class VendorPlanService provides means to operate vendor plans rating.
 *
 * @package Tygh\Addons\VendorRating\Service
 */
class VendorPlanService
{
    const RATING_STORAGE_OBJECT_TYPE = 'vendor_plan';
    const MIN_RATING = 0;
    const MAX_RATING = 100;

    /**
     * @var \Tygh\Addons\VendorRating\Rating\Storage
     */
    protected $rating_storage;

    /**
     * Service constructor.
     *
     * @param \Tygh\Addons\VendorRating\Rating\Storage $rating_storage
     */
    public function __construct(
        Storage $rating_storage
    ) {
        $this->rating_storage = $rating_storage;
    }

    /**
     * @param int $plan_id
     *
     * @return int
     */
    public function getManualRating($plan_id)
    {
        return $this->rating_storage->getManualRating($plan_id, self::RATING_STORAGE_OBJECT_TYPE);
    }

    /**
     * @param int       $plan_id
     * @param int|float $rating
     *
     * @return int
     */
    public function setManualRating($plan_id, $rating)
    {
        $rating = $this->roundRating($rating);

        return $this->rating_storage->setManualRating($plan_id, self::RATING_STORAGE_OBJECT_TYPE, $rating);
    }

    /**
     * @param int $plan_id
     */
    public function deleteManualRating($plan_id)
    {
        $this->rating_storage->deleteManualRating($plan_id, self::RATING_STORAGE_OBJECT_TYPE);
    }

    /**
     * @param int|float $rating
     *
     * @return int
     */
    protected function roundRating($rating)
    {
        $rating = max($rating, self::MIN_RATING);
        $rating = min($rating, self::MAX_RATING);

        $rating = (int) round($rating);

        return $rating;
    }
}
