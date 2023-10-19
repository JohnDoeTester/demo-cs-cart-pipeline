{*
    $item_ids                   array                   List of product ID
    $picker_id                  string                  Picker unique ID
    $input_name                 string                  Select input name
    $multiple                   bool                    Whether to multiple selection
    $show_advanced              bool                    Show advanced button
    $show_positions             bool                    Show product position
    $autofocus                  bool                    Whether to auto focus on input
    $autoopen                   bool                    Whether to auto open dropdown
    $allow_clear                bool                    Show clear button
    $empty_variant_text         string                  Empty variant text
    $view_mode                  enum (simple|external)  View mode
    $meta                       string                  Object picker class
    $select_group_class         string                  Select group class
    $advanced_class             string                  Advanced class
    $simple_class               string                  Simple class
    $select_class               string                  Select class
    $selected_external_class    string                  Selected external class
    $selection_class            string                  Selection class
    $result_class               string                  Result class
    $additional_query_params    string                  Additional parameters for product search
    $segment                    string                  Additional parameter for product advanced search
*}

{$picker_id = $picker_id|default:uniqid()}
{$picker_text_key = $picker_text_key|default:"value"}
{$input_name = $input_name|default:"object_picker_simple_`$picker_id`"}
{$multiple = $multiple|default:false}
{$show_advanced = $show_advanced|default:true}
{$autofocus = $autofocus|default:false}
{$autoopen = $autoopen|default:false}
{$allow_clear = $allow_clear|default:false}
{$show_positions  = $show_positions|default:false}
{$item_ids = $item_ids|default:[]|array_filter}
{$for_current_storefront = $for_current_storefront|default:false}
{$advanced_picker_id=$advanced_picker_id|default:"object_picker_advanced_`$picker_id`"}
{$additional_query_params = $additional_query_params|default:""}
{$show_in_stock = $show_in_stock|default:true}

{if $multiple && $show_advanced}
    {$empty_variant_text = $empty_variant_text|default:__("type_to_search_or_click_button")}
{else}
    {$empty_variant_text = $empty_variant_text|default:__("none")}
{/if}

{$meta = "cm-object-product-add-container `$meta`"}

<div class="object-picker {if $view_mode == "external"}object-picker--external{/if} object-picker--product {$meta}" data-object-picker="object_picker_{$picker_id}">
    <div class="object-picker__select-group object-picker__select-group--products {$select_group_class}">
        {if $show_advanced}
            <div class="object-picker__advanced object-picker__advanced--products {$advanced_class}">
                {include file="pickers/products/picker.tpl"
                    picker_id="{$advanced_picker_id}"
                    data_id="om"
                    no_container=true
                    icon="icon-reorder"
                    but_text="{__("advanced_products_search")}"
                    show_but_text=false
                    view_mode="button"
                    meta="object-picker__advanced-btn object-picker__advanced-btn--products `$object_picker_advanced_btn_class`"
                    segment=$segment
                    additional_query_params=$additional_query_params
                }
            </div>
        {/if}
        <div class="object-picker__simple {if $type == "list"}object-picker__simple--list{/if} {if $show_advanced}object-picker__simple--advanced{/if} object-picker__simple--products {$simple_class}">
            <select {if $multiple}multiple{/if}
                    name="{$input_name}"
                    class="cm-object-picker object-picker__select object-picker__select--products {$select_class}"
                    data-ca-object-picker-object-type="product"
                    data-ca-object-picker-escape-html="false"
                    data-ca-object-picker-ajax-url="{"products.get_products_list?{if $additional_query_params}{$additional_query_params}{/if}{if $for_current_storefront}&for_current_storefront={$for_current_storefront}{/if}"|fn_url}"
                    data-ca-object-picker-ajax-delay="250"
                    data-ca-object-picker-template-result-selector="#object_picker_result_template_{$picker_id}"
                    data-ca-object-picker-template-selection-selector="#object_picker_selection_template_{$picker_id}"
                    data-ca-object-picker-template-selection-load-selector="#object_picker_selection_load_template_{$picker_id}"
                    data-ca-object-picker-autofocus="{$autofocus|to_json}"
                    data-ca-object-picker-autoopen="{$autoopen}"
                    data-ca-dispatch="{$submit_url}"
                    data-ca-target-form="{$submit_form}"
                    data-ca-object-picker-placeholder="{$empty_variant_text|escape:"javascript"}"
                    data-ca-object-picker-placeholder-value=""
                    data-ca-object-picker-width="{$width}"
                    data-ca-object-picker-extended-picker-id="{$advanced_picker_id}"
                    data-ca-object-picker-extended-picker-text-key="{$picker_text_key}"
                    {if $view_mode == "external"}
                        data-ca-object-picker-external-container-selector="#object_picker_selected_external_{$picker_id}"
                    {/if}
                    {if $show_empty_variant}
                        data-ca-object-picker-allow-clear="{$allow_clear}"
                        data-ca-object-picker-predefined-variants="{[["id" => 0, "text" => {$empty_variant_text}]]|to_json}"
                    {/if}
            >
                {foreach $item_ids as $item_id}
                    <option value="{$item_id}" selected="selected"></option>
                {/foreach}
            </select>
        </div>
    </div>

    {if $view_mode == "external"}
        <div id="object_picker_selected_external_{$picker_id}" class="object-picker__selected-external {$selected_external_class}">
            {foreach $item_ids as $item_id}
                <div class="object-picker__skeleton object-picker__skeleton--products {$selection_class}" data-data="{["id" => $item_id]|to_json}">
                    {include file="views/products/components/picker/skeleton_external.tpl"}
                </div>
            {/foreach}
        </div>
        <p class="no-items object-picker__selected-external-not-items">{__("no_data")}</p>
    {/if}
</div>

<script type="text/template" id="object_picker_result_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__result object-picker__result--products {$result_class}{if $show_simple_product} object-picker__result--simple-products{/if}">
        {include file="views/products/components/picker/item_extended.tpl"
            type="result"
            title_pre={$result_title_pre}
            title_post={$result_title_post}
        }
    </div>
</script>

{if $view_mode == "external"}
    <script type="text/template" id="object_picker_selection_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
        <div class="cm-object-picker-object object-picker__selection-extended object-picker__selection-extended--products">
            {if $show_positions}
                {include file="views/products/components/picker/item_position.tpl"}
            {/if}
            {include file="views/products/components/picker/item_extended.tpl"
                type="selection"
                title_pre={$selection_title_pre}
                title_post={$selection_title_post}
            }
            {include file="buttons/button.tpl"
                but_role="button-icon"
                but_meta="btn cm-object-picker-remove-object object-picker__products-delete"
                but_icon="icon-remove object-picker__products-delete-icon"
                title=__("delete")
            }
        </div>
    </script>
{else}
    <script type="text/template" id="object_picker_selection_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
        <div class="cm-object-picker-object object-picker__selection object-picker__selection--products{if $show_simple_product} object-picker__selection--simple-products{/if}">
            {include file="views/products/components/picker/item.tpl"
                type="selection"
                title_pre={$selection_title_pre}
                title_post={$selection_title_post}
            }
        </div>
    </script>
{/if}

<script type="text/template" id="object_picker_selection_load_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__skeleton object-picker__skeleton--products">
        {include file="views/products/components/picker/skeleton_external.tpl"}
    </div>
</script>