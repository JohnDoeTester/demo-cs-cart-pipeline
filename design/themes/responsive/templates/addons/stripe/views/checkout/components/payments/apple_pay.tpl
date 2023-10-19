{include file="addons/stripe/views/checkout/components/payments/payment_button.tpl"
    payment_type = $payment_type
    total = $stripe_cart_total
    currency = $processor_params.currency
    country = $processor_params.country
    is_test = $processor_params.is_test === "YesNo::YES"|enum
}
