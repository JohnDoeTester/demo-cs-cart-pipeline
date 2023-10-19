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

use MailchimpAPI\MailchimpException;
use MailchimpAPI\Responses\MailchimpResponse;
use Tygh\Common\OperationResult;
use Tygh\Enum\NotificationSeverity;
use Tygh\Registry;
use Tygh\Exceptions\EmailSyncException;
use MailchimpAPI\Mailchimp as Mailchimp3;

/**
 * Class Mailchimp
 *
 * @package Tygh\Backend\EmailSync
 *
 * @psalm-suppress InvalidArgument
 */
class Mailchimp extends ABackend
{
    /** @var Mailchimp3 */
    private $mc;

    /** @var int */
    private $list_id = 0;

    /** @var array{manual_sync: bool, import:bool} */
    protected $support = [
        'manual_sync' => false,
        'import'      => true
    ];

    /**
     * MailchimpApi constructor.
     *
     * @throws EmailSyncException If any errors occurred.
     */
    public function __construct()
    {
        if (Registry::get('addons.email_marketing.em_mailchimp_api_key')) {
            try {
                $this->mc = new Mailchimp3(Registry::get('addons.email_marketing.em_mailchimp_api_key'));
            } catch (MailchimpException $e) {
                fn_set_notification(NotificationSeverity::ERROR, __('error'), $e->getMessage());
            }

            $this->list_id = Registry::get('addons.email_marketing.em_mailchimp_list');
        }

        if (!$this->mc) {
            throw new EmailSyncException();
        }
    }

