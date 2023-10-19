<p>{__("ogone_direct.config_info") nofilter}</p>
<hr>

<div class="control-group cm-required">
    <label class="control-label cm-required" for="pspid">{__("pspid")}:</label>
    <div class="controls">
        <input type="text"
               name="payment_data[processor_params][pspid]"
               id="pspid"
               size="60"
               value="{$processor_params.pspid}"
        />
    </div>
</div>

<div class="control-group">
    <label class="control-label cm-required" for="userid">{__("user_id")}:</label>
    <div class="controls">
        <input type="text"
               name="payment_data[processor_params][userid]"
               id="userid"
               size="60"
               value="{$processor_params.userid}"
        />
    </div>
</div>

<div class="control-group cm-required">
    <label class="control-label cm-required" for="password">{__("password")}:</label>
    <div class="controls">
        <input type="password"
               name="payment_data[processor_params][password]"
               id="password"
               size="60"
               value="{$processor_params.password}"
               autocomplete="new-password"
        />
    </div>
</div>

<div class="control-group cm-required">
    <label class="control-label cm-required" for="sha_sign">{__("ogone.sha_in")}:</label>
    <div class="controls">
        <input type="password"
               name="payment_data[processor_params][sha_sign]"
               id="sha_sign"
               size="60"
               value="{$processor_params.sha_sign}"
        />
        <p class="muted description">{__("ttc_ogone.sha_in") nofilter}</p>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="use_new_sha_method">{__("use_new_sha_method")}:</label>
    <div class="controls">
        <input type="hidden"
               name="payment_data[processor_params][use_new_sha_method]"
               value="N"
        />
        <input type="checkbox"
               name="payment_data[processor_params][use_new_sha_method]"
               id="use_new_sha_method"
               value="Y"
               {if $processor_params.use_new_sha_method|default:"Y" == "Y"}checked="checked"{/if}
        />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="currency">{__("currency")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][currency]" id="currency">
            {$supported_currencies = ["AUD", "CAD", "CHF", "CZK", "DKK", "EUR", "FRF", "GBP", "HKD", "HUF", "ILS", "JPY", "LTL", "LVL", "MXN", "NOK", "NZD", "PLN", "RUR", "SEK", "SGD", "SKK", "THB", "TRY", "USD", "ZAR"]}
            {foreach $supported_currencies as $currency_code}
                <option value="{$currency_code}"
                        {if $processor_params.currency == $currency_code}selected="selected"{/if}
                        {if !$currencies.$currency_code}disabled="disabled"{/if}
                >{__("currency_code_{$currency_code|lower}")}</option>
            {/foreach}
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="mode">{__("test_live_mode")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][mode]" id="mode">
            <option value="test"
                    {if $processor_params.mode == "test"}selected="selected"{/if}
            >{__("test")}</option>
            <option value="live"
                    {if $processor_params.mode == "live"}selected="selected"{/if}
            >{__("live")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="order_prefix">{__("order_prefix")}:</label>
    <div class="controls">
        <input type="text"
               name="payment_data[processor_params][order_prefix]"
               id="order_prefix"
               size="60"
               value="{$processor_params.order_prefix}"
        />
    </div>
</div>