{*
Payment form.
*}

{if $payment_method.processor_params|default:[]}
    {$processor_params = $payment_method.processor_params}
{else}
    {$processor_params = $payment_info.processor_params|default:[]}
{/if}

{if $processor_params.is_stripe_connect|default:false}
    {$default_payment_type="Addons\StripeConnect\PaymentTypes::CARD_SIMPLE"|enum}
    {$payment_type = $processor_params.payment_type|default:$default_payment_type}
    {script src="js/addons/stripe_connect/views/{$payment_type}.js"}

    <div class="clearfix"
         data-ca-stripe-element="form"
         data-ca-stripe-publishable-key="{$processor_params.publishable_key}"
    >
        <input type="hidden"
               name="payment_info[stripe_connect.payment_intent_id]"
               data-ca-stripe-element="paymentIntentId"
               data-ca-stripe-payment-id="{$cart.payment_id}"
               data-ca-stripe-confirmation-url="{fn_url("stripe_connect.check_confirmation")}"
               data-ca-stripe-process-payment-name="dispatch[order_management.place_order]"
        />

        <input type="hidden"
               name="payment_info[stripe_connect.token]"
               data-ca-stripe-element="token"
        />

        <div class="stripe-payment-form__section stripe-payment-form__section--card">
            <div class="ty-credit-card cm-cc_form">
                <div class="ty-credit-card__control-group control-group ty-control-group">
                    <label for="credit_card_number"
                           class="control-group ty-control-group__title cm-cc-number cc-number cm-required"
                    >{__("card_number")}</label>
                    <div class="stripe-payment-form__card"
                         data-ca-stripe-element="card"
                    >{* Card number field *}</div>
                </div>

                <div class="ty-credit-card__control-group control-group ty-control-group">
                    <label for="credit_card_month"
                           class="control-group ty-control-group__title cm-cc-date cc-date cm-cc-exp-month cm-required"
                    >{__("valid_thru")}</label>
                    <div class="stripe-payment-form__expiry"
                         data-ca-stripe-element="expiry"
                    >{* Expriry field *}</div>
                </div>

                <div class="ty-credit-card__control-group control-group ty-control-group">
                    <label for="credit_card_name"
                           class="control-group ty-control-group__title cm-required"
                    >{__("cardholder_name")}</label>
                    <input size="35"
                           type="text"
                           id="credit_card_name"
                           value=""
                           class="cm-cc-name ty-credit-card__input ty-uppercase"
                           data-ca-stripe-element="name"
                    />
                </div>
            </div>

            <div class="control-group ty-control-group ty-credit-card__cvv-field cvv-field">
                <label for="credit_card_cvv2" class="control-group ty-control-group__title cm-required cm-cc-cvv2  cc-cvv2 cm-autocomplete-off">{__("cvv2")}</label>
                <div class="stripe-payment-form__cvc"
                     data-ca-stripe-element="cvc"
                >{* CVC field *}</div>

                <div class="cvv2">
                    <a>{__("what_is_cvv2")}</a>
                    <div class="popover fade bottom in">
                        <div class="arrow"></div>
                        <h3 class="popover-title">{__("what_is_cvv2")}</h3>
                        <div class="popover-content">
                            <div class="cvv2-note">
                                <div class="card-info clearfix">
                                    <div class="cards-images">
                                        <img src="{$images_dir}/visa_cvv.png" border="0" alt=""/>
                                    </div>
                                    <div class="cards-description">
                                        <strong>{__("visa_card_discover")}</strong>
                                        <p>{__("credit_card_info")}</p>
                                    </div>
                                </div>
                                <div class="card-info ax clearfix">
                                    <div class="cards-images">
                                        <img src="{$images_dir}/express_cvv.png" border="0" alt=""/>
                                    </div>
                                    <div class="cards-description">
                                        <strong>{__("american_express")}</strong>
                                        <p>{__("american_express_info")}</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="control-group">
                <label for="credit_card_name"
                       class="control-group ty-control-group__title cm-required"
                >{__("zip_postal_code")}</label>
                <div class="stripe-payment-form__postal_code"
                     data-ca-stripe-element="postal_code"
                >{* Postal code field *}</div>
            </div>
        </div>
    </div>
{/if}
