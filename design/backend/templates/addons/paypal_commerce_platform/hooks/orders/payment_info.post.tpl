{if $is_show_disburse_payouts_button}
    <div class="control-group">
        {include file="buttons/button.tpl"
            but_text=__("paypal_commerce_platform.disburse_payouts_to_vendors")
            but_role="action"
            but_href="paypal_commerce_platform.disburse_payouts?order_id=`$order_info.order_id`&redirect_url=`$config.current_url|escape:url`"
            but_meta="btn cm-post"
        }
    </div>
{elseif
    isset($order_info.payment_method.processor_params.is_paypal_commerce_platform)
    && isset($order_info.payment_method.processor_params.delay_disburse_of_payouts)
    && $order_info.payment_method.processor_params.is_paypal_commerce_platform === "YesNo::YES"|enum
    && $order_info.payment_method.processor_params.delay_disburse_of_payouts === "YesNo::YES"|enum
    && $order_info.payment_info["paypal_commerce_platform.capture_id"]
    && $order_info.payment_info["paypal_commerce_platform.payout_id"]
    && !$order_info.payment_info["paypal_commerce_platform.refund_id"]
}
    <div class="control-group text-success">
        {__("paypal_commerce_platform.funds_were_transferred_to_vendor")}
    </div>
{/if}
