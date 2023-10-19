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

namespace Tygh\Addons\VendorPrivileges;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Class ServiceProvider is intended to register services and components of the "Vendor Privileges" add-on to the application
 * container.
 *
 * @package Tygh\Addons\ProductVariations
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['addons.vendor_privileges.privileges'] = function(Container $app) {
            return self::createPrivileges();
        };
    }

    /**
     * @return \Tygh\Addons\VendorPrivileges\Privileges
     */
    public static function createPrivileges()
    {
        $vendor_schema = fn_get_permissions_schema('vendor');
        $admin_schema = fn_get_permissions_schema('admin');

        return new Privileges($admin_schema, $vendor_schema);
    }
}
