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

namespace Tygh\Shippings;

/**
 * Shipping pickup services interface
 */
interface IPickupService
{
    /**
     * Gets minimal cost among available pickup points
     *
     * @return float|false
     */
    public function getPickupMinCost();

    /**
     * This method is for later use. For now it's better to return an empty array
     * because the structure of pickup points list is not unified yet.
     *
     * @return array
     */
    public function getPickupPoints();

    /**
     * Gets pickup points quantity
     *
     * @return int|false
     */
    public function getPickupPointsQuantity();
}
