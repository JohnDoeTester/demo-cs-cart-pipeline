{$disable_funding = $payment_method.processor_params.disable_funding|default:[]|array_filter}

<input type="hidden"
       data-ca-paypal-checkout="true"
       data-ca-paypal-checkout-button="litecheckout_place_order"

       data-ca-paypal-checkout-client-id="{$payment_method.processor_params.client_id}"
       data-ca-paypal-checkout-currency="{$payment_method.processor_params.currency}"
       data-ca-paypal-checkout-disable-funding="{implode(",", $disable_funding)}"
       data-ca-paypal-checkout-style-layout="{$payment_method.processor_params.style.layout|default:"vertical"}"
       data-ca-paypal-checkout-style-color="{$payment_method.processor_params.style.color|default:"gold"}"
       data-ca-paypal-checkout-style-height="{$payment_method.processor_params.style.height|default:55}"
       data-ca-paypal-checkout-style-shape="{$payment_method.processor_params.style.shape|default:"rect"}"
       data-ca-paypal-checkout-style-label="{$payment_method.processor_params.style.label|default:"pay"}"
       data-ca-paypal-checkout-style-tagline="{$payment_method.processor_params.style.tagline|default:"false"}"

       data-ca-paypal-checkout-debug="{if $payment_method.processor_params.mode === "live"}false{else}true{/if}"
/>
<div id="smart-button-container">
    <div style="text-align: center;">
        <div id="paypal-button-container"></div>
    </div>
</div>
