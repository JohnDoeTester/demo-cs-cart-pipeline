{$button_label = __("stripe.online_payment")}
{if $is_test}
    {$button_label = __("stripe.test_payment")}
{/if}
<div class="stripe-payment-form__payment-button"
     data-ca-stripe-element="paymentButton"
     data-ca-stripe-total="{$total}"
     data-ca-stripe-currency="{$currency}"
     data-ca-stripe-country="{$country}"
     data-ca-stripe-payment-label="{$button_label}"
     data-ca-stripe-unsupported-payment=".stripe-payment-form__section--unsupported-payment-{$payment_type}"
></div>

<div class="litecheckout__item stripe-payment-form__section stripe-payment-form__section--unsupported-payment stripe-payment-form__section--unsupported-payment-{$payment_type} hidden">
    {__("stripe.{$payment_type}_not_supported")}
</div>
