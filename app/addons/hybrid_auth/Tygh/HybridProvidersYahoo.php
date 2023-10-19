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

namespace Tygh;

use Exception;
use Hybrid_Logger;
use Hybrid_Providers_Yahoo;

class HybridProvidersYahoo extends Hybrid_Providers_Yahoo
{
    /**
     * @inheridoc
     */
    public function initialize()
    {
        parent::initialize();

        $this->api->api_base_url = 'https://api.login.yahoo.com/openid/v1/';
    }

    /**
     * @inheridoc
     *
     * @return \Hybrid_User_Profile
     */
    public function getUserProfile()
    {
        $this->setAuthorizationHeaders('basic');
        $this->refreshToken();
        $this->setAuthorizationHeaders('bearer');

        $response = $this->api->get('userinfo', [
            'format' => 'json',
        ]);

        if (empty($response)) {
            throw new Exception("User profile request failed! {$this->providerId} returned an invalid response: " . Hybrid_Logger::dumpData($response), 6);
        }

        $this->user->profile->identifier = isset($response->sub) ? $response->sub : '';
        $this->user->profile->firstName = isset($response->given_name) ? $response->given_name : '';
        $this->user->profile->lastName = isset($response->family_name) ? $response->family_name : '';
        $this->user->profile->displayName = isset($response->name) ? trim($response->name) : '';
        $this->user->profile->profileURL = isset($response->picture) ? $response->picture : '';
        $this->user->profile->gender = isset($response->gender) ? $response->gender : '';
        $this->user->profile->email = isset($response->email) ? $response->email : '';
        $this->user->profile->emailVerified = isset($response->emailVerified) ? $response->emailVerified : '';

        return $this->user->profile;
    }

    /**
     * Set correct Authorization headers.
     *
     * @param string $token_type Token type
     *   Specify token type.
     *
     * @return void
     */
    private function setAuthorizationHeaders($token_type)
    {
        switch ($token_type) {
            case 'basic':
                // The /get_token requires authorization header.
                $token = base64_encode("{$this->config['keys']['id']}:{$this->config['keys']['secret']}");
                $this->api->curl_header = [
                    "Authorization: Basic {$token}",
                    'Content-Type: application/x-www-form-urlencoded',
                ];
                break;

            case 'bearer':
                // Yahoo API requires the token to be passed as a Bearer within the authorization header.
                $this->api->curl_header = [
                    "Authorization: Bearer {$this->api->access_token}",
                ];
                break;
        }
    }
}
