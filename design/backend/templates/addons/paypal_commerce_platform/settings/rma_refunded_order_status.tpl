{if $addons.rma.status == "A"}
    <div class="control-group setting-wide">
        <label for="elm_rma_refunded_order_status"
               class="control-label"
        >
            {__("paypal_commerce_platform.rma.order_status_on_refund")}
            <p class="muted description">{__("ttc_paypal_commerce_platform.rma.order_status_on_refund")}</p>
        </label>
        <div class="controls">
            <select name="addon_data[options][{$rma_refunded_order_status_id}]"
                    id="elm_rma_refunded_order_status"
            >
                <option value=""
                        {if !$addons.paypal_commerce_platform.rma_refunded_order_status}selected="selected"{/if}
                >{__("paypal_commerce_platform.do_not_change")}</option>
                <optgroup label="{__("paypal_commerce_platform.set_status_to")}">
                    {foreach $order_statuses as $code => $status}
                        <option value="{$code}"
                                {if $addons.paypal_commerce_platform.rma_refunded_order_status == $code}selected="selected"{/if}
                        >{$status}</option>
                    {/foreach}
                </optgroup>
            </select>
        </div>
    </div>
{/if}