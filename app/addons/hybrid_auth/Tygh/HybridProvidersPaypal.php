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

use Tygh\Enum\YesNo;

class HybridProvidersPaypal extends \Hybrid_Providers_Paypal
{
    public $useSafeUrls = true;

    public $scope = ['openid', 'profile', 'email', 'address', 'https://uri.paypal.com/services/paypalattributes'];

    /**
     * load the user profile from the IDp api client
     */
    public function getUserProfile()
    {
        $profile = parent::getUserProfile();

        //store for seamless checkout
        Tygh::$app['session']['paypal_token'] = $this->token('access_token');

        return $profile;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        if (isset($this->config['paypal_sandbox'])) {
            $this->config['sandbox'] = YesNo::toBool($this->config['paypal_sandbox']);
        }
        parent::initialize();
    }
}
