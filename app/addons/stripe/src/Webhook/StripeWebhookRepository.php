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

namespace Tygh\Addons\Stripe\Webhook;

use Stripe\WebhookEndpoint;
use Tygh\Database\Connection;

final class StripeWebhookRepository
{
    const WEBHOOK_SECRET_STORAGE_KEY = 'webhook_secret_key';
    const WEBHOOK_ID_STORAGE_KEY = 'webhook_id';

    /** @var Connection $db */
    private $db;

    /**
     * StripeWebhookRepository constructor.
     *
     * @param Connection $db Database connection instance
     *
     * @return void
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @param int             $payment_id Payment ID
     * @param WebhookEndpoint $endpoint   Endpoint
     *
     * @return void
     */
    public function save($payment_id, WebhookEndpoint $endpoint)
    {
        if (!$payment_id) {
            return;
        }

        $processor_params = $this->getProcessorParams($payment_id);

        $processor_params[self::WEBHOOK_ID_STORAGE_KEY] = $endpoint->id;
        $processor_params[self::WEBHOOK_SECRET_STORAGE_KEY] = $endpoint->secret;

        $processor_params = serialize($processor_params);
        $this->db->query('UPDATE ?:payments SET processor_params = ?s WHERE payment_id = ?i', $processor_params, $payment_id);
    }

    /**
     * @param int $payment_id Payment ID
     *
     * @return string
     *
     * @throws \Tygh\Exceptions\DatabaseException Database exception.
     */
    public function findSecretKeyByPaymentId($payment_id)
    {
        if (!$payment_id) {
            return '';
        }

        $processor_params = $this->getProcessorParams($payment_id);

        return !empty($processor_params[self::WEBHOOK_SECRET_STORAGE_KEY])
            ? $processor_params[self::WEBHOOK_SECRET_STORAGE_KEY]
            : '';
    }

    /**
     * @param int $payment_id Payment ID
     *
     * @return string
     *
     * @throws \Tygh\Exceptions\DatabaseException Database exception.
     */
    public function findIdByPaymentId($payment_id)
    {
        if (!$payment_id) {
            return '';
        }

        $processor_params = $this->getProcessorParams($payment_id);

        return !empty($processor_params[self::WEBHOOK_ID_STORAGE_KEY])
            ? $processor_params[self::WEBHOOK_ID_STORAGE_KEY]
            : '';
    }

    /**
     * @param int $payment_id Payment ID
     *
     * @return array<string, string>
     *
     * @throws \Tygh\Exceptions\DatabaseException Database exception.
     */
    public function getProcessorParams($payment_id)
    {
        $processor_params = $this->db->getField('SELECT processor_params FROM ?:payments WHERE payment_id = ?i', $payment_id);

        return unserialize($processor_params);
    }
}
