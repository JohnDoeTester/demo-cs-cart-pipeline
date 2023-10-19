{if $addons.rma.status == "A"}
    <div class="control-group setting-wide">
        <label for="elm_rma_refunded_order_status"
               class="control-label"
        >
            {__("stripe_connect.rma.order_status_on_refund")}
            <p class="muted description">{__("ttc_stripe_connect.rma.order_status_on_refund")}</p>
        </label>
        <div class="controls">
            <select name="addon_data[options][{$rma_refunded_order_status_id}]"
                    id="elm_rma_refunded_order_status"
            >
                <option value=""
                        {if !$addons.stripe_connect.rma_refunded_order_status}selected="selected"{/if}
                >{__("stripe_connect.do_not_change")}</option>
                <optgroup label="{__("stripe_connect.set_status_to")}">
                    {foreach $order_statuses as $code => $status}
                        <option value="{$code}"
                                {if $addons.stripe_connect.rma_refunded_order_status == $code}selected="selected"{/if}
                        >{$status}</option>
                    {/foreach}
                </optgroup>
            </select>
        </div>
    </div>
{/if}