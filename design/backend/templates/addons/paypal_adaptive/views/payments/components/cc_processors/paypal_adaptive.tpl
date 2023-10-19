{include file="common/subheader.tpl" title=__("addons.paypal_adaptive.paypal_workflow") target="#ppa_workflow"}
<div id="ppa_workflow" class="collapse in">
    <div class="control-group">
        <label class="control-label" for="currency">{__("currency")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][currency]" id="currency">
                {foreach from=$paypal_currencies item="currency"}
                    <option value="{$currency.code}"{if !$currency.active} disabled="disabled"{/if}{if $processor_params.currency == $currency.code} selected="selected"{/if}>{$currency.name}</option>
                {/foreach}
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label">{__("paypal_adaptive_type_payments")}:</label>
        <div class="controls">
            <label class="radio inline">
                <input class="cm-switch-availability cm-switch-inverse cm-switch-visibility" id="sw_block_chained_settings" type="radio" value="parallel" name="payment_data[processor_params][payment_type]" {if $addons.paypal_adaptive.collect_payouts == "Y" || $processor_params.payment_type == "parallel" || !$processor_params.payment_type} checked="checked"{/if}>
                {__("paypal_adaptive_papallel_payments")}
            </label>
            <label class="radio inline">
                <input class="cm-switch-availability cm-switch-visibility" id="sw_block_chained_settings" type="radio" value="chained" name="payment_data[processor_params][payment_type]" {if $addons.paypal_adaptive.collect_payouts == "Y"} disabled="disabled"{elseif $processor_params.payment_type == "chained"} checked="checked"{/if}>
                {__("paypal_adaptive_chained_payments")}
            </label>
            <p class="muted description">{__("addons.paypal_adaptive.payment_type_notice", ["[url]" => "addons.update&addon=paypal_adaptive"|fn_url]) nofilter}</p>
        </div>
    </div>

    <div id="block_chained_settings"{if $addons.paypal_adaptive.collect_payouts == "Y" || $processor_params.payment_type == "parallel" || !$processor_params.payment_type} style="display: none;"{/if}>
        <div class="control-group">
            <label class="control-label" for="elm_paypal_fees_payer">{__("paypal_adaptive_payer_fees")}:</label>
            <div class="controls">
                <select name="payment_data[processor_params][fees_payer]" id="elm_paypal_fees_payer">
                    <option value="EACHRECEIVER" {if $processor_params.fees_payer == "EACHRECEIVER"}selected="selected"{/if}>{__("paypal_adaptive_fees_eachreceiver")}</option>
                    <option value="PRIMARYRECEIVER" {if $processor_params.fees_payer == "PRIMARYRECEIVER"}selected="selected"{/if}>{__("paypal_adaptive_fees_primaryreceiver")}</option>
                </select>
            </div>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label cm-required" for="primary_email">{__("primary_email")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][primary_email]" id="primary_email" value="{$processor_params.primary_email}" class="input-text" />
            <p class="muted description">{__("addons.paypal_adaptive.primary_email_notice")}</p>
        </div>
    </div>

    <input type="hidden" name="payment_data[processor_params][in_context]" value="N" />
</div>

