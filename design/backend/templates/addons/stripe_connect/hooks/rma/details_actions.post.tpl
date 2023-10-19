{if $is_refund == "Y"
    && $order_info.payment_method.processor_params
    && $order_info.payment_method.processor_params.is_stripe_connect|default:null
}
    <div class="control-group notify-department">
        <label class="control-label"
               for="elm_stripe_connect_perform_refund"
        >
            {__("stripe_connect.rma.perform_refund")}
            <p class="muted description">{__("ttc_stripe_connect.rma.perform_refund")}</p>
        </label>
        <div class="controls">
            {if $order_info.payment_info["stripe_connect.refund_id"]}
                <p class="label label-success">{__("refunded")}</p>
            {else}
                <label class="checkbox">
                    <input type="checkbox"
                           name="change_return_status[stripe_connect_perform_refund]"
                           id="elm_stripe_connect_perform_refund"
                           value="Y"
                    />
                </label>
            {/if}
        </div>
    </div>
{/if}