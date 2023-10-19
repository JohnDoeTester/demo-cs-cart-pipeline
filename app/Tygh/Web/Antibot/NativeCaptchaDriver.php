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

namespace Tygh\Web\Antibot;

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

/**
 * Class NativeCaptchaDriver implements native captcha provider.
 *
 * @package Tygh\Addons\Recaptcha
 */
class NativeCaptchaDriver implements IAntibotDriver
{
    const RESPONSE_PARAM_NAME = 'native_captcha_response';

    /**
     * @var \Gregwar\Captcha\CaptchaBuilder $builder
     */
    protected $builder;

    /**
     * @var int $width Image width
     */
    protected $width = 150;

    /**
     * @var int $height Image height
     */
    protected $height = 40;

    /**
     * @var int $length Phrase length
     */
    protected $length = 6;

    /**
     * @var int $color Image background color
     */
    protected $color = 245;

    /**
     * @var array $session Session
     */
    protected $session;

    /**
     * NativeCaptchaDriver constructor.
     */
    public function __construct($session)
    {
        $this->session = $session;
        $this->initCaptchaBuilder();

        if (!isset($this->session['native_captcha'])) {
            $this->renewCaptcha();
        }
    }

    /**
     * Initializes captcha builder.
     */
    protected function initCaptchaBuilder()
    {
        $this->builder = new CaptchaBuilder(
            null,
            new PhraseBuilder($this->length)
        );

        $this->builder->setBackgroundColor($this->color, $this->color, $this->color);

        $this->builder->build($this->width, $this->height);
    }

    /**
     * Generates captcha and stores it in the session.
     */
    protected function renewCaptcha()
    {
        $this->initCaptchaBuilder();

        $this->session['native_captcha'] = [
            'image'  => $this->builder->inline(),
            'answer' => fn_strtolower($this->builder->getPhrase()),
        ];
    }

    /**
     * @inheritdoc
     */
    public function isSetUp()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function validateHttpRequest(array $http_request_data)
    {
        $result = false;

        if (isset($http_request_data[static::RESPONSE_PARAM_NAME])) {
            $user_response = fn_strtolower($http_request_data[static::RESPONSE_PARAM_NAME]);
            $stored_answer = $this->session['native_captcha']['answer'];
            $this->builder->setPhrase($stored_answer);
            $result = $this->builder->testPhrase($user_response);
            $this->renewCaptcha();
        }

        return $result;
    }
}
