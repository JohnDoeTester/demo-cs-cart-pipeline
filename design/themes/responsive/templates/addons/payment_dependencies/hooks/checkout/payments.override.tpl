{if $cart.payment_id}
    {foreach $payment_methods as $payment}
        <div class="litecheckout__shipping-method litecheckout__field litecheckout__field--xsmall">

            <input type="radio"
                   name="selected_payment_method"
                   id="radio_{$payment.payment_id}"
                   data-ca-target-form="litecheckout_payments_form"
                   data-ca-url="checkout.checkout"
                   data-ca-result-ids="litecheckout_final_section,litecheckout_step_payment,shipping_rates_list,litecheckout_terms,checkout*"
                   class="litecheckout__shipping-method__radio cm-select-payment hidden"
                   value="{$payment.payment_id}"
                   {if $payment.payment_id == $cart.payment_id}checked{/if}
            />

            <label id="payments_{$payment.payment_id}"
                   class="litecheckout__shipping-method__wrapper js-litecheckout-toggle"
                   for="radio_{$payment.payment_id}"
                   data-ca-toggling="payments_form_wrapper_{$payment.payment_id}"
                   data-ca-hide-all-in=".litecheckout__payment-methods"
            >
                {if $payment.image}
                    <div class="litecheckout__shipping-method__logo">
                        {include file="common/image.tpl" obj_id=$payment.payment_id images=$payment.image class="litecheckout__shipping-method__logo-image"}
                    </div>
                {/if}
                <p class="litecheckout__shipping-method__title">{$payment.payment}</p>
                <p class="litecheckout__shipping-method__delivery-time">{$payment.description}</p>
            </label>

        </div>
    {/foreach}
{else}
    <div class="litecheckout__item">
        <p class="ty-error-text">
            {__("pd.no_payments_available")}
        </p>
    </div>
{/if}