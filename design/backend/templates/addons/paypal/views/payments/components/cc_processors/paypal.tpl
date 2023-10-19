{if $addons.paypal.status == "D"}
    <div class="alert alert-block">
	<p>{__("paypal.addon_is_disabled_notice") nofilter}</p>
    </div>
{else}

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
    <label class="control-label" for="order_prefix">{__("order_prefix")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][order_prefix]" id="order_prefix" value="{$processor_params.order_prefix}" >
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="elm_new_order_status">{__("addons.paypal.status_for_new_orders")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][new_order_status]" id="elm_new_order_status">
            <option value="O" {if $processor_params.new_order_status == "O"}selected="selected"{/if}>{__("open")}</option>
            <option value="{"OrderStatuses::INCOMPLETED"|enum}" {if $processor_params.new_order_status === "OrderStatuses::INCOMPLETED"|enum}selected="selected"{/if}>{__("incompleted")}</option>
        </select>
        <p class="muted description">{__("ttc_addons.paypal.status_for_new_orders")}</p>
    </div>
</div>

<div class="control-group">
    <label class="control-label"></label>
    <div class="controls">
        <p id="txt_new_order_status_open"
           class="{if $processor_params.new_order_status != ""}hidden{/if}"
        >{__("addons.paypal.status_for_new_orders.open") nofilter}</p>
        <p id="txt_new_order_status_incomplete"
           class="{if $processor_params.new_order_status !== "OrderStatuses::INCOMPLETED"|enum}hidden{/if}"
        >{__("addons.paypal.status_for_new_orders.incomplete") nofilter}</p>
    </div>
</div>

{include file="common/subheader.tpl" title=__("addons.paypal.technical_details") target="#section_technical_details"}

<div id="section_technical_details">

    <div class="control-group">
        <label class="control-label" for="account">{__("account")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][account]" id="account" value="{$processor_params.account}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="item_name">{__("paypal_item_name")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][item_name]" id="item_name" value="{$processor_params.item_name}" >
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

    <input type="hidden" name="payment_data[processor_params][authentication_method]" id="authentication_method{$suffix}" value="signature" >

    <div class="control-group">
        <label class="control-label" for="signature{$suffix}">{__("signature")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][signature]" id="signature{$suffix}" value="{$processor_params.signature}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="mode">{__("test_live_mode")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][mode]" id="mode">
                <option value="test" {if $processor_params.mode == "test"}selected="selected"{/if}>{__("test")}</option>
                <option value="live" {if $processor_params.mode == "live"}selected="selected"{/if}>{__("live")}</option>
            </select>
        </div>
    </div>
</div>

<script>
    (function(_, $) {
        $('#elm_new_order_status').change(function() {
            $('#txt_new_order_status_open,#txt_new_order_status_incomplete').toggleClass('hidden');
        });
    })(Tygh, Tygh.$);
</script>
{/if}
