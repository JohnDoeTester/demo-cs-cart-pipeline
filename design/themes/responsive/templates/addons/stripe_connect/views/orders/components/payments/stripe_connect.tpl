{*
Payment form.
*}

{if $payment_method|default:[]}
    {$payment_id = $payment_method.payment_id}
{else}
    {$payment_id = $payment_info.payment_id}
{/if}

{if $payment_method.processor_params|default:[]}
    {$processor_params = $payment_method.processor_params}
{else}
    {$processor_params = $payment_info.processor_params|default:[]}
{/if}

{if $processor_params.is_checkout_enabled === "YesNo::YES"|enum}

{elseif $processor_params.is_stripe_connect}
    {$payment_type = $processor_params.payment_type|default:"card_simple"}
    {script src="js/addons/stripe_connect/views/{$payment_type}.js" class="cm-ajax-force"}
    <div class="litecheckout__group clearfix"
         data-ca-stripe-element="form"
         data-ca-stripe-publishable-key="{$processor_params.publishable_key}"
    >
        <input type="hidden"
               name="payment_info[stripe_connect.payment_intent_id]"
               data-ca-stripe-element="paymentIntentId"
               data-ca-stripe-payment-id="{$payment_id}"
               data-ca-stripe-order-id="{$order_id}"
               data-ca-stripe-confirmation-url="{fn_url("stripe_connect.check_confirmation")}"
               data-ca-stripe-confirm-url="{fn_url("stripe_connect.confirm")}"
        />

        {include file = "addons/stripe_connect/views/checkout/components/payments/{$payment_type}.tpl"
            payment_type = $payment_type
            total = $stripe_cart_total
            currency = $processor_params.currency
            country = $processor_params.country
        }
    </div>
{/if}
