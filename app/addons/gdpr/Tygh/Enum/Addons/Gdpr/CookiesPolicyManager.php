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

namespace Tygh\Enum\Addons\Gdpr;

class CookiesPolicyManager
{
    /***
     * TODO: Currently these constants duplicate those from the Tygh\Addons\Gdpr\CookiesPolicyManager, so they need to be changed in both places. This duplication will need to be removed later.
     */
    const AGREEMENT_TYPE_COOKIES = 'cookies';
    const REQUEST_ACCEPTANCE_FLAG = 'cookies_accepted';

    const COOKIE_POLICY_IMPLICIT = 'implicit';
    const COOKIE_POLICY_EXPLICIT = 'explicit';
    const COOKIE_POLICY_NONE = 'none';
}
