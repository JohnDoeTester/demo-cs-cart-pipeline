{include file="common/subheader.tpl" title=__("general") target="#general_{$payment_id}"}

<div id="general_{$payment_id}">
    <div class="control-group">
        <label class="control-label cm-required" for="api_username_{$payment_id}">{__("pay360.api_username")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][api_username]" id="api_username_{$payment_id}" value="{$processor_params.api_username}"  size="60">
        </div>
    </div>

    <div class="control-group">
        <label class="control-label cm-required" for="api_password_{$payment_id}">{__("pay360.api_password")}:</label>
        <div class="controls">
            <input type="password" name="payment_data[processor_params][api_password]" id="api_password_{$payment_id}" value="{$processor_params.api_password}" size="60">
        </div>
    </div>

    <div class="control-group">
        <label class="control-label cm-required" for="inst_id_{$payment_id}">{__("pay360.installation_id")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][inst_id]" id="inst_id_{$payment_id}" value="{$processor_params.inst_id}" size="60">
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="test_{$payment_id}">{__("test_live_mode")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][test]" id="test_{$payment_id}">
                <option value="N" {if $processor_params.test === "YesNo::NO"|enum}selected="selected"{/if}>{__("live")}</option>
                <option value="Y" {if $processor_params.test === "YesNo::YES"|enum}selected="selected"{/if}>{__("test")}</option>
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="currency_{$payment_id}">{__("currency")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][currency]" id="currency_{$payment_id}">
                {foreach $currencies as $code => $currency}
                    <option value="{$code}"
                            {if $processor_params.currency === $code}selected="selected"{/if}
                    >{$currency.description}</option>
                {/foreach}
            </select>
        </div>
    </div>
</div>

{include file="common/subheader.tpl" title=__("pay360.mandatory_fields_commentary") target="#optional_settings_{$payment_id}"}

<div id="optional_settings_{$payment_id}">
    <div class="control-group">
        <label class="control-label" for="account_number_{$payment_id}">{__("account_number")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][account_number]" id="account_number_{$payment_id}" value="{$processor_params.account_number}" maxlength="10" size="60">
            <p class="muted description">{__("pay360.account_number_description")}</p>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label cm-regexp"  data-ca-regexp="^[A-Za-z0-9]*$" data-ca-message="{__("pay360.use_only_alphabet_symbols_and_numbers")|escape:javascript}" for="postal_code_{$payment_id}">{__("zip_postal_code")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][postal_code]" id="postal_code_{$payment_id}" value="{$processor_params.postal_code}" maxlength="6" size="60">
            <p class="muted description">{__("pay360.postal_code_description")}</p>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label cm-regexp"  data-ca-regexp="^[A-Za-z]*$" data-ca-message="{__("pay360.use_only_alphabet_symbols")|escape:javascript}" for="surname_{$payment_id}">{__("last_name")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][surname]" id="surname_{$payment_id}" value="{$processor_params.surname}" maxlength="6" size="60">
            <p class="muted description">{__("pay360.surname_description")}</p>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="date_of_birth_{$payment_id}">{__("date_of_birth")}:</label>
        <div class="controls">
            {include file="common/calendar.tpl"
                date_id="date_of_birth_{$payment_id}"
                date_name="payment_data[processor_params][date_of_birth]"
                date_val="{$processor_params.date_of_birth}"
            }
        </div>
    </div>
</div>