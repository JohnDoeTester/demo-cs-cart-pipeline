{*
string $picker_id       Unique picker identifier
bool   $autofocus       Whether the picker input must be automatically focused
bool   $multiple        Whether the picker allows multiple selection
int[]  $item_ids        Selected destination identifiers
*}
{$picker_id = $picker_id|default:uniqid()}
{$autofocus = $autofocus|default:false}
{$multiple = $multiple|default:false}
{$item_ids = $item_ids|default:[]|array_filter}

<div class="object-picker__simple object-picker__simple--destinations">
    <input type="hidden"
           name="store_location_data[shipping_destinations]"
           value=""
    />
    <select {if $multiple}multiple{/if}
            name=""
            class="cm-object-picker object-picker__select-native"
            data-ca-object-picker-object-type="destination"
            data-ca-object-picker-escape-html="false"
            data-ca-object-picker-ajax-url="{"destinations.picker?store_location_id={$store_location.store_location_id}"|fn_url}"
            data-ca-object-picker-ajax-delay="250"
            data-ca-object-picker-template-result-selector="#destination_picker_result_template_{$picker_id}"
            data-ca-object-picker-template-selection-selector="#destination_picker_selection_template_{$picker_id}"
            data-ca-object-picker-template-selection-load-selector="#destination_picker_selection_load_template_{$picker_id}"
            {if $view_mode === "external"}
                data-ca-object-picker-external-container-selector="#destination_picker_external_selected_destinations_{$picker_id}"
            {/if}
            data-ca-object-picker-placeholder="{__("type_to_search")}"
            data-ca-object-picker-autofocus="false"
    >
        {foreach $item_ids as $item_id}
            <option value="{$item_id}" selected="selected"></option>
        {/foreach}
    </select>
    <a class="btn cm-ajax cm-hide-with-inputs" data-ca-target-id="show_to" href="{"store_locator.update?store_location_id={$store_location.store_location_id}&selected_section=pickup&add_all_destinations"|fn_url}">
        {__("warehouses.add_all")}
    </a>
</div>

{if $view_mode === "external"}
    <div class="object-picker__selected-external-container">
        <div id="destination_picker_external_selected_destinations_{$picker_id}"
             class="object-picker__selected-external object-picker__selected-external--destination"
        >{strip}
                {foreach $item_ids as $item_id}
                    <div class="object-picker__skeleton object-picker__skeleton--destination" {""}
                         data-data="{["id" => $item_id]|to_json}"
                    >
                        {include file="addons/warehouses/components/picker/destinations/load.tpl"}
                    </div>
                {/foreach}
            {/strip}</div>
        <p class="object-picker__selected-external-not-items no-items">{__("no_data")}</p>
        <div class="object-picker__selected-header">
            <div class="object-picker__header-item object-picker__header-item--name">
                <div class="object-picker__name">
                    {__("warehouses.rate_area")}
                </div>
            </div>
            <div class="object-picker__header-item object-picker__header-item--shipping-delay">
                {__("warehouses.shipping_delay")}
                {include file="common/tooltip.tpl" tooltip=__("warehouses.shipping_delay.tooltip")}
            </div>
            <div class="object-picker__header-item object-picker__header-item--warn-about-delay">
                {__("warehouses.warn_about_delay")}
                {include file="common/tooltip.tpl" tooltip=__("warehouses.warn_about_delay.tooltip")}
            </div>
        </div>
    </div>
{/if}

<script type="text/template" id="destination_picker_result_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__result object-picker__result--destination">
        {include file="addons/warehouses/components/picker/destinations/item.tpl"
            type="result"
        }
    </div>
</script>

<script type="text/template" id="destination_picker_selection_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__selection-extended object-picker__selection-extended--table object-picker__selection-extended--destination cm-object-picker-object {literal}${data.additional_class}{/literal}">
        {include file="addons/warehouses/components/picker/destinations/item.tpl"
            type="selection"
        }
        {if $view_mode === "external"}
            <div class="object-picker__selection-extended-item object-picker__selection-extended-item--table" data-th="">
                {include file="buttons/button.tpl"
                    but_role="button-icon"
                    but_meta="btn cm-object-picker-remove-object object-picker__delete"
                    but_icon="icon-remove object-picker__delete-icon"
                    title=__("delete")
                }
            </div>
        {/if}
    </div>
</script>

<script type="text/template" id="destination_picker_selection_load_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__skeleton object-picker__skeleton--destination">
        {include file="addons/warehouses/components/picker/destinations/load.tpl"}
    </div>
</script>
