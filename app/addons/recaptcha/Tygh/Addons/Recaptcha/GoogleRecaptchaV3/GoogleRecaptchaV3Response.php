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

namespace Tygh\Addons\Recaptcha\GoogleRecaptchaV3;

use ReCaptcha\Response;
use Tygh\Registry;

class GoogleRecaptchaV3Response extends Response
{
    private $score;
    private $data;

    /**
     * Constructor.
     *
     * @param bool  $success
     * @param array $errorCodes
     * @param int   $score
     */
    public function __construct($success, array $errorCodes = [], $score = 0)
    {
        parent::__construct($success, $errorCodes);
        $this->score = $score;
        $this->data = [];
    }

    /**
     * Returns score from the Google response
     *
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Builds the response from the expected JSON returned by the service.
     *
     * @param string $json
     *
     * @return \Tygh\Addons\Recaptcha\GoogleRecaptchaV3\GoogleRecaptchaV3Response
     */
    public static function fromJson($json)
    {
        $responseData = json_decode($json, true);

        if (!$responseData) {
            return new GoogleRecaptchaV3Response(false, ['invalid-json']);
        }

        if (
            isset($responseData['success'])
            && isset($responseData['score'])
            && $responseData['success'] == true
        ) {
            return new GoogleRecaptchaV3Response(true, [], $responseData['score']);
        }

        if (isset($responseData['error-codes']) && is_array($responseData['error-codes'])) {
            return new GoogleRecaptchaV3Response(false, $responseData['error-codes']);
        }

        return new GoogleRecaptchaV3Response(false);
    }
}
