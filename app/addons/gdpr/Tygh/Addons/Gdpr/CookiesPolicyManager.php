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

namespace Tygh\Addons\Gdpr;

use Tygh;
use Tygh\Registry;
use Tygh\Embedded;

/**
 * Provides methods to handle cookies policy
 *
 * @package Tygh\Addons\Gdpr
 */
class CookiesPolicyManager
{
    /***
     * @TODO: Currently these constants are duplicated by those from the \Tygh\Enum\Addons\Gdpr\CookiesPolicyManager, so they need to be changed in both places. This duplication will need to be removed later.
     */
    const AGREEMENT_TYPE_COOKIES = 'cookies';
    const REQUEST_ACCEPTANCE_FLAG = 'cookies_accepted';

    const COOKIE_POLICY_IMPLICIT = 'implicit';
    const COOKIE_POLICY_EXPLICIT = 'explicit';
    const COOKIE_POLICY_NONE = 'none';

    /** @var $service Service GDPR service */
    protected $service;

    /** @var $settings array GDPR settings */
    protected $settings;

    /**
     * CookiesPolicyManager constructor.
     *
     * @param Service $service  GDPR service
     * @param array   $settings Add-on settings
     */
    public function __construct(Service $service, array $settings)
    {
        $this->service = $service;
        $this->settings = $settings;
    }

    /**
     * Generates a config for Klaro
     *
     * @return array Klaro config
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getKlaroConfig()
    {
        $config = fn_get_schema('gdpr', 'klaro_config');
        $config['services'] = array_values($config['services']);

        if ($this->settings['general']['gdpr_cookie_consent'] === self::COOKIE_POLICY_EXPLICIT) {
            $config['hideLearnMore'] = false;
            $config['optOut'] = false;
        }
        return $config;
    }

    /**
     * Tries to unset all cookies that would be send in request or came from request
     *
     * @return bool
     */
    public function unsetAllCookies()
    {
        if (headers_sent()) {
            return false;
        }

        return header_remove('Set-Cookie');
    }

    /**
     * Saves user cookies agreement
     *
     * @param int $user_id User identifier
     *
     * @return mixed
     */
    public function saveAgreement($user_id = 0)
    {
        if ($user_id) {
            $this->service->saveAcceptedAgreement(array('user_id' => $user_id), self::AGREEMENT_TYPE_COOKIES);
        }

        return $this->storeAgreement();
    }

    /**
     * Stores user cookies agreement into temporary storage
     *
     * @return mixed
     */
    public function storeAgreement()
    {
        Tygh::$app['session']['gdpr'][self::AGREEMENT_TYPE_COOKIES] = true;
        return fn_set_cookie('has_cookie_consent', 'Y', COOKIE_ALIVE_TIME);
    }

    /**
     * Checks if user has accepted cookies agreement
     *
     * @param array $auth User authorization data
     *
     * @return bool
     */
    public function hasUserAgreement($auth)
    {
        return $this->hasStoredUserAgreement()
            || $this->service->hasUserAgreement(self::AGREEMENT_TYPE_COOKIES, $auth);
    }

    /**
     * Checks if user has stored cookies agreement in temporary storage
     *
     * @return bool
     */
    public function hasStoredUserAgreement()
    {
        $has_cookie_consent = fn_get_cookie('has_cookie_consent');
        return $has_cookie_consent === 'Y'
            || isset(Tygh::$app['session']['gdpr'][self::AGREEMENT_TYPE_COOKIES]) && Tygh::$app['session']['gdpr'][self::AGREEMENT_TYPE_COOKIES];
    }

    /**
     * Appends required parameter to provided or current url
     *
     * @param string $base Base url
     *
     * @return string
     */
    protected function getAcceptUrl($base = '')
    {
        $base = $base ?: Registry::get('config.current_url');
        return fn_link_attach($base, sprintf('%s=Y', self::REQUEST_ACCEPTANCE_FLAG));
    }

    /**
     * Checks if store runs in the widget mode
     *
     * @param array $params Additional data
     *
     * @return bool
     */
    protected function isWidgetMode($params)
    {
        return Embedded::isEnabled()
            || defined('AJAX_REQUEST') && !empty($params['init_context']) && isset($params['callback']) && $params['callback'] === 'TYGH_LOADER.callback'; // widget on the same domain is not being detected as embedded mode
    }
}
