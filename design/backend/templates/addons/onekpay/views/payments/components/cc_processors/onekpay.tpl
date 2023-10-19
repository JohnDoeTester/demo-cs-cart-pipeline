<p>{__("onekpay.short_notice") nofilter}</p>
<hr>

<div class="control-group">
    <label class="control-label" for="mode">{__("test_live_mode")}:</label>
    <div class="controls">
	<select name="payment_data[processor_params][mode]" id="mode">
	    <option value="test" {if $processor_params.mode == "test"}selected="selected"{/if}>{__("test")}</option>
	    <option value="live" {if $processor_params.mode == "live"}selected="selected"{/if}>{__("live")}</option>
	</select>
    </div>
</div>

<div class="control-group">
    <label class="control-label cm-required" for="onekpay_merchant_number">{__("onekpay.merchant_number")}:</label>
    <div class="controls">
	<input type="text" name="payment_data[processor_params][onekpay_merchant_number]" id="onekpay_merchant_number" value="{$processor_params.onekpay_merchant_number}" size="60">
    </div>
</div>

<div class="control-group">
    <label class="control-label cm-required" for="onekpay_terminal_number">{__("onekpay.terminal_number")}:</label>
    <div class="controls">
	<input type="text" name="payment_data[processor_params][onekpay_terminal_number]" id="onekpay_terminal_number" value="{$processor_params.onekpay_terminal_number}" size="60">
    </div>
</div>

<div class="control-group">
    <label class="control-label cm-required" for="onekpay_secure_hash">{__("onekpay.secure_hash")}:</label>
    <div class="controls">
	<input type="text" name="payment_data[processor_params][onekpay_secure_hash]" id="onekpay_secure_hash" value="{$processor_params.onekpay_secure_hash}" size="60">
    </div>
</div>
