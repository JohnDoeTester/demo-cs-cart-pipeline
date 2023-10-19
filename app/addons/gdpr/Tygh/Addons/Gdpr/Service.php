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
use Tygh\Mailer\Mailer;
use Tygh\Database\Connection;
use Tygh\Gdpr\SchemaManager;

/**
 * Provides methods to fetch data from settings
 *
 * @package Tygh\Addons\Gdpr
 */
class Service
{
    const USER_ACTION_DATA_REQUEST = 'personal_data_request';
    const USER_ACTION_ANONYMIZATION_REQUEST = 'anonymization_request';

    /** @var SchemaManager $schema_manager Schema manager */
    protected $schema_manager;

    /** @var Connection $db Database connection */
    protected $db;

    /** @var array $gdpr_settings Setting from schema */
    protected $gdpr_settings;

    /** @var array $auth User authorization data */
    protected $auth;

    /** @var array $addon_settings Saved add-on settings */
    protected $addon_settings;

    /** @var Mailer $mailer Mail sender */
    protected $mailer;

    public function __construct(
        SchemaManager $schema_manager,
        Connection $db,
        array $addon_settings,
        array $auth,
        Mailer $mailer
    ) {
        $this->schema_manager = $schema_manager;
        $this->addon_settings = $addon_settings;
        $this->auth = $auth;
        $this->db = $db;
        $this->mailer = $mailer;
    }

    /**
     * Checks whether element should be displayed
     *
     * @param string $agreement_type Agreement type
     *
     * @return bool
     */
    public function isNeeded($agreement_type)
    {
        $is_enabled = isset($this->addon_settings['gdpr']['gdpr_settings_data'][$agreement_type]['enable'])
            && $this->addon_settings['gdpr']['gdpr_settings_data'][$agreement_type]['enable'] == 'Y';

        return $is_enabled && !$this->hasUserAgreement($agreement_type);
    }

    /**
     * Checks if the user has already agreed with the consent by its type
     *
     * @param string $agreement_type Type of agreement
     * @param array  $auth           User authorization data
     * @param bool   $check_in_cache Check in cache flag
     *
     * @return bool
     */
    public function hasUserAgreement($agreement_type, $auth = array(), $check_in_cache = true)
    {
        $cached_agreement = $this->getCachedUserAgreementStatus($agreement_type);

        if (isset($cached_agreement) && $check_in_cache) {
            return $cached_agreement;
        }

        $user_id = isset($auth['user_id']) ? $auth['user_id'] : $this->auth['user_id'];

        if (empty($user_id)) {
            return false;
        }

        $condition = $this->db->quote('user_id = ?i', $user_id);
        $email = $this->db->getField('SELECT email FROM ?:users WHERE ?p', $condition);

        if ($email) {
            $condition = $this->db->quote('(?p OR email = ?s)', $condition, $email);
        }

        $agreement = $this->db->getField(
            'SELECT agreement_id FROM ?:gdpr_user_agreements WHERE type = ?s AND ?p',
            $agreement_type,
            $condition
        );

        $agreed = !empty($agreement);

        if ($check_in_cache) {
            $this->storeUserAgreementStatus($agreement_type, $agreed);
        }

        return $agreed;
    }

    /**
     * Fetches full agreement text for a certain agreement_type of agreement
     *
     * @param string $agreement_type Agreement type
     *
     * @return mixed|string
     */
    public function getFullAgreement($agreement_type)
    {
        $agreement = '';
        $settings = $this->getGdprSettings();

        if (isset($settings[$agreement_type]['full_agreement_langvar'])) {
            $agreement = __($settings[$agreement_type]['full_agreement_langvar'], $this->getLangvarPlaceholders($agreement_type));
        }

        return $agreement;
    }

    /**
     * Fetches short agreement text for a certain agreement_type of agreement
     *
     * @param string $agreement_type Agreement type
     *
     * @return string
     */
    public function getShortAgreement($agreement_type)
    {
        $agreement = '';
        $settings = $this->getGdprSettings();

        if (isset($settings[$agreement_type]['short_agreement_langvar'])) {
            $agreement = __($settings[$agreement_type]['short_agreement_langvar']);
        }

        return $agreement;
    }

    /**
     * Saves accepted user agreement to the agreements log
     *
     * @param array  $params         Parameters
     * @param string $agreement_type Type of agreement
     *
     * @return mixed
     */
    public function saveAcceptedAgreement($params, $agreement_type)
    {
        $agreement_data = array(
            'email'     => isset($params['email']) ? $params['email'] : '',
            'user_id'   => isset($params['user_id']) ? $params['user_id'] : '',
            'type'      => $agreement_type,
            'timestamp' => isset($params['timestamp']) ? $params['timestamp'] : TIME,
            'agreement' => $this->getFullAgreement($agreement_type),
        );

        $agreement_id = $this->db->query('INSERT INTO ?:gdpr_user_agreements ?e', $agreement_data);
        $this->storeUserAgreementStatus($agreement_type, (bool) $agreement_id);

        return $agreement_id;
    }

    /**
     * Checks if the user can be anonymized
     *
     * @param int $user_id User id
     *
     * @return bool
     */
    public function isUserAnonymizable($user_id)
    {
        $anonymizable = false;

        if (!empty($user_id)) {
            $anonymized = $this->isUserAnonymized($user_id);

            if (!$anonymized) {
                $user_info = fn_get_user_short_info($user_id);
                $anonymizable = isset($user_info['user_type'])
                    && $user_info['user_type'] === 'C';
            }
        }

        return $anonymizable;
    }

