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

namespace Tygh\Backend\EmailSync;

use Tygh\Common\OperationResult;

abstract class ABackend
{
    /*
     * Service support flags:
     * manual_sync - if service unsubscriptions should be synced manually
     * import - if service supports subscribers import
     */
    protected $support = array(
        'manual_sync' => false,
        'import' => false
    );

    /**
     * Subscribes email
     *
     * @param array<string, string> $data Subscriber data
     *
     * @return bool
     */
    public function subscribe(array $data)
    {
        return true;
    }

    /**
     * Unsubscribes email
     *
     * @param string $email Email
     *
     * @return bool
     */
    public function unsubscribe($email)
    {
        return true;
    }

    /**
     * Adds callback url when user subscribes using service form (webhook)
     *
     * @param string $list_id List ID
     * @param string $url     Callback url
     *
     * @return bool
     */
    public function subscribeCallback($list_id, $url)
    {
        return true;
    }

    /**
     * Adds callback url when user unsubscribes using service form (webhook)
     *
     * @param string $list_id List ID
     * @param string $url     Callback url
     *
     * @return bool
     */
    public function unsubscribeCallback($list_id, $url)
    {
        return true;
    }

    /**
     * Updates url webhook
     *
     * @param string $list_id List identifier
     * @param string $url     Url
     *
     * @return \Tygh\Common\OperationResult
     */
    public function updateWebhookUrl($list_id, $url)
    {
        return new OperationResult(false);
    }

    /**
     * Processes webhook data
     *
     * @param array<array-key, string|array> $data Web hook data
     *
     * @psalm-param array{
     *     type: string,
     *     data: array{email: string, new_email: string, old_email: string, ip_opt: string, merges: array{FNAME: string}}
     * } $data Web hook data
     *
     * @return bool|array<string, string|int>
     */
    public function processWebHook(array $data)
    {
        return true;
    }

    /**
     * Batch email subscription
     *
     * @param array<array-key, array|string|int> $data Emails (with name, timestamp, etc)
     *
     * @return bool
     */
    public function batchSubscribe(array $data)
    {
        return true;
    }

    /**
     * Batch email unsubscription
     *
     * @param array<string> $emails Emails
     *
     * @return bool
     */
    public function batchUnsubscribe(array $emails)
    {
        return true;
    }

    /**
     * Syncs unsubscribed users
     */
    public function sync()
    {
        return false;
    }    

    /**
     * Gets subscription lists
     *
     * @return bool|array<string, string>
     */
    public function getLists()
    {
        return [];
    }

    /**
     * Gets service options
     * @param string $option option name
     * @return mixed boolean true if option is supported, false - if not. Array with all options if $option parameter is ommited
     */
    public function supports($option = '')
    {
        if (!empty($option)) {
            return !empty($this->support[$option]);    
        }

        return $this->support;
    }
}
