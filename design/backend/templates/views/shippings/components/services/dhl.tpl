<fieldset>

<div class="control-group">
    <label class="control-label" for="ship_dhl_system_id">{__("ship_dhl_site_id")}</label>
    <div class="controls">
    <input id="ship_dhl_system_id" type="text" name="shipping_data[service_params][system_id]" size="30" value="{$shipping.service_params.system_id}"/>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="password">{__("password")}</label>
    <div class="controls">
    <input id="password" type="text" name="shipping_data[service_params][password]" size="30" value="{$shipping.service_params.password}"/>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="account_number">{__("account_number")}</label>
    <div class="controls">
        <input id="account_number" type="text" name="shipping_data[service_params][account_number]" size="30" value="{$shipping.service_params.account_number}"/>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="country">{__("ship_dhl_account_country")}</label>
    <div class="controls">
        <select class="cm-country cm-location-billing" id="country" name="shipping_data[service_params][account_country]">
            <option value="">- {__("select_country")} -</option>
            {$countries = ""|fn_get_simple_countries}
            {foreach $countries as $code => $country}
                <option value="{$code}" {if $code == $shipping.service_params.account_country}selected="selected"{/if}>{$country}</option>
            {/foreach}
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="test_mode">{__("test_mode")}</label>
    <div class="controls">
    <input type="hidden" name="shipping_data[service_params][test_mode]" value="N" />
    <input id="test_mode" type="checkbox" name="shipping_data[service_params][test_mode]" value="Y" {if $shipping.service_params.test_mode == "Y"}checked="checked"{/if} />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="system_of_measurement">{__("system_of_measurement")}</label>
    <div class="controls">
        <select id="system_of_measurement" name="shipping_data[service_params][system_of_measurement]">
            <option value="{"\Tygh\Shippings\Services\Dhl::IMPERIAL_UNITS"|constant}" {if $shipping.service_params.system_of_measurement == "\Tygh\Shippings\Services\Dhl::IMPERIAL_UNITS"|constant}}selected{/if}>{__("imperial_units")}</option>
            <option value="{"\Tygh\Shippings\Services\Dhl::METRIC_UNITS"|constant}" {if $shipping.service_params.system_of_measurement == "\Tygh\Shippings\Services\Dhl::METRIC_UNITS"|constant}selected{/if}>{__("metric_units")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="max_weight">{__("max_box_weight")}</label>
    <div class="controls">
    <input id="max_weight" type="text" name="shipping_data[service_params][max_weight_of_box]" size="30" value="{$shipping.service_params.max_weight_of_box|default:0}"/>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="ship_dhl_length">{__("ship_dhl_length")}</label>
    <div class="controls">
    <input id="ship_dhl_length" type="text" name="shipping_data[service_params][length]" size="30" value="{$shipping.service_params.length}"/>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="ship_dhl_width">{__("ship_dhl_width")}</label>
    <div class="controls">
    <input id="ship_dhl_width" type="text" name="shipping_data[service_params][width]" size="30" value="{$shipping.service_params.width}"/>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="ship_dhl_height">{__("ship_dhl_height")}</label>
    <div class="controls">
    <input id="ship_dhl_height" type="text" name="shipping_data[service_params][height]" size="30" value="{$shipping.service_params.height}"/>
    </div>
</div>

</fieldset>