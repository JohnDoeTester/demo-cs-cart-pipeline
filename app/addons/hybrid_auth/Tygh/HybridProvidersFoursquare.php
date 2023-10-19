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

class HybridProvidersFoursquare extends \Hybrid_Providers_Foursquare
{
    private static $apiVersion = array('v' => '20120610');
    private static $defPhotoSize = '100x100';

    /**
     * load the user profile from the IDp api client
     */
    public function getUserProfile()
    {
        $data = $this->api->api('users/self', 'GET', self::$apiVersion);

        if (!isset($data->response->user->id)) {
            throw new Exception('User profile request failed! ' . $this->providerId . ' returned an invalid response:' . Hybrid_Logger::dumpData( $data ), 6);
        }

        $data = $data->response->user;

        $this->user->profile->identifier = $data->id;
        $this->user->profile->firstName = $data->firstName;
        $this->user->profile->lastName = empty($data->lastName) ? '' : $data->lastName;
        $this->user->profile->displayName = $this->buildDisplayName($this->user->profile->firstName, $this->user->profile->lastName);
        $this->user->profile->photoURL = $this->buildPhotoURL($data->photo->prefix, $data->photo->suffix);
        $this->user->profile->profileURL = 'https://www.foursquare.com/user/' . $data->id;
        $this->user->profile->gender = $data->gender;
        $this->user->profile->city = $data->homeCity;
        $this->user->profile->email = $data->contact->email;
        $this->user->profile->emailVerified = $data->contact->email;

        return $this->user->profile;
    }

    /**
     * Builds the user name
     *
     * @param string $firstName The value of the first name
     * @param string $lastName  The value of the last name
     *
     * @return string of the user name
     */
    private function buildDisplayName($firstName, $lastName)
    {
        return trim($firstName . ' ' . $lastName);
    }

    /**
     * Builds the photo url
     *
     * @param string $prefix The value of the start photo url
     * @param string $suffix The value of the finish photo url
     *
     * @return string of the photo url
     */
    private function buildPhotoURL($prefix, $suffix)
    {
        if (isset($prefix) && isset($suffix)) {
            return $prefix . ((isset($this->config['params']['photo_size'])) ? ($this->config['params']['photo_size']) : (self::$defPhotoSize)) . $suffix;
        }

        return '';
    }
}