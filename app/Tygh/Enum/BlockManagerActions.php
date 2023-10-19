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
 * BlockManagerActions contains actions that can be performed with the snapping element in the block manager.
 *
 * @package Tygh\Enum
 */
class BlockManagerActions
{
    const ACT_PROPERTIES = 'properties';
    const ACT_SWITCH = 'switch';
    const ACT_DELETE = 'delete';

    public static function getAll()
    {
        return array(
            self::ACT_PROPERTIES => self::ACT_PROPERTIES,
            self::ACT_SWITCH => self::ACT_SWITCH,
            self::ACT_DELETE => self::ACT_DELETE,
        );
    }
}