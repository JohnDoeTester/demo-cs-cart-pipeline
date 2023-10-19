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

namespace Tygh\Addons\PaypalCommercePlatform;

use Tygh\Enum\VendorPayoutApprovalStatuses;
use Tygh\Enum\VendorPayoutTypes;
use Tygh\Enum\YesNo;
use Tygh\VendorPayouts;

class PayoutsManager
{
    /** @var int $company_id */
    protected $company_id;

    /** @var \Tygh\VendorPayouts $manager */
    protected $manager;

    /**
     * PayoutsManager constructor.
     *
     * @param int $company_id Vendor ID to instantiate payouts manager for
     */
    public function __construct($company_id)
    {
        $this->company_id = $company_id;
        $this->manager = VendorPayouts::instance(['vendor' => $company_id]);
    }

    /**
     * Creates withdrawal when an order is paid.
     *
     * @param float $amount   Withdrawal amount
     * @param int   $order_id Order ID
     *
     * @return int Withdrawal ID
     */
    public function createWithdrawal($amount, $order_id)
    {
        $params = [
            'company_id'      => $this->company_id,
            'payout_type'     => VendorPayoutTypes::WITHDRAWAL,
            'approval_status' => VendorPayoutApprovalStatuses::COMPLETED,
            'payout_amount'   => $amount,
            'comments'        => __(
                'paypal_commerce_platform.withdrawal_for_the_order',
                [
                    '[order_id]' => $order_id,
                ]
            ),
        ];

        return $this->manager->update($params);
    }

    /**
     * Updates refund info when an order is refunded.
     *
     * @param float $amount   Refund amount
     * @param int   $order_id Order ID
     *
     * @return int|void Refund ID
     */
    public function updateRefund($amount, $order_id)
    {
        $params = [
            'exclude_from_balance' => YesNo::YES,
            'comments'             => __('paypal_commerce_platform.refunded_via_paypal')
        ];

        $amount = -$amount;

        $refund_payouts = db_get_array(
            'SELECT * FROM ?:vendor_payouts WHERE payout_type = ?s AND order_id = ?s AND order_amount = ?s',
            VendorPayoutTypes::ORDER_REFUNDED,
            $order_id,
            $amount
        );
        $refund_payout = reset($refund_payouts);

        if (empty($refund_payout['payout_id'])) {
            return;
        }

        return $this->manager->update($params, $refund_payout['payout_id']);
    }

    /**
     * Gets order commission value.
     *
     * @param int $order_id Order ID
     *
     * @return float Commission value
     */
    public function getOrderFee($order_id)
    {
        $commission = $this->manager->getSimple(
            [
                'order_id'    => $order_id,
                'payout_type' => VendorPayoutTypes::ORDER_PLACED,
            ]
        );

        if (!$commission) {
            return 0;
        }

        $commission = reset($commission);

        return $commission['commission_type'] === 'P'
            ? $commission['commission_amount']
            : $commission['commission'];
    }
}
