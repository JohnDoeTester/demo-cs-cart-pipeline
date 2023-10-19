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

namespace Tygh\Addons\Recaptcha\RequestMethod;


use ReCaptcha\RequestMethod\Post as BasePost;
use ReCaptcha\RequestMethod\CurlPost;
use ReCaptcha\RequestParameters;

/**
 * Sends POST requests to the reCAPTCHA service
 */
class Post extends BasePost
{
    /**
     * @inheritdoc
     */
    public function submit(RequestParameters $params)
    {
        $result = parent::submit($params);

        if ($result === false && function_exists('curl_init')) {
            $curl_post = new CurlPost();
            $result = $curl_post->submit($params);
        }

        return $result;
    }
}