{include file="common/subheader.tpl" title=__("addons.paypal_adaptive.store_settings") target="#ppa_store_settings"}
<div id="ppa_store_settings" class="collapse in">
    <div class="control-group">
        <label class="control-label" for="user_currency">{__("paypal_adaptive_override_with_secondary_currency")}:</label>
        <div class="controls">
            <input type="hidden" name="payment_data[processor_params][user_currency]" value="N" />
            <input type="checkbox" id="user_currency" name="payment_data[processor_params][user_currency]" {if $processor_params.user_currency == "Y"}checked="checked"{/if} value="Y">
        </div>
    </div>

    <div class="control-group">
        {assign var="statuses" value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses}

        <label class="control-label" for="elm_paypal_pending">{__("paypal_adaptive_pending_payment")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][statuses][pending_payment]" id="elm_paypal_pending">
                {foreach from=$statuses item="s" key="k"}
                    <option value="{$k}" {if (isset($processor_params.statuses.pending_payment) && $processor_params.statuses.pending_payment == $k) || (!isset($processor_params.statuses.pending_payment) && $k == 'I')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>
</div>

{$are_credentials_filled = $processor_params.username && $processor_params.password}
{include file="common/subheader.tpl" title=__("addons.paypal_adaptive.paypal_credentials") meta="{if $are_credentials_filled}collapsed{/if}" target="#ppa_credentials"}
<div id="ppa_credentials" class="collapse {if $are_credentials_filled}out{else}in{/if}">
    <div class="control-group">
        <label class="control-label" for="username">{__("paypal_api_username")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][username]" id="username" value="{$processor_params.username}" class="input-text" size="60"/>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="password">{__("paypal_api_password")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][password]" id="password" value="{$processor_params.password}" class="input-text" size="60"/>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label">{__("paypal_authentication_method")}:</label>
        <div class="controls">
            <label class="radio inline" for="elm_payment_auth_method_cert">
                <input id="elm_payment_auth_method_cert" type="radio" value="cert" name="payment_data[processor_params][authentication_method]" {if $processor_params.authentication_method == "cert" || !$processor_params.authentication_method} checked="checked"{/if}>
                {__("certificate")}
            </label>
            <label class="radio inline" for="elm_payment_auth_method_signature">
                <input id="elm_payment_auth_method_signature" type="radio" value="signature" name="payment_data[processor_params][authentication_method]" {if $processor_params.authentication_method == "signature"} checked="checked"{/if}>
                {__("signature")}
            </label>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="certificate_filename">{__("certificate_filename")}:</label>
        <div class="controls" id="certificate_file">
            {if $processor_params.certificate_filename}
                <div class="text-type-value pull-left">
                    {$processor_params.certificate_filename}
                    <a href="{'payments.delete_certificate?payment_id='|cat:$payment_id|fn_url}" class="cm-ajax" data-ca-target-id="certificate_file">
                        {include_ext file="common/icon.tpl"
                            class="icon-remove-sign cm-tooltip hand"
                            title=__('remove')
                        }
                    </a>
                </div>
            {/if}

            <div {if $processor_params.certificate_filename}class="clear"{/if}>{include file="common/fileuploader.tpl" var_name="payment_certificate[]"}</div>
            <!--certificate_file--></div>
    </div>

    <div class="control-group">
        <label class="control-label" for="api_signature">{__("signature")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][signature]" id="api_signature" value="{$processor_params.signature}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label">{__("test_live_mode")}:</label>
        <div class="controls">
            <label class="radio inline">
                <input class="cm-switch-availability cm-switch-inverse cm-switch-visibility" id="sw_block_app_id" type="radio" value="test" name="payment_data[processor_params][mode]" {if $processor_params.mode == "test" || !$processor_params.mode} checked="checked"{/if}>
                {__("test")}
            </label>
            <label class="radio inline">
                <input class="cm-switch-availability cm-switch-visibility" id="sw_block_app_id" type="radio" value="live" name="payment_data[processor_params][mode]" {if $processor_params.mode == "live"} checked="checked"{/if}>
                {__("live")}
            </label>
        </div>
    </div>

    <div id="block_app_id"{if $processor_params.mode == "test" || !$processor_params.mode} style="display: none;"{/if}>
        <div class="control-group">
            <label class="control-label cm-required" for="app_id">{__("application_id")}:</label>
            <div class="controls">
                <input type="text" name="payment_data[processor_params][app_id]" id="app_id" value="{$processor_params.app_id}" class="input-text" {if $processor_params.mode == "test" || !$processor_params.mode} disabled="" {/if} placeholder="APP-80W284485P519543T"/>
            </div>
        </div>
    </div>
</div>