    /**
     * Marks user as anonymized
     *
     * @param int $user_id User identifier
     *
     * @return mixed
     */
    public function markUserAsAnonymized($user_id)
    {
        $result = false;

        if (!empty($user_id)) {
            $result = $this->db->replaceInto('gdpr_user_data', array(
                'user_id' => (int) $user_id,
                'anonymized' => 'Y',
            ));
        }

        return $result;
    }

    /**
     * Checks whether the user already anonymized
     *
     * @param int $user_id User identifier
     *
     * @return bool
     */
    public function isUserAnonymized($user_id)
    {
        $anonymized = 'N';

        if (!empty($user_id)) {
            $anonymized = $this->db->getField('SELECT anonymized FROM ?:gdpr_user_data WHERE user_id = ?i', $user_id);
        }

        return $anonymized === 'Y';
    }

    /**
     * Notifies staff members of user anonymization request
     *
     * @param int    $user_id   User identifier
     * @param string $comment   User comment
     *
     * @return bool
     */
    public function notifyStaffOfAnonymizationRequest($user_id, $comment)
    {
        $user_info = fn_get_user_short_info($user_id);
        return fn_send_anonymization_request_email($user_info, $comment);
    }

    /**
     * Notifies staff members of user personal data request
     *
     * @param int    $user_id   User identifier
     * @param string $comment   User comment
     *
     * @return bool
     */
    public function notifyStaffOfDataRequest($user_id, $comment)
    {
        return $this->notifyStaffOfUserRequest($user_id, $comment, self::USER_ACTION_DATA_REQUEST);
    }

    /**
     * Notifies staff members of user request
     *
     * @param int    $user_id   User identifier
     * @param string $comment   User comment
     * @param string $action    User action request
     *
     * @return bool
     */
    protected function notifyStaffOfUserRequest($user_id, $comment, $action)
    {

        $user_info = fn_get_user_short_info($user_id);
        $sent = $this->sendUserRequestEmail(
            $user_info,
            $comment,
            $action
        );

        if ($sent) {
            $this->saveAcceptedAgreement(array('user_id' => $user_id), $action);
            return true;
        }

        return false;
    }

    /**
     * Sends a user request to the "users department" by email
     *
     * @param array  $user_info User data
     * @param string $comment   User comment
     * @param string $action    User action request
     *
     * @return bool
     */
    protected function sendUserRequestEmail($user_info, $comment, $action)
    {
        if (empty($user_info['email'])) {
            return false;
        }

        $vars = array(
            '[user_profile_url]' => fn_url(sprintf('profiles.update?user_id=%s', $user_info['user_id']), 'A'),
            '[customer_email]'   => $user_info['email'],
            '[comment]'          => $comment,
            '[user]'             => sprintf(
                'Customer %s %s',
                isset($user_info['firstname']) ? $user_info['firstname'] : '',
                isset($user_info['lastname']) ? $user_info['lastname'] : ''
            ),
        );

        $message = array(
            'body'     => __("gdpr.email_body_{$action}", $vars),
            'subj'     => __("gdpr.email_subject_{$action}", $vars),
            'reply_to' => $user_info['email'],
            'to'       => 'default_company_users_department',
            'from'     => 'default_company_users_department',
        );

        return $this->mailer->send($message, 'A', CART_LANGUAGE);
    }

    /**
     * Stores current user agreement status of a particular type to the session
     *
     * @param string  $agreement_type   Type of agreement
     * @param boolean $agreed Agreement status
     *
     * @return $this
     */
    protected function storeUserAgreementStatus($agreement_type, $agreed)
    {
        Tygh::$app['session']['gdpr'][$agreement_type] = $agreed;
        return $this;
    }

    /**
     * Fetches current user agreement status of a particular type from the session
     *
     * @param string $agreement_type Type of agreement
     *
     * @return boolean|null
     */
    protected function getCachedUserAgreementStatus($agreement_type)
    {
        if (isset(Tygh::$app['session']['gdpr'][$agreement_type])) {
            return Tygh::$app['session']['gdpr'][$agreement_type];
        }

        return null;
    }

    /**
     * Fetches language variables placeholders for a certain agreement type
     *
     * @param string $agreement_type Agreement type
     * @param string $email          Company email placeholder value
     * @param string $company        Company name placeholder value
     *
     * @return array
     */
    protected function getLangvarPlaceholders($agreement_type, $email = '', $company = '')
    {
        $settings = $this->getGdprSettings();
        $placeholders = array(
            '[email]'   => $email ?: Registry::get('settings.Company.company_users_department'),
            '[company]' => $company ?: Registry::get('settings.Company.company_name'),
        );

        if (isset($settings[$agreement_type]['get_langvar_placeholders']) && is_callable($settings[$agreement_type]['get_langvar_placeholders'])) {
            $placeholders = array_replace(
                $placeholders,
                (array) call_user_func($settings[$agreement_type]['get_langvar_placeholders'])
            );
        }

        return $placeholders;
    }

    /**
     * Fetches gdpr settings schema
     *
     * @return array
     */
    protected function getGdprSettings()
    {
        if (!isset($this->gdpr_settings)) {
            $this->gdpr_settings = $this->schema_manager->getSchema('settings');
        }

        return $this->gdpr_settings;
    }
}