    /**
     * Subscribes email
     *
     * @param array<string, string> $data Subscriber data
     *
     * @return bool
     */
    public function subscribe(array $data)
    {
        list($email, $_data) = $this->formSubscriber($data);

        $result = false;

        try {
            /** @psalm-suppress InvalidArgument */
            $this->mc->lists($this->list_id)->members()->post([
                'email_address' => $email,
                'email_type'    => 'html',
                'status'        => 'subscribed',
                'merge_fields'  => $_data,
            ]);

            $result = true;
        } catch (MailchimpException $e) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), $e->getMessage());
        }

        return $result;
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
        $result = false;

        try {
            /** @psalm-suppress InvalidArgument */
            $this->mc->lists($this->list_id)->members($email)->delete();

            $result = true;
        } catch (MailchimpException $e) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), $e->getMessage());
        }

        return $result;
    }

    /**
     * Subscribe callback
     *
     * @param string $list_id List identifier
     * @param string $url     Url
     *
     * @return bool
     */
    public function subscribeCallback($list_id, $url)
    {
        $error_message = '';

        try {
            /** @psalm-suppress InvalidArgument */
            $response = $this->mc->lists($list_id)->webhooks()->post([
                'url' => $url,
                'events' => [
                    'subscribe'   => true,
                    'unsubscribe' => true,
                    'profile'     => true,
                    'cleaned'     => true,
                    'upemail'     => true,
                    'campaign'    => true,
                ]
            ]);

            if ($response->wasFailure()) {
                $error_message = $this->getErrorMessage($response);
            }

            if (empty($error_message)) {
                /** @var array $response_data */
                $response_data = $response->deserialize(true);

                fn_email_marketing_mailchimp_update_webhook($response_data['id'], $list_id);
            }
        } catch (MailchimpException $e) {
            $error_message = $e->getMessage();
        }

        if (!empty($error_message)) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), $error_message);
            return false;
        }

        return true;
    }

    /**
     * Unsubscribe callback
     *
     * @param string $list_id List identifier
     * @param string $url     Url
     *
     * @return bool
     */
    public function unsubscribeCallback($list_id, $url)
    {
        $error_message = '';

        $webhook_id = db_get_field('SELECT webhook_id FROM ?:em_mailchimp_webhooks WHERE list_id = ?s', $list_id);

        if (empty($webhook_id)) {
            $webhook_id = $this->getWebhookId($list_id, $url);
            if (empty($webhook_id)) {
                return true;
            }
        }

        try {
            /** @psalm-suppress InvalidArgument */
            $response = $this->mc->lists($list_id)->webhooks($webhook_id)->delete();

            if ($response->wasFailure()) {
                $error_message = $this->getErrorMessage($response);
            }
        } catch (MailchimpException $e) {
            $error_message = $e->getMessage();
        }

        if (!empty($error_message)) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), $error_message);
            return false;
        }

        db_query('DELETE FROM ?:em_mailchimp_webhooks WHERE list_id = ?s', $list_id);

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
        $error_message = '';
        $result = new OperationResult(true);
        $webhook_id = db_get_field('SELECT webhook_id FROM ?:em_mailchimp_webhooks WHERE list_id = ?s', $list_id);

        try {
            /** @psalm-suppress InvalidArgument */
            $response = $this->mc->lists($list_id)->webhooks($webhook_id)->patch([
                'url' => $url,
            ]);

            if ($response->wasFailure()) {
                $error_message = $this->getErrorMessage($response);
                $result->addError('0', $error_message);
            }
        } catch (MailchimpException $e) {
            $error_message = $e->getMessage();
            $result->addError($e->getCode(), $error_message);
        }

        if (!empty($error_message)) {
            $result->setSuccess(false);
        }

        return $result;
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
     * @return array{
     *     action?: string,
     *     email?: string,
     *     ip_address?: string,
     *     name?: string,
     *     new_email?: string,
     *     timestamp?: int
     * }
     */
    public function processWebHook(array $data)
    {
        $result = [];

        if (!empty($data) && !empty($data['type'])) {
            if ($data['type'] === 'subscribe' || $data['type'] === 'profile') {
                /** @var array<array-key, string> $data['data']['merges'] */
                $result = [
                    'action'     => $data['type'] === 'subscribe' ? 'subscribe' : 'update',
                    'email'      => (string) $data['data']['email'],
                    'name'       => (string) $data['data']['merges']['FNAME'],
                    'ip_address' => (string) $data['data']['ip_opt'],
                    'timestamp'  => time()
                ];
            } elseif ($data['type'] === 'unsubscribe') {
                $result = [
                    'action' => 'unsubscribe',
                    'email'  => (string) $data['data']['email']
                ];
            } elseif ($data['type'] === 'upemail') {
                $result = [
                    'action'    => 'email_update',
                    'email'     => (string) $data['data']['old_email'],
                    'new_email' => (string) $data['data']['new_email']
                ];
            } elseif ($data['type'] === 'cleaned') {
                $result = [
                    'action' => 'unsubscribe',
                    'email'  => (string) $data['data']['email']
                ];
            }
        }

        return $result;
    }

    /**
     * Send batch of subscribers
     *
     * @param array<array-key, array|string|int> $data List of subscribers
     *
     * @return bool
     */
    public function batchSubscribe(array $data)
    {
        $error_message = '';

        $batch = [];

        foreach ($data as $subscriber) {
            list($email, $merge_vars) = $this->formSubscriber($subscriber);
            $batch[] = [
                'email_address' => $email,
                'status'        => 'subscribed',
                'merge_fields'  => $merge_vars
            ];
        }

        try {
            /** @psalm-suppress InvalidArgument */
            $response = $this->mc->lists($this->list_id)->batchSubscribe($batch, true);

            if ($response->wasFailure()) {
                $error_message = $this->getErrorMessage($response);
            }
        } catch (MailchimpException $e) {
            $error_message = $e->getMessage();
        }

        if (!empty($error_message)) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), $error_message);
            return false;
        }

        return true;
    }

    /**
     * Send batch of unsubscribers
     *
     * @param array<string> $emails Array of emails
     *
     * @return bool
     */
    public function batchUnsubscribe(array $emails)
    {
        $batch = [];

        foreach ($emails as $email) {
            $batch[] = [
                'email_address' => $email,
                'status'        => 'unsubscribed',
            ];
        }
        
        try {
            /** @psalm-suppress InvalidArgument */
            $response = $this->mc->lists($this->list_id)->batchSubscribe($batch, true);

            if ($response->wasFailure()) {
                $error_message = $this->getErrorMessage($response);
            }
        } catch (MailchimpException $e) {
            $error_message = $e->getMessage();
        }

        if (!empty($error_message)) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), $error_message);
            return false;
        }

        return true;
    }

    /**
     * Gets lists
     *
     * @return bool|array<string, string>
     */
    public function getLists()
    {
        $result = $lists = [];

        if ($this->mc) {
            try {
                /** @var MailchimpResponse $response */
                $response = $this->mc->lists()->get();
            } catch (MailchimpException $e) {
                fn_set_notification(NotificationSeverity::ERROR, __('error'), $e->getMessage());

                return false;
            }
        }

        if (!empty($response)) {
            try {
                $response = $response->deserialize(true);
            } catch (MailchimpException $e) {
                fn_set_notification(NotificationSeverity::ERROR, __('error'), $e->getMessage());

                return false;
            }

            $lists = $response['lists'];
        }

        if (!empty($lists)) {
            foreach ($lists as $list) {
                $result[$list['id']] = $list['name'];
            }
        }

        return $result;
    }

    /**
     * @return array<array-key, array{email: string, name: string, timestamp: false|int}>
     */
    public function import()
    {
        $error_message = '';

        try {
            /** @psalm-suppress InvalidArgument */
            $data = $this->mc->lists($this->list_id)->members()->get();

            if ($data->wasFailure()) {
                $error_message = $this->getErrorMessage($data);
            }

            if (empty($error_message)) {
                $data = $data->deserialize(true);
            }
        } catch (MailchimpException $e) {
            $error_message = $e->getMessage();
        }

        $subscribers = [];

        do {
            if (empty($data)) {
                continue;
            }

            /** @var array{total_items: int, members: array} $data */
            foreach ($data['members'] as $member) {
                $subscribers[] = [
                    'email'     => (string) $member['email_address'],
                    'name'      => (string) $member['merge_fields']['FNAME'],
                    'timestamp' => strtotime($member['timestamp_opt'])
                ];
            }

            if (sizeof($subscribers) < $data['total_items']) {
                $params = [
                    'status' => 'subscribed',
                    'offset' => floor($data['total_items'] / sizeof($subscribers))
                ];

                try {
                    /** @psalm-suppress InvalidArgument */
                    $data = $this->mc->lists($this->list_id)->members()->get($params);
                } catch (MailchimpException $e) {
                    $error_message = $e->getMessage();
                }
            } else {
                $data = [];
            }
        } while (!empty($data));

        if (!empty($error_message)) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), $error_message);
        }

        return $subscribers;
    }

    /**
     * Form subscriber data
     *
     * @param array<string, string> $data Subscriber data
     *
     * @return array{
     *     string, array{
     *         FNAME: string|null,
     *         optin_ip: string,
     *         optin_time: string,
     *         mc_language: string,
     *     }
     * }
     */
    private function formSubscriber(array $data)
    {
        return [
            $data['email'],
            [
                'FNAME'       => empty($data['name']) ? '' : $data['name'],
                'optin_ip'    => $data['ip_address'],
                'optin_time'  => strftime('%Y-%m-%d %H:%M:%S', (int) $data['timestamp']),
                'mc_language' => $data['lang_code']
            ]
        ];
    }

    /**
     * Get error message from response
     *
     * @param MailchimpResponse $response Response
     *
     * @return string
     */
    private function getErrorMessage(MailchimpResponse $response)
    {
        try {
            /** @var array $result */
            $result = $response->deserialize(true);
        } catch (MailchimpException $e) {
            return $e->getMessage();
        }

        $error = reset($result['errors']);

        return $error['message'];
    }

    /**
     * Gets webhook identifier from MailChimp by list identifier and url
     *
     * @param string $list_id List identifier
     * @param string $url     Url
     *
     * @return string|false
     */
    private function getWebhookId($list_id, $url)
    {
        /** @var MailchimpResponse $response */
        $response = null;

        $response_data = [];

        try {
            /** @psalm-suppress InvalidArgument */
            $response = $this->mc->lists($list_id)->webhooks()->get();

            if ($response->wasFailure()) {
                $error_message = $this->getErrorMessage($response);
            }
        } catch (MailchimpException $e) {
            $error_message = $e->getMessage();
        }

        if (!empty($error_message)) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), $error_message);
            return false;
        }


        try {
            /** @var array<string, array> $response_data */
            $response_data = $response->deserialize(true);
        } catch (MailchimpException $e) {
            $error_message = $e->getMessage();
        }

        if (!empty($error_message)) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), $error_message);
            return false;
        }

        foreach ($response_data['webhooks'] as $webhook_data) {
            if ($webhook_data['url'] !== $url) {
                continue;
            }

            return (string) $webhook_data['id'];
        }

        return false;
    }
}
