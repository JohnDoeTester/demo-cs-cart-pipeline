{*
Payment processor configuration form.
*}

{$suffix = $payment_id|default:0}
{$supported_country_codes = ["AE", "AT", "AR", "AU", "BE", "BR", "BG", "BO", "CA", "CH", "CL", "CO", "CR", "CY", "CZ", "DE", "DK", "DO", "EE", "EG", "ES", "FI", "FR", "GB", "GR", "HK", "HR", "HU", "ID", "IE", "IL", "IN", "IS", "IT", "LI", "LT", "LU", "LV", "MY", "MT", "MX", "NL", "NO", "NZ", "PE", "PL", "PT", "PY", "RO", "SE", "SG", "SI", "SK", "TH", "TT", "US", "UY", "JP"]}
{$countries = fn_get_countries(["country_codes" => $supported_country_codes])}

{script src="js/addons/stripe/backend/config.js"}

<input type="hidden"
       name="payment_data[processor_params][is_stripe]"
       value="{"YesNo::YES"|enum}"
/>

<input type="hidden"
       name="payment_data[processor_params][is_test]"
       value="{$processor_params.is_test|default:("YesNo::NO"|enum)}"
/>

<input type="hidden"
       name="payment_data[processor_params][webhook_id]"
       value="{$processor_params.webhook_id}"
/>

<input type="hidden"
       name="payment_data[processor_params][webhook_secret_key]"
       value="{$processor_params.webhook_secret_key}"
/>

<div class="control-group">
    <label for="elm_publishable_key{$suffix}"
           class="control-label cm-required"
    >{__("stripe.publishable_key")}:</label>
    <div class="controls">
        <input type="text"
               name="payment_data[processor_params][publishable_key]"
               id="elm_publishable_key{$suffix}"
               value="{$processor_params.publishable_key}"
        />
    </div>
</div>

<div class="control-group">
    <label for="elm_secret_key{$suffix}"
           class="control-label cm-required"
    >{__("stripe.secret_key")}:</label>
    <div class="controls">
        <input type="password"
               name="payment_data[processor_params][secret_key]"
               id="elm_secret_key{$suffix}"
               value="{$processor_params.secret_key}"
               autocomplete="new-password"
        />
    </div>
</div>

<div class="control-group">
    <label for="elm_merchant_identifier{$suffix}"
           class="control-label"
    >{__("stripe.merchant_identifier")}:</label>
    <div class="controls">
        <input type="text"
               name="payment_data[processor_params][merchant_identifier]"
               id="elm_merchant_identifier{$suffix}"
               value="{$processor_params.merchant_identifier}"
        />
    </div>
</div>

<div class="control-group">
    <label for="elm_country{$suffix}"
           class="control-label"
    >{__("stripe.account_country")}</label>
    <div class="controls">
        <select name="payment_data[processor_params][country]"
                id="elm_country{$suffix}"
        >
            {foreach $countries[0] as $country}
                <option value="{$country.code}"
                        {if $processor_params.country === $country.code}selected="selected"{/if}
                >{$country.country}</option>
            {/foreach}
        </select>
    </div>
</div>

<div class="control-group">
    <label for="elm_currency{$suffix}"
           class="control-label"
    >{__("currency")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][currency]"
                id="elm_currency{$suffix}"
        >
            {foreach $currencies as $code => $currency}
                <option value="{$code}"
                        {if $processor_params.currency === $code}selected="selected"{/if}
                >{$currency.description}</option>
            {/foreach}
        </select>
    </div>
</div>

<div class="control-group">
    <label for="elm_currency{$suffix}"
           class="control-label"
    >{__("stripe.payment_type")}:</label>
    <div class="controls">
        <div class="row-fluid">
            <div class="span4">
                <ul class="unstyled">
                    {foreach ['stripe_checkout', 'card', 'apple_pay', 'google_pay'] as $payment_type}
                        <li>
                            <label class="radio inline"
                                   for="elm_payment_type_{$payment_type}{$suffix}"
                            >
                                {__("stripe.payment_type.{$payment_type}")}
                                <input type="radio"
                                       id="elm_payment_type_{$payment_type}{$suffix}"
                                       data-ca-stripe-description-element-id="elm_payment_type_{$payment_type}{$suffix}_description"
                                       data-ca-stripe-payment-button-name="{__("stripe.payment_type.buy_with_{$payment_type}")}"
                                       data-ca-stripe-show-payment-button-label-id="lbl_show_payment_button{$suffix}"
                                       name="payment_data[processor_params][payment_type]"
                                       value="{$payment_type}"
                                       {if $processor_params.payment_type|default:'stripe_checkout' == $payment_type}
                                           checked="checked"
                                       {/if}
                                />
                            </label>
                        </li>
                    {/foreach}
                </ul>
            </div>
            <div class="span6">
                <div id="elm_payment_type_apple_pay{$suffix}_description"
                     class="stripe-description hidden"
                >
                    <small>
                        {__("stripe.payment_type.apple_pay.description", [
                            "[guidelines_url]" => "https://developer.apple.com/design/human-interface-guidelines/apple-pay/overview/introduction/"
                        ]) nofilter}
                        <p>
                            <a href="{$images_dir}/addons/stripe/payments/apple_pay.png"
                               target="_blank"
                            >
                                <img src="{$images_dir}/addons/stripe/payments/apple_pay.png"
                                     height="60"
                                     style="height: 60px;"
                                     alt="{__("stripe.payment_type.apple_pay")}"
                                />
                            </a>
                        </p>
                    </small>
                </div>
                <div id="elm_payment_type_google_pay{$suffix}_description"
                     class="stripe-description hidden"
                >
                    <small>
                        {__("stripe.payment_type.google_pay.description", [
                            "[guidelines_url]" => "https://developers.google.com/pay/api/web/guides/brand-guidelines"
                        ]) nofilter}
                        <p>
                            <a href="{$images_dir}/addons/stripe/payments/google_pay.png"
                               target="_blank"
                            >
                                <img src="{$images_dir}/addons/stripe/payments/google_pay.png"
                                     height="60"
                                     style="height: 60px;"
                                     alt="{__("stripe.payment_type.google_pay")}"
                                />
                            </a>
                        </p>
                    </small>
                </div>
                <div id="elm_payment_type_stripe_checkout{$suffix}_description"
                     class="stripe-description hidden"
                >
                    <small>
                        {__("stripe.payment_type.stripe_checkout.description") nofilter}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="control-group">
    <label for="elm_show_payment_button{$suffix}"
           class="control-label"
           id="lbl_show_payment_button{$suffix}"
           data-ca-stripe-show-payment-button-template="{__("stripe.show_payment_button")|escape:html}"
    >{__("stripe.show_payment_button")}</label>
    <div class="controls">
        <input type="hidden"
               name="payment_data[processor_params][show_payment_button]"
               value="{"YesNo::NO"|enum}"
        />
        <input type="checkbox"
               id="elm_show_payment_button{$suffix}"
               name="payment_data[processor_params][show_payment_button]"
               value="{"YesNo::YES"|enum}"
                {if $processor_params.show_payment_button|default:("YesNo::NO"|enum) == "YesNo::YES"|enum}
                    checked="checked"
                {/if}
        />
    </div>
</div>
