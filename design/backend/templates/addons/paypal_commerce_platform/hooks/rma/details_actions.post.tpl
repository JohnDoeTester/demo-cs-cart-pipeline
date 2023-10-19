{if $is_refund == "Y"
    && $order_info.payment_method.processor_params
    && $order_info.payment_method.processor_params.is_paypal_commerce_platform|default:null
}
    <div class="control-group notify-department">
        <label class="control-label"
               for="elm_paypal_commerce_platform_perform_refund"
        >
            {__("paypal_commerce_platform.rma.perform_refund")}
            <p class="muted description">{__("ttc_paypal_commerce_platform.rma.perform_refund")}</p>
        </label>
        <div class="controls">
            {if $order_info.payment_info["paypal_commerce_platform.refund_id"]}
                <p class="label label-success">{__("refunded")}</p>
            {else}
                <label class="checkbox">
                    <input type="checkbox"
                           name="change_return_status[paypal_commerce_platform_perform_refund]"
                           id="elm_paypal_commerce_platform_perform_refund"
                           value="Y"
                    />
                </label>
            {/if}
        </div>
    </div>
{/if}
