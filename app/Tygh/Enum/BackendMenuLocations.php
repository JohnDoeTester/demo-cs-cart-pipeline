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

namespace Tygh\Enum;

/**
 * BackendMenuLocations contains possible values for locations of admin/vendor menu
 *
 * @package Tygh\Enum
 */
class BackendMenuLocations
{
    const TOP = 'top';
    const CENTRAL = 'central';

    /**
     * Gets list of all backend menu locations
     *
     * @return array<string>
     */
    public static function getAll()
    {
        return [
            self::TOP,
            self::CENTRAL,
        ];
    }
}
