{if $addons.paypal.status == "D"}
    <div class="alert alert-block">
	<p>{__("paypal.addon_is_disabled_notice") nofilter}</p>
    </div>
{else}

{include file="addons/paypal/common/connect_to_paypal.tpl"}
{$suffix = $payment_id|default:0}

<div class="control-group">
    <label class="control-label" for="currency{$suffix}">{__("currency")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][currency]" id="currency{$suffix}">
            {foreach from=$paypal_currencies item="currency"}
                <option value="{$currency.code}"{if !$currency.active} disabled="disabled"{/if}{if $processor_params.currency == $currency.code} selected="selected"{/if}>{$currency.name}</option>
            {/foreach}
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="order_prefix{$suffix}">{__("order_prefix")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][order_prefix]" id="order_prefix{$suffix}" size="36" value="{$processor_params.order_prefix}" >
    </div>
</div>

{include file="common/subheader.tpl" title=__("addons.paypal.technical_details") meta="collapsed" target="#section_technical_details{$suffix}"}

<div id="section_technical_details{$suffix}" class="collapse out">

    <div class="control-group">
        <label class="control-label" for="elm_in_context{$suffix}">{__("paypal_use_in_context_checkout")}:</label>
        <div class="controls">
            <input type="hidden" name="payment_data[processor_params][in_context]" value="N" />
            <input type="checkbox" name="payment_data[processor_params][in_context]" {if $processor_params.in_context|default:"Y" == "Y"}checked="checked"{/if} id="elm_in_context{$suffix}" value="Y" />
            <p class="muted description">{__("ttc_paypal_use_in_context_checkout")}</p>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label{if $processor_params.in_context|default:"Y" == "Y"} cm-required{/if}" for="elm_merchant_id{$suffix}" id="lbl_merchant_id{$suffix}">{__("merchant_id")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][merchant_id]" id="elm_merchant_id{$suffix}" size="24" value="{$processor_params.merchant_id}" >
            <div class="muted description" id="elm_merchant_id_notice{$suffix}">{__("paypal_express_notice") nofilter}</div>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="username{$suffix}">{__("username")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][username]" id="username{$suffix}" size="24" value="{$processor_params.username}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="password{$suffix}">{__("password")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][password]" id="password{$suffix}" size="24" value="{$processor_params.password}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label">{__("paypal_authentication_method")}:</label>
        <div class="controls">
            <label class="radio inline" for="elm_payment_auth_method_cert{$suffix}">
                <input id="elm_payment_auth_method_cert{$suffix}" type="radio" value="cert" name="payment_data[processor_params][authentication_method]" {if $processor_params.authentication_method == "cert" || !$processor_params.authentication_method} checked="checked"{/if}>
                {__("certificate")}
            </label>

            <label class="radio inline" for="elm_payment_auth_method_signature{$suffix}">
                <input id="elm_payment_auth_method_signature{$suffix}" type="radio" value="signature" name="payment_data[processor_params][authentication_method]" {if $processor_params.authentication_method == "signature"} checked="checked"{/if}>
                {__("signature")}
            </label>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="certificate{$suffix}">{__("certificate_filename")}:</label>
        <div class="controls" id="certificate_file{$suffix}">

            {if $processor_params.certificate_filename}
                <div class="text-type-value pull-left">
                    {$processor_params.certificate_filename}
                    <a href="{'payments.delete_certificate?payment_id='|cat:$payment_id|fn_url}" class="cm-ajax cm-post" data-ca-target-id="certificate_file{$suffix}">
                        {include_ext file="common/icon.tpl"
                            class="icon-remove-sign cm-tooltip hand"
                            title=__('remove')
                        }
                    </a>
                </div>
            {/if}

            <div {if $processor_params.certificate_filename}class="clear"{/if}>{include file="common/fileuploader.tpl" var_name="payment_certificate[]"}</div>
        <!--certificate_file{$suffix}--></div>
    </div>

    <div class="control-group">
        <label class="control-label" for="signature{$suffix}">{__("signature")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][signature]" id="signature{$suffix}" value="{$processor_params.signature}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="send_adress{$suffix}">{__("send_shipping_address")}:</label>
        <div class="controls">
            <input type="checkbox" name="payment_data[processor_params][send_adress]" {if !$payment_id || $processor_params.send_adress == "Y"}checked="checked"{/if} id="send_adress{$suffix}" value="Y">
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="mode{$suffix}">{__("test_live_mode")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][mode]" id="mode{$suffix}">
                <option value="test" {if $processor_params.mode === "test"} selected="selected"{/if}>{__("test")}</option>
                <option value="live" {if $processor_params.mode === "live"} selected="selected"{/if}>{__("live")}</option>
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="elm_show_cart_button{$suffix}">{__("paypal.show_cart_button")}:</label>
        <div class="controls">
            <input type="hidden"
                   name="payment_data[processor_params][show_cart_button]"
                   value="{"YesNo::NO"|enum}"
            />
            <input type="checkbox"
                   name="payment_data[processor_params][show_cart_button]"
                   {if $processor_params.show_cart_button|default:("YesNo::NO"|enum) === "YesNo::YES"|enum}
                       checked="checked"
                   {/if}
                   id="elm_show_cart_button{$suffix}"
                   value="{"YesNo::YES"|enum}"
            />
        </div>
    </div>
</div>

<script>
    (function(_, $) {
        $(document).on('change', '#elm_in_context{$suffix}', function() {
            $('#lbl_merchant_id{$suffix}').toggleClass('cm-required', $(this).is(':checked'));
        });
    })(Tygh, Tygh.$);
</script>
{/if}
