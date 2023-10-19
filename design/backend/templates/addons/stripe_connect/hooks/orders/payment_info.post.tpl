{if $is_show_transfer_funds_button && $is_stripe_connect_payment}
    <div class="control-group">
        {include file="buttons/button.tpl"
            but_text=__("stripe_connect.transfer_funds_to_vendors")
            but_role="action"
            but_href="stripe_connect.transfer_funds?order_id=`$order_info.order_id`&redirect_url=`$config.current_url|escape:url`"
            but_meta="btn cm-post"
        }
    </div>
{elseif
    isset($order_info.payment_method.processor_params.is_stripe_connect)
    && isset($order_info.payment_method.processor_params.delay_transfer_of_funds)
    && $order_info.payment_method.processor_params.is_stripe_connect === "YesNo::YES"|enum
    && $order_info.payment_method.processor_params.delay_transfer_of_funds === "YesNo::YES"|enum
    && $order_info.payment_info["stripe_connect.charge_id"]
    && $order_info.payment_info["stripe_connect.transfer_id"]
    && !$order_info.payment_info["stripe_connect.refund_id"]
}
    <div class="control-group text-success">
        {__("stripe_connect.funds_were_transferred_to_vendor")}
    </div>
{/if}
