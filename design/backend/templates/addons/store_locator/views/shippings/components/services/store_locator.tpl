{assign var="store_locator" value="store_locator.manage"|fn_url:'A'}

{include file="common/subheader.tpl" title=__("information") target="#pickup_instruction_`$smarty.request.shipping_id`"}
<div id="pickup_instruction_{$smarty.request.shipping_id}" class="in collapse">
{__("store_locator.text_pickup_instruction", ["[store_locator]" => $store_locator]) nofilter}
</div>

{include file="common/subheader.tpl" title=__("stores") target="#pickup_settings_`$smarty.request.shipping_id`"}
<input type="hidden" name="shipping_data[service_params][active_stores]" value="" />
<div id="pickup_settings_{$smarty.request.shipping_id}" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="elm_shipping_data_select_stores">{__("store_locator.select_stores")}</label>
        <div class="controls">
            {include file="addons/store_locator/components/custom_select_box.tpl" name="shipping_data[service_params][active_stores]" id="elm_shipping_data_select_stores" fields=$select_stores selected_fields=$active_stores selected_names=$all_stores}
        </div>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="sorting">{__("sort_by")}:</label>
    <div class="controls">
        <select name="shipping_data[service_params][sorting]" id="sorting">
            <option value="position" {if $shipping.service_params.sorting == "position"}selected="selected"{/if}>{__("store_locator.stores_position")}</option>
            <option value="shipping_position" {if $shipping.service_params.sorting == "shipping_position"}selected="selected"{/if}>{__("store_locator.shipping_position")}</option>
        </select>
    </div>
</div>

{include file="common/subheader.tpl" title=__("store_locator.display")}

<div class="control-group">
    <label class="control-label" for="display">{__("store_locator.display")}:</label>
    <div class="controls">
        <select name="shipping_data[service_params][display]" id="display">
            <option value="ML" {if $shipping.service_params.display == "ML"}selected="selected"{/if}>{__("store_locator.display_ml")}</option>
            <option value="M" {if $shipping.service_params.display == "M"}selected="selected"{/if}>{__("store_locator.display_m")}</option>
            <option value="L" {if $shipping.service_params.display == "L"}selected="selected"{/if}>{__("store_locator.display_l")}</option>
        </select>
    </div>
</div>

