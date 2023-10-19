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

namespace Tygh\Enum\Addons\Recaptcha;

/**
 * Class RecaptchaTypes
 * Describes types of recaptcha
 *
 * @package Tygh\Addons\Recaptcha\Enum
 */
class RecaptchaTypes
{
    const RECAPTCHA_TYPE_V2 = 'recaptcha_v2';
    const RECAPTCHA_TYPE_V3 = 'recaptcha_v3';

    /**
     * Validates type
     *
     * @param string $type recapthca type
     *
     * @return bool
     */
    public static function isRecapthcaType($type)
    {
        if (in_array($type, [static::RECAPTCHA_TYPE_V2, static::RECAPTCHA_TYPE_V3])) {
            return true;
        }

        return false;
    }
}
