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

namespace Tygh\Addons\StripeConnect\Webhook\Handlers;

use Tygh\Addons\StripeConnect\ServiceProvider;
use Tygh\Addons\StripeConnect\Webhook\Handler;
use Stripe\Event;
use Tygh\Addons\StripeConnect\Logger;

class AccountApplicationDeauthorized implements Handler
{
    /**
     * Handles the account.application.deauthorized event
     *
     * @param Event $event Stripe event
     *
     * @return void
     */
    public function handle(Event $event)
    {
        $vendor_account = $event->account;
        $company_id = fn_stripe_connect_get_company_id_by_account($vendor_account);

        if (!$company_id) {
            return;
        }

        $account_helper = ServiceProvider::getAccountHelper();
        $account_helper->disconnectAccount($company_id);

        $company_name = fn_get_company_name($company_id);

        fn_execute_as_company(static function () use ($company_name, $company_id) {
            Logger::log(Logger::ACTION_INFO, __('stripe_connect.account_was_deauthorized', [
                '[company]' => $company_name,
                '[company_id]' => $company_id
            ]));
        }, $company_id);
    }
}
