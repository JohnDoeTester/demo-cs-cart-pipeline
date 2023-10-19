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

/** @var array $schema */

use Tygh\Addons\Gdpr\CookiesPolicyManager;
use Tygh\Registry;

$schema['gdpr'] = function () {
    return (bool) db_get_field(
        'SELECT COUNT(*) AS cnt FROM ?:gdpr_user_agreements WHERE timestamp >= ?i',
        strtotime('-30 days')
    );
};

$schema['cookie_consent_implicit'] = static function () {
    return Registry::get('addons.gdpr.gdpr_cookie_consent') === CookiesPolicyManager::COOKIE_POLICY_IMPLICIT;
};

$schema['cookie_consent_explicit'] = static function () {
    return Registry::get('addons.gdpr.gdpr_cookie_consent') === CookiesPolicyManager::COOKIE_POLICY_EXPLICIT;
};

return $schema;