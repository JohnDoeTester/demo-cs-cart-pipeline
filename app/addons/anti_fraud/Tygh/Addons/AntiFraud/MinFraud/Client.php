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

namespace Tygh\Addons\AntiFraud\MinFraud;


use Tygh\Http;

/**
 * This class provides a client API for accessing MaxMind minFraud Insights.
 *
 * @package Tygh\Addons\AntiFraud\MinFraud
 */
class Client
{
    const INSIGHTS_URI = 'https://minfraud.maxmind.com/minfraud/v2.0/insights';

    private $user_id;
    private $license_key;

    /**
     * Client constructor.
     *
     * @param int       $user_id        MaxMind user ID
     * @param string    $license_key    MaxMind license key
     */
    public function __construct($user_id, $license_key)
    {
        $this->user_id = $user_id;
        $this->license_key = $license_key;
    }

    /**
     * This method performs a minFraud Insights lookup using the request data
     *
     * @param Request $request
     *
     * @return Response minFraud Insights response object
     */
    public function send(Request $request)
    {
        $raw_response = Http::post(self::INSIGHTS_URI, $request->json(), array(
            'basic_auth' => array($this->user_id, $this->license_key),
            'headers' => array(
                "Content-Type: application/vnd.maxmind.com-minfraud-insights+json; charset=UTF-8; version=2.0",
                "Accept: application/json",
                "Accept-Charset: UTF-8"
            )
        ));

        return new Response($raw_response);
    }
}