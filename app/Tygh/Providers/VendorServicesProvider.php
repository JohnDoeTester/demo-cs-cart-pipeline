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

namespace Tygh\Providers;

use Tygh\Tygh;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Vendors\Invitations\Repository;
use Tygh\Vendors\Invitations\Sender;

/**
 * The provider class that registers the vendor invites repository in the Tygh::$app container.
 *
 * @package Tygh\Providers
 */
class VendorServicesProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['vendors.invitations.repository'] = function ($app) {
            return new Repository($app['db']);
        };

        $app['vendors.invitations.sender'] = function ($app) {
            return new Sender($app['db'], $app['vendors.invitations.repository'], $app['mailer']);
        };
    }

    /**
     * @return \Tygh\Vendors\Invitations\Repository
     */
    public static function getInvitationsRepository()
    {
        return Tygh::$app['vendors.invitations.repository'];
    }

    /**
     * @return \Tygh\Vendors\Invitations\Sender
     */
    public static function getInvitationsSender()
    {
        return Tygh::$app['vendors.invitations.sender'];
    }
}
