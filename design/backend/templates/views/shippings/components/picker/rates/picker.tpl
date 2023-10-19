{$picker_id = $picker_id|default:uniqid()}
{$multiple = $multiple|default:false}
{$item_ids = $item_ids|default:[]|array_filter}
{$view_only = $view_only|default:true}

<div class="object-picker__simple object-picker__simple--shipping-rates">
    <select {if $multiple}multiple{/if}
            class="cm-object-picker object-picker__select-native"
            data-ca-object-picker-object-type="shipping-rates"
            data-ca-object-picker-escape-html="false"
            data-ca-object-picker-ajax-url="{"destinations.selector?shipping_id={$shipping_id}"|fn_url}"
            data-ca-object-picker-ajax-delay="250"
            data-ca-object-picker-template-result-selector="#shipping_rates_picker_result_template_{$picker_id}"
            data-ca-object-picker-template-selection-selector="#shipping_rates_picker_selection_template_{$picker_id}"
            data-ca-object-picker-template-selection-load-selector="#shipping_rates_picker_selection_load_template_{$picker_id}"
            {if $view_mode === "external"}
                data-ca-object-picker-external-container-selector="#shipping_rates_picker_external_selected_rates_{$picker_id}"
            {/if}
            data-ca-object-picker-placeholder="{__("type_to_search")}"
            data-ca-object-picker-autofocus="false"
    >
        {foreach $item_ids as $item_id}
            <option value="{$item_id}" selected="selected"></option>
        {/foreach}
    </select>
    {if !$view_only}
        <a class="btn cm-ajax shipping-rate__add-all" data-ca-target-id="dashboard_shipping_rate" href="{"shippings.update?shipping_id={$shipping_id}&selected_section=shipping_charges&add_all_destinations"|fn_url}">
            {__("add_all_destinations")}
        </a>
    {/if}
</div>

{if $view_mode === "external"}
    <div class="object-picker__selected-external-container">
        <div id="shipping_rates_picker_external_selected_rates_{$picker_id}" class="object-picker__selected-external object-picker__selected-external--shipping-rates">
            {foreach $item_ids as $item_id}
                <div class="object-picker__skeleton object-picker__skeleton--shipping-rates" data-data="{["id" => $item_id]|to_json}">
                    <div class="object-picker__skeleton object-picker__skeleton--shipping-rates">...</div>
                </div>
            {/foreach}
        </div>
    </div>
{/if}

<script type="text/template" id="shipping_rates_picker_result_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__result object-picker__result--shipping-rates">
        {include file="views/shippings/components/picker/rates/item.tpl"}
    </div>
</script>

<script type="text/template" id="shipping_rates_picker_selection_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__selection-external object-picker__selection-external--shipping-rates cm-object-picker-object">
        {include file="views/shippings/components/picker/rates/item_selection.tpl"}
    </div>
</script>

<script type="text/template" id="shipping_rates_picker_selection_load_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__skeleton object-picker__skeleton--shipping-rates">...</div>
</script>
