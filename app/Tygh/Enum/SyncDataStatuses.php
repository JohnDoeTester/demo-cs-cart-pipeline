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
 * Class SyncDataStatuses
 *
 * @package Tygh\Enum
 */
class SyncDataStatuses
{
    /**
     * New synchronization
     */
    const STATUS_NEW = 'N';

    /**
     * Synchronization in progress
     */
    const STATUS_PROGRESS = 'P';

    /**
     * Synchronization is successfully finished
     */
    const STATUS_SUCCESS = 'S';

    /**
     * Synchronization is unsuccessfully finished
     */
    const STATUS_UNSUCCESS = 'U';
}
