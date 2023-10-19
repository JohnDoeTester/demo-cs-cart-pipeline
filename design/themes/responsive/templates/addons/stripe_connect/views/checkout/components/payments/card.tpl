<div class="litecheckout__item stripe-payment-form__section stripe-payment-form__section--card">
    <div class="clearfix">
        <div class="ty-credit-card cm-cc_form">
            <div class="ty-credit-card__control-group ty-control-group">
                <label for="credit_card_number"
                    class="ty-control-group__title cm-cc-number cc-number cm-required"
                >{__("card_number")}</label>
                <div class="stripe-payment-form__card"
                    data-ca-stripe-element="card"
                >{* Card number field *}</div>
            </div>

            <div class="ty-credit-card__control-group ty-control-group">
                <label for="credit_card_month"
                    class="ty-control-group__title cm-cc-date cc-date cm-cc-exp-month cm-required"
                >{__("valid_thru")}</label>
                <div class="stripe-payment-form__expiry"
                    data-ca-stripe-element="expiry"
                >{* Expriry field *}</div>
            </div>

            <div class="ty-credit-card__control-group ty-control-group">
                <label for="credit_card_name"
                    class="ty-control-group__title cm-required"
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

        <div class="ty-credit-card__cvv-field cvv-field">
            <div class="ty-control-group">
                <label for="credit_card_cvv2" class="ty-control-group__title cm-required cm-cc-cvv2  cc-cvv2 cm-autocomplete-off">{__("cvv2")}</label>
                <div class="stripe-payment-form__cvc"
                    data-ca-stripe-element="cvc"
                >{* CVC field *}</div>

                <div class="ty-cvv2-about">
                    <span class="ty-cvv2-about__title">{__("what_is_cvv2")}</span>
                    <div class="ty-cvv2-about__note">

                        <div class="ty-cvv2-about__info mb30 clearfix">
                            <div class="ty-cvv2-about__image">
                                <img src="{$images_dir}/visa_cvv.png" alt="" />
                            </div>
                            <div class="ty-cvv2-about__description">
                                <h5 class="ty-cvv2-about__description-title">{__("visa_card_discover")}</h5>
                                <p>{__("credit_card_info")}</p>
                            </div>
                        </div>
                        <div class="ty-cvv2-about__info clearfix">
                            <div class="ty-cvv2-about__image">
                                <img src="{$images_dir}/express_cvv.png" alt="" />
                            </div>
                            <div class="ty-cvv2-about__description">
                                <h5 class="ty-cvv2-about__description-title">{__("american_express")}</h5>
                                <p>{__("american_express_info")}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ty-control-group">
                <label for="credit_card_postal_code"
                       class="ty-control-group__title cm-cc-postal-code cm-required"
                >{__("zip_postal_code")}</label>
                <div class="stripe-payment-form__postal_code"
                     data-ca-stripe-element="postal_code"
                     data-ca-stripe-element-value="{$user_data.b_zipcode|default:$user_data.s_zipcode}"
                >{* Postal code field *}</div>
            </div>
        </div>
    </div>
</div>
