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

use Exception;
use Stripe\WebhookEndpoint;
use Tygh\Addons\Stripe\Webhook\StripeWebhook;
use Tygh\Application;
use Tygh\Enum\Addons\Stripe\PaymentTypes;
use Tygh\Enum\YesNo;
use Stripe\Exception\InvalidRequestException;
use Tygh\Registry;
use Tygh\Tygh;

class PaymentsHookHandler
{
    /**
     * @var Application $application Application
     */
    protected $application;

    /**
     * PaymentsHookHandler constructor.
     *
     * @param Application $application Application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * The "update_payment_post" hook handler
     *
     * @param array<string, string> $payment_data     Payment data
     * @param int                   $payment_id       Payment ID
     * @param string                $lang_code        Lang code
     * @param array<string>         $certificate_file Certificate files
     * @param string                $certificates_dir Certificates directory
     * @param array<string, string> $processor_params Processor params
     *
     * @return void
     *
     * @throws \Stripe\Exception\ApiErrorException Stripe exception.
     */
    public function onUpdatePaymentPost(
        array $payment_data,
        $payment_id,
        $lang_code,
        array $certificate_file,
        $certificates_dir,
        array $processor_params
    ) {
        if (
            empty($processor_params['is_stripe'])
            || YesNo::isFalse($processor_params['is_stripe'])
            || empty($processor_params['payment_type'])
            || !in_array($processor_params['payment_type'], [
                PaymentTypes::STRIPE_CHECKOUT,
                PaymentTypes::APPLE_PAY,
                PaymentTypes::GOOGLE_PAY
            ])
        ) {
            return;
        }

        /** @var \Tygh\Addons\Stripe\Webhook\StripeWebhookRepository $webhook_repository */
        $webhook_repository = Tygh::$app['addons.stripe.webhook_repository'];

        try {
            $webhook_id = $webhook_repository->findIdByPaymentId($payment_id);
            StripeWebhook::setConfig($processor_params['secret_key']);

            if ($webhook_id) { // webhook existing checking
                StripeWebhook::retrieve($webhook_id);
            } else { // tries to register
                $endpoint = StripeWebhook::register($payment_id);
                $webhook_repository->save($payment_id, $endpoint);
            }
        } catch (InvalidRequestException $e) {
            if ((int) $e->getHttpStatus() === 404) { // webhook does not exist, so we try to register
                $endpoint = StripeWebhook::register($payment_id);
                $webhook_repository->save($payment_id, $endpoint);
            }
        } catch (Exception $e) {
            fn_log_event('general', 'runtime', [
                'message' => __('stripe.webhook_register_error', [
                    '[payment_id]' => $payment_id,
                    '[error]'      => $e->getMessage(),
                ]),
            ]);
        }
    }

    /**
     * The "delete_payment_pre" hook handler
     *
     * Actions performed:
     *  - Gets and saves the webhook endpoint in the Registry.
     *
     * @param int $payment_id Payment id to be deleted
     *
     * @return void
     *
     * @see fn_delete_payment()
     */
    public function onDeletePaymentPre($payment_id)
    {
        /** @var \Tygh\Addons\Stripe\Webhook\StripeWebhookRepository $webhook_repository */
        $webhook_repository = Tygh::$app['addons.stripe.webhook_repository'];
        $processor_params = $webhook_repository->getProcessorParams($payment_id);

        try {
            $webhook_id = $webhook_repository->findIdByPaymentId($payment_id);
            StripeWebhook::setConfig($processor_params['secret_key']);

            if ($webhook_id) {
                $endpoint = StripeWebhook::retrieve($webhook_id);
                Registry::set('runtime.stripe_webhook_endpoint', $endpoint);
            }
        } catch (Exception $e) {
        }
    }

    /**
     * The "delete_payment_post" hook handler
     *
     * Actions performed:
     *  - Gets a webhook endpoint and sends a request to delete it.
     *
     * @param int  $payment_id Payment id to be deleted
     * @param bool $result     True if payment was successfully deleted, false otherwise
     *
     * @return void
     *
     * @see fn_delete_payment()
     */
    public function onDeletePaymentPost($payment_id, $result)
    {
        /** @var WebhookEndpoint|null $endpoint */
        $endpoint = Registry::get('runtime.stripe_webhook_endpoint');

        if ($result && $endpoint instanceof WebhookEndpoint) {
            try {
                $endpoint->delete();
            } catch (Exception $e) {
            }
        }

        Registry::del('runtime.stripe_webhook_endpoint');
    }
}
