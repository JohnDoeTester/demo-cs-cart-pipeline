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

namespace Tygh\Addons\Recaptcha;

use Tygh\Addons\Recaptcha\GoogleRecaptchaV3\GoogleRecaptchaV3;
use Tygh\Addons\Recaptcha\RequestMethod\Post;
use Tygh\Enum\Addons\Recaptcha\RecaptchaTypes;
use Tygh\Enum\YesNo;
use Tygh\Web\Antibot\IAntibotDriver;
use ReCaptcha\ReCaptcha;
use Tygh\Web\Antibot\IErrorableAntibotDriver;
use Tygh\Web\Session;

/**
 * Class RecaptchaDriver implements integration with Google reCAPTCHA service.
 *
 * @package Tygh\Addons\Recaptcha
 */
class RecaptchaDriver implements IAntibotDriver, IErrorableAntibotDriver
{
    const RECAPTCHA_TOKEN_PARAM_NAME = 'g-recaptcha-response';
    const RECAPTCHA_V3_TOKEN_PARAM_NAME = 'g-recaptcha-v3-token';

    /**
     * @var array Recaptcha add-on settings
     */
    protected $settings;

    /**
     * @var Session Current session instance
     */
    protected $session;

    /**
     * @var array Array of scenarios and recaptcha types
     */
    protected $use_for_settings;

    /**
     * @var bool Settings flag, is settings set up or not
     */
    protected $is_settings_set_up;

    /**
     * RecaptchaDriver constructor.
     *
     * @param array   $addon_settings   Recaptcha add-on settings
     * @param Session $session          Current session instance
     * @param array   $use_for_settings Array of scenarios and recaptcha types
     */
    public function __construct(array $addon_settings, Session $session, array $use_for_settings)
    {
        $this->settings = $addon_settings;
        $this->session = $session;
        $this->use_for_settings = $use_for_settings;
    }

    /**
     * @inheritdoc
     */
    public function isSetUp()
    {
        if (empty($this->is_settings_set_up)) {
            $this->is_settings_set_up = $this->checkSettings();
        }

        return $this->is_settings_set_up;
    }

    /**
     * @inheritdoc
     */
    public function validateHttpRequest(array $http_request_data)
    {
        if (
            !isset($http_request_data[static::RECAPTCHA_TOKEN_PARAM_NAME])
            && !isset($http_request_data[static::RECAPTCHA_V3_TOKEN_PARAM_NAME])
        ) {
            return false;
        }

        $user_ip_address = fn_get_ip();
        $user_ip_address = $user_ip_address['host'];

        if (isset($http_request_data[static::RECAPTCHA_TOKEN_PARAM_NAME])) {
            $recaptcha_token = $http_request_data[static::RECAPTCHA_TOKEN_PARAM_NAME];

            $recaptcha = new ReCaptcha($this->settings['recaptcha_secret'], new Post());
            $response = $recaptcha->verify($recaptcha_token, $user_ip_address);

            return $response->isSuccess();
        }

        if (isset($http_request_data[static::RECAPTCHA_V3_TOKEN_PARAM_NAME])) {
            $recaptcha_token = $http_request_data[static::RECAPTCHA_V3_TOKEN_PARAM_NAME];
            $score = 0;

            if (isset($http_request_data['validate_token'])) {
                $recaptcha = new GoogleRecaptchaV3($this->settings['recaptcha_v3_secret'], new Post());

                $result = $recaptcha->verify($recaptcha_token, $user_ip_address);

                if (!$result) {
                    return false;
                }

                $score = $result->getScore();

                $this->session['recaptcha_v3'] = [
                    'token' => $recaptcha_token,
                    'score' => $score,
                ];
            }

            if (
                isset($this->session['recaptcha_v3']['token'])
                && isset($this->session['recaptcha_v3']['score'])
                && $recaptcha_token == $this->session['recaptcha_v3']['token']
            ) {
                $score = $this->session['recaptcha_v3']['score'];
            }
            if ($score >= $this->getSuccessScore()) {
                if (isset($this->settings['hide_after_validation']) && $this->settings['hide_after_validation'] == YesNo::YES) {
                    unset($this->session['recaptcha_v3']);
                }
                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getErrorMessage($scenario)
    {
        if ($this->use_for_settings[$scenario] == RecaptchaTypes::RECAPTCHA_TYPE_V3)
            $error = __('recaptcha.error_recaptcha_v3_failed');
        else
            $error = __('error_confirmation_code_invalid');

        return $error;
    }

    /**
     * Checks the settings was set up or not.
     *
     * @return bool
     */
    private function checkSettings()
    {
        $required_settings = [];
        foreach ($this->use_for_settings as $setting => $type) {
            $required_settings[$type]['settings'] = [];
        }

        if (isset($required_settings[RecaptchaTypes::RECAPTCHA_TYPE_V2])) {
            $required_settings[RecaptchaTypes::RECAPTCHA_TYPE_V2]['settings'] = [
                'recaptcha_site_key',
                'recaptcha_secret',
                'recaptcha_theme',
                'recaptcha_size',
                'recaptcha_type',
            ];
        }

        if (isset($required_settings[RecaptchaTypes::RECAPTCHA_TYPE_V3])) {
            $required_settings[RecaptchaTypes::RECAPTCHA_TYPE_V3]['settings'] = [
                'recaptcha_v3_site_key',
                'recaptcha_v3_secret',
                'recaptcha_v3_success_score',
            ];
        }

        if (empty($required_settings)) {
            return false;
        }

        foreach ($required_settings as $type => $type_settings) {
            foreach ($type_settings['settings'] as $setting) {
                if ($this->settings[$setting] === '') {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Gets a score, which will enable user to take an appropriate action
     *
     * @return float
     */
    private function getSuccessScore()
    {
        $score = (double) $this->settings['recaptcha_v3_success_score'];
        if ($score >= 1.0) {
            $score = 1.0;
        } elseif ($score <= 0.0) {
            $score = 0.0;
        }
        return $score;
    }
}
