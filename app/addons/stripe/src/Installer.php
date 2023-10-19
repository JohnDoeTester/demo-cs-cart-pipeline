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

namespace Tygh\Addons\Stripe;

use Tygh\Addons\InstallerWithDemoInterface;
use Tygh\Addons\Stripe\Payments\Stripe;
use Tygh\Core\ApplicationInterface;
use Tygh\Enum\YesNo;
use Tygh\Registry;

class Installer implements InstallerWithDemoInterface
{
    /**
     * @var \Tygh\Core\ApplicationInterface
     */
    protected $application;

    public function __construct(ApplicationInterface $app)
    {
        $this->application = $app;
    }

    /**
     * @inheritDoc
     */
    public static function factory(ApplicationInterface $app)
    {
        return new self($app);
    }

    /**
     * @inheritDoc
     */
    public function onBeforeInstall()
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function onInstall()
    {
        if (!$this->getDb()->getField('SELECT type FROM ?:payment_processors WHERE processor_script = ?s',
            Stripe::getScriptName())) {
            $this->getDb()->query('INSERT INTO ?:payment_processors ?e', [
                'processor'          => __('stripe.stripe'),
                'processor_script'   => Stripe::getScriptName(),
                'processor_template' => 'addons/stripe/views/orders/components/payments/stripe.tpl',
                'admin_template'     => 'stripe.tpl',
                'callback'           => 'Y',
                'type'               => 'P',
                'addon'              => Stripe::getAddonName(),
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function onUninstall()
    {
        $processor_id = $this->getProcessorId();

        if (!$processor_id) {
            return;
        }

        $this->getDb()->query('DELETE FROM ?:payment_processors WHERE processor_id = ?i', $processor_id);
        $this->getDb()->query(
            'UPDATE ?:payments SET ?u WHERE processor_id = ?i',
            [
                'processor_id'     => 0,
                'processor_params' => '',
                'status'           => 'D',
            ],
            $processor_id
        );
    }

    public function onDemo()
    {
        $publishable_key = Registry::ifGet('config.stripe.publishable_key', '');
        $secret_key = Registry::ifGet('config.stripe.secret_key', '');
        $country = Registry::ifGet('config.stripe.country', '');
        $currency = Registry::ifGet('config.stripe.currency', '');
        $is_test = Registry::ifGet('config.stripe.is_test', false);

        $this->createPayment(
            'Apple Pay',
            20,
            fn_get_theme_path('[themes]/[theme]/media/images/addons/stripe/payments/apple_pay.png'),
            $publishable_key,
            $secret_key,
            $country,
            $currency,
            'apple_pay',
            $is_test
        );

        $this->createPayment(
            'Google Pay',
            30,
            fn_get_theme_path('[themes]/[theme]/media/images/addons/stripe/payments/google_pay.png'),
            $publishable_key,
            $secret_key,
            $country,
            $currency,
            'google_pay',
            $is_test
        );
    }

    /**
     * @return string
     */
    protected function getProcessorId()
    {
        $processor_id = $this->getDb()->getField(
            'SELECT processor_id FROM ?:payment_processors WHERE processor_script = ?s',
            Stripe::getScriptName()
        );

        return $processor_id;
    }

    /**
     * @return \Tygh\Database\Connection
     */
    protected function getDb()
    {
        return $this->application['db'];
    }

    /**
     * Creates a Stripe-based payment method.
     *
     * @param string $name            Payment method name
     * @param int    $position        Order
     * @param string $image           Payment method logo
     * @param string $publishable_key Stripe Publishable key
     * @param string $secret_key      Stripe Secret key
     * @param string $country         Stripe account country
     * @param string $currency        Stripe account currency
     * @param string $type            Payment type: card, apple_pay, google_pay
     * @param bool   $is_test         Whether this is a test payment
     * @param int    $company_id      Owning company ID
     *
     * @throws \Tygh\Exceptions\DeveloperException
     */
    protected function createPayment(
        $name,
        $position,
        $image,
        $publishable_key,
        $secret_key,
        $country,
        $currency,
        $type,
        $is_test,
        $company_id = 1
    ) {
        $current_allow_external_uploads = Registry::ifGet('runtime.allow_upload_external_paths', false);
        Registry::set('runtime.allow_upload_external_paths', true, true);

        $_REQUEST['payment_image_image_data'] = [
            [
                'detailed_alt' => '',
                'type'         => 'M',
                'object_id'    => 0,
                'position'     => 0,
            ],
        ];

        $_REQUEST['type_payment_image_image_icon'] = [
            'server',
        ];

        $_REQUEST['file_payment_image_image_icon'] = [
            $image,
        ];

        $payment_id = fn_update_payment([
            'payment'          => $name,
            'position'         => $position,
            'processor_id'     => $this->getProcessorId(),
            'instructions'     => __('stripe.test_payment.description'),
            'company_id'       => 0,
            'processor_params' => [
                'is_stripe'           => YesNo::YES,
                'is_test'             => YesNo::toId($is_test),
                'publishable_key'     => $publishable_key,
                'secret_key'          => $secret_key,
                'payment_type'        => $type,
                'country'             => $country,
                'currency'            => $currency,
                'show_payment_button' => YesNo::YES,
            ],
        ], 0);
        if (fn_allowed_for('ULTIMATE')) {
            fn_ult_update_share_object($payment_id, 'payments', $company_id);
        }

        Registry::set('runtime.allow_upload_external_paths', $current_allow_external_uploads, true);
    }
}
