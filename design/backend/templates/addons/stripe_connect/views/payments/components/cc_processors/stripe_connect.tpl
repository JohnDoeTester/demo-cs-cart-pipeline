{$suffix = $payment_id|default:0}

<input type="hidden"
       name="payment_data[processor_params][is_stripe_connect]"
       value={"YesNo::YES"|enum}
/>

<input type="hidden"
       name="payment_data[processor_params][created_at]"
       value="{if $processor_params.created_at}{$processor_params.created_at}{else}{time()}{/if}"
/>

<div class="control-group">
    <label for="elm_client_id{$suffix}"
           class="control-label cm-required"
    >{__("stripe_connect.client_id")}:</label>
    <div class="controls">
        <input type="text"
               name="payment_data[processor_params][client_id]"
               id="elm_client_id{$suffix}"
               value="{$processor_params.client_id}"
        />
    </div>
</div>

<div class="control-group">
    <label for="elm_publishable_key{$suffix}"
           class="control-label cm-required"
    >{__("stripe_connect.publishable_key")}:</label>
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
    >{__("stripe_connect.secret_key")}:</label>
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
    <label class="control-label">
        {__("stripe_connect.redirect_uris")}:
    </label>
    <div class="controls">
        {include file="common/widget_copy.tpl"
            widget_copy_title=__("stripe_connect.redirect_uri_vendor")
            widget_copy_text=__("stripe_connect.redirect_uris.description")
            widget_copy_code_text="companies.stripe_connect_auth"|fn_url:"V"
        }

        {if !$runtime.company_id}
            {include file="common/widget_copy.tpl"
                widget_copy_title=__("stripe_connect.redirect_uri_admin")
                widget_copy_text=__("stripe_connect.redirect_uris.description")
                widget_copy_code_text="companies.stripe_connect_auth"|fn_url:"A"
            }
        {/if}
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
                        {if $processor_params.currency == $code} selected="selected"{/if}
                >{$currency.description}</option>
            {/foreach}
        </select>
    </div>
</div>

<div class="control-group">
    <label for="elm_payment_type{$suffix}"
           class="control-label"
    >
        {__("stripe_connect.enable_3d_secure")}:
    </label>
    <div class="controls">
        <input type="hidden"
               name="payment_data[processor_params][payment_type]"
               value="{"Addons\StripeConnect\PaymentTypes::CARD_SIMPLE"|enum}"
        />
        <input type="checkbox"
               name="payment_data[processor_params][payment_type]"
               value="{"Addons\StripeConnect\PaymentTypes::CARD"|enum}"
                {if
                    $processor_params.payment_type === "Addons\StripeConnect\PaymentTypes::CARD"|enum
                    || empty($processor_params.payment_type)
                }
                    checked="checked"
                {/if}
               onclick="Tygh.$.disable_elms(['stripe_checkout{$suffix}'], !this.checked);Tygh.$('#stripe_checkout{$suffix}').prop('checked', false);"
        />
        <div class="stripe-config-form__3d-secure-description">
            {__("stripe_connect.enable_3d_secure.description") nofilter}
        </div>
    </div>
</div>

<div class="control-group">
    <label for="elm_is_checkout_enabled{$suffix}"
           class="control-label"
    >
        {__("stripe_connect.stripe_checkout")}:
    </label>
    <div class="controls">
        <input type="hidden"
               name="payment_data[processor_params][is_checkout_enabled]"
               value={"YesNo::NO"|enum}
        />
        <input id="stripe_checkout{$suffix}"
               type="checkbox"
               name="payment_data[processor_params][is_checkout_enabled]"
               value={"YesNo::YES"|enum}
               {if $processor_params.payment_type === "Addons\StripeConnect\PaymentTypes::CARD"|enum}
                   {if $processor_params.is_checkout_enabled === "YesNo::YES"|enum}
                       checked="checked"
                   {/if}
               {elseif empty($processor_params.payment_type)}
                   checked="checked"
               {else}
                   disabled="disabled"
               {/if}
        />
        <div class="stripe-config-form__is-checkout-enabled-description">
            {__("stripe_connect.stripe_checkout.description") nofilter}
        </div>
    </div>
</div>

<div class="control-group">
    <label for="elm_payment_type{$suffix}"
           class="control-label"
    >
        {__("stripe_connect.allow_express_accounts")}:
    </label>
    <div class="controls">
        <input type="hidden"
               name="payment_data[processor_params][allow_express_accounts]"
               value="{"YesNo::NO"|enum}"
        />
        <input type="checkbox"
               name="payment_data[processor_params][allow_express_accounts]"
               id="elm_allow_express_accounts{$suffix}"
               {if $processor_params.allow_express_accounts === "YesNo::YES"|enum}checked{/if}
               value="{"YesNo::YES"|enum}"
        />
        <div class="stripe-config-form__allow-express-accounts-description">
            {__("stripe_connect.allow_express_accounts.description") nofilter}
        </div>
    </div>
</div>
<div class="control-group">
    <label class="control-label">
        {__("stripe_connect.check_accounts")}:
    </label>
    <div class="controls">
        {include file="common/widget_copy.tpl"
        widget_copy_text=__("stripe_connect.check_accounts_cron")
        widget_copy_code_text="php /path/to/cart/"|fn_get_console_command:$config.admin_index:["dispatch" => "stripe_connect.check_accounts"]
        }
    </div>
</div>

{include file="common/subheader.tpl" title=__("stripe_connect.delay_transfer_of_funds_to_vendors") target="#delay_transfer_of_funds{$suffix}"}

<fieldset id="delay_transfer_of_funds{$suffix}" class="collapse-visible collapse in">
    <div class="control-group">
        <label for="elm_delay_transfer_of_funds{$suffix}"
               class="control-label"
        >{__("stripe_connect.delay_transfer_of_funds")}:</label>
        <div class="controls">
            <input type="hidden"
                   name="payment_data[processor_params][delay_transfer_of_funds]"
                   value="{"YesNo::NO"|enum}"
            />
            <input type="checkbox"
                   name="payment_data[processor_params][delay_transfer_of_funds]"
                   id="elm_delay_transfer_of_funds{$suffix}"
                   {if $processor_params.delay_transfer_of_funds === "YesNo::YES"|enum}checked{/if}
                   value="{"YesNo::YES"|enum}"
            />
            <div class="stripe-config-form__delay-transfer-of-funds-description">
                {__("stripe_connect.trigger_transfer_funds.description")}
            </div>
        </div>
    </div>

    {if $payment_id}
        <div class="control-group">
            <label class="control-label">
                {__("stripe_connect.automatic_transfer")}:
            </label>
            <div class="controls">
                    {include file="common/widget_copy.tpl"
                        widget_copy_text=__("stripe_connect.cron_text")
                        widget_copy_code_text="php /path/to/cart/"|fn_get_console_command:$config.admin_index:["dispatch" => "stripe_connect.transfer_funds_by_cron","payment_id" => $payment_id,"days" => 14]
                    }
            </div>
        </div>
    {/if}
</fieldset>
