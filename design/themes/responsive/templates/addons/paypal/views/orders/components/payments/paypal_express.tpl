{if $payment_method.processor_params.in_context|default:"N" == "Y" && !$smarty.session.pp_express_details}
    <input type="hidden"
           data-ca-paypal-in-context-checkout="true"
           data-ca-paypal-merchant-id="{$payment_method.processor_params.merchant_id}"
           data-ca-paypal-environment="{if $payment_method.processor_params.mode == "live"}production{else}sandbox{/if}"
           data-ca-paypal-button="litecheckout_place_order"
    />
{/if}
