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

namespace Tygh\Addons\Stripe\HookHandlers;

use Tygh\Addons\Stripe\ServiceProvider;
use Tygh\Application;

class DispatchHookHandler
{
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * The "dispatch_assign_template" hook handler.
     *
     * Actions performed:
     *  - Adds information about availabe payment buttons into view
     *
     * @see \fn_dispatch()
     */
    public function onDispatchAssignTemplate($controller, $mode, $area, $controllers_cascade)
    {
        if (AREA !== 'C') {
            return;
        }

        /** @var \Tygh\SmartyEngine\Core $view */
        $view = $this->application['view'];

        $view->assign('stripe_payment_buttons_icons', ServiceProvider::getPaymentMethodIcons());
    }
}
