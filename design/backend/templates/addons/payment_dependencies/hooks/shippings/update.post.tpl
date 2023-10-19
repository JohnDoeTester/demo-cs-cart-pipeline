<div class="control-group">
    <label class="control-label">{__("pd_allowed_payment_methods")}:</label>
    <div class="controls">
        <input type="hidden" name="shipping_data[enable_payment_ids]" value="" />
        {foreach from=$payments item="payment"}
            <label class="checkbox inline {if $payment.status != "ObjectStatuses::ACTIVE"|enum}hidden{/if}" for="elm_shippings_payments_{$payment.payment_id}">
            <input type="checkbox" name="shipping_data[enable_payment_ids][]" id="elm_shippings_payments_{$payment.payment_id}" {if !$payment.payment_id|in_array:$shipping.disable_payment_ids}checked="checked"{/if} value="{$payment.payment_id}" />
            {$payment.payment}</label>
        {foreachelse}
            &ndash;
        {/foreach}
        <p class="muted description">{__("pd_allowed_payment_methods_text")}</p>
    </div>
</div>