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

namespace Tygh\Enum\Addons\StripeConnect;

use ReflectionClass;

class RejectedReasons
{
    const FRAUD  = 'rejected.fraud';
    const LISTED = 'rejected.listed';
    const TERMS  = 'rejected.terms_of_service';
    const OTHER  = 'rejected.other';

    /**
     * Gets all values
     *
     * @return string[]
     */
    public static function getAll()
    {
        $reflection = new ReflectionClass(static::class);
        return $reflection->getConstants();
    }
}
