{if $addons.paypal.status == "D"}
    <div class="alert alert-block">
	<p>{__("paypal.addon_is_disabled_notice") nofilter}</p>
    </div>
{else}

<div class="control-group">
    <label class="control-label" for="pf_currency">{__("currency")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][currency]" id="pf_currency">
            {foreach from=$paypal_currencies item="currency"}
                <option value="{$currency.id}"{if !$currency.active} disabled="disabled"{/if}{if $processor_params.currency == $currency.id} selected="selected"{/if}>{$currency.name}</option>
            {/foreach}
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="order_prefix">{__("order_prefix")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][order_prefix]" id="order_prefix" size="60" value="{$processor_params.order_prefix}" >
    </div>
</div>

{include file="common/subheader.tpl" title=__("addons.paypal.technical_details") target="#section_technical_details"}

<div id="section_technical_details">

    <div class="control-group">
        <label class="control-label cm-required" for="username">{__("username")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][username]" id="username" size="60" value="{$processor_params.username}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label cm-required" for="password">{__("password")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][password]" id="password" size="60" value="{$processor_params.password}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label cm-required" for="vendor">{__("paypal_vendor")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][vendor]" id="vendor" size="60" value="{$processor_params.vendor}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label cm-required" for="partner">{__("partner")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][partner]" id="partner" size="60" value="{$processor_params.partner}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="mode">{__("test_live_mode")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][mode]" id="mode">
                <option value="test"{if $processor_params.mode == "test"} selected="selected"{/if}>{__("test")}</option>
                <option value="live"{if $processor_params.mode == "live"} selected="selected"{/if}>{__("live")}</option>
            </select>
        </div>
    </div>

    {include file="common/subheader.tpl" title=__("3d_secure")}

    <div class="control-group">
        <label class="control-label" for="merchant_id">{__("merchant_id")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][merchant_id]" id="merchant_id" value="{$processor_params.merchant_id}" >
            <p class="muted description">{__("addons.paypal.3d_secure_mandatory_notice")}</p>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="processor_id">{__("processor_id")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][processor_id]" id="processor_id" value="{$processor_params.processor_id}" >
            <p class="muted description">{__("addons.paypal.3d_secure_mandatory_notice")}</p>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="transaction_password">{__("transaction_password")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][transaction_password]" id="transaction_password" value="{$processor_params.transaction_password}" >
            <p class="muted description">{__("addons.paypal.3d_secure_mandatory_notice")}</p>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="transaction_url">{__("transaction_url")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][transaction_url]" id="transaction_url" value="{$processor_params.transaction_url}" >
            <p class="muted description">{__("addons.paypal.3d_secure_mandatory_notice")}</p>
        </div>
    </div>
</div>
{/if}
