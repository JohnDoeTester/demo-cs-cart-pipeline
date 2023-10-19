{*
    $item_ids                   array                   List of product ID
    $picker_id                  string                  Picker unique ID
    $input_name                 string                  Select input name
    $multiple                   bool                    Whether to multiple selection
    $show_advanced              bool                    Show advanced button
    $autofocus                  bool                    Whether to auto focus on input
    $autoopen                   bool                    Whether to auto open dropdown
    $allow_clear                bool                    Show clear button
    $create_option_to_end       bool                    Insert the new option at the end of the results
    $empty_variant_text         string                  Empty variant text
    $meta                       string                  Object picker class
    $select_group_class         string                  Select group class
    $advanced_class             string                  Advanced class
    $simple_class               string                  Simple class
    $select_class               string                  Select class
    $selection_class            string                  Selection class
    $result_class               string                  Result class
    $close_on_select            bool                    Close dropdown after selection (default true)
    $dropdown_css_class         string                  Dropdown class
*}

{$picker_id = $picker_id|default:uniqid()}
{$input_name = $input_name|default:"object_picker_simple_`$picker_id`"}
{$multiple = $multiple|default:false}
{$show_advanced = $show_advanced|default:true}
{$autofocus = $autofocus|default:false}
{$autoopen = $autoopen|default:false}
{$close_on_select = $close_on_select|default:true}
{$allow_clear = $allow_clear|default:false}
{$hide_selection = $hide_selection|default:false}
{$create_option_to_end = $create_option_to_end|default:"false"}
{$item_ids = $item_ids|default:[]|array_filter}
{$search_data = $search_data|default:[]}
{$dropdown_css_class = "object-picker__dropdown object-picker__dropdown--product-features `$dropdown_css_class`"|default:"object-picker__dropdown object-picker__dropdown--product-features"}

{if $multiple && $show_advanced}
    {$empty_variant_text = $empty_variant_text|default:__("type_to_search_or_click_button")}
{else}
    {$empty_variant_text = $empty_variant_text|default:__("none")}
{/if}

{$url = "product_features.get_features_list?exclude_empty_groups=1"|fn_url}

{$meta = "cm-object-features-add-container `$meta`"}
{$predefined_variants = $predefined_variants|default:[]}
{$predefined_variant_items = []}

{if $show_empty_variant}
     {$predefined_variants["0"] = $empty_variant_text}
{/if}

{foreach $predefined_variants as $id => $variant}
    {$predefined_variant_items[] = ["id" => $id, "text" => $variant]}
{/foreach}

<div class="object-picker object-picker--features {$meta}" data-object-picker="object_picker_{$picker_id}">
    <div class="object-picker__select-group object-picker__select-group--features {$select_group_class}">
        <div class="object-picker__simple {if $type == "list"}object-picker__simple--list{/if} {if $show_advanced}object-picker__simple--advanced{/if} object-picker__simple--features {$simple_class}">
            <select {if $multiple}multiple{/if}
                    name="{$input_name}"
                    class="cm-object-picker object-picker__select object-picker__select--features {$select_class}"
                    data-ca-object-picker-object-type="assign_product_features_values"
                    data-ca-object-picker-escape-html="false"
                    data-ca-object-picker-ajax-url="{$url}"
                    data-ca-object-picker-ajax-delay="250"
                    data-ca-object-picker-autofocus="{$autofocus|to_json}"
                    data-ca-object-picker-autoopen="{$autoopen}"
                    data-ca-object-picker-close-on-select="{$close_on_select|to_json}"
                    data-ca-object-picker-search-request-data="{$search_data|to_json}"
                    data-ca-dispatch="{$submit_url}"
                    data-ca-target-form="{$submit_form}"
                    data-ca-object-picker-placeholder="{$empty_variant_text|escape:"javascript"}"
                    data-ca-object-picker-placeholder-value=""
                    data-ca-object-picker-width="{$width}"
                    data-ca-object-picker-dropdown-css-class="{$dropdown_css_class}"
                    data-ca-object-picker-template-result-selector="#product_features_picker_result_selector_{$picker_id}"
                    data-ca-object-picker-allow-clear="{$allow_clear}"
                    {if $allow_add}
                        data-ca-object-picker-enable-create-object="true"
                        data-ca-object-picker-template-result-new-selector="#product_features_picker_result_new_selector_template_{$picker_id}"
                        data-ca-object-picker-create-object-to-end="{$create_option_to_end}"
                    {/if}
                    {if $dropdown_parent_selector}
                        data-ca-object-picker-dropdown-parent-selector="{$dropdown_parent_selector}"
                    {/if}
                    {if $hide_selection}
                        data-ca-object-picker-hide-selection="true"
                    {/if}
                    {if $predefined_variant_items}
                        data-ca-object-picker-predefined-variants="{$predefined_variant_items|array_reverse|to_json}"
                        data-ca-object-picker-template-result-predefined-selector="#product_features_picker_result_predefined_template_{$picker_id}"
                    {/if}
            >
                {foreach $item_ids as $item_id}
                    <option value="{$item_id}" selected="selected"></option>
                {/foreach}
            </select>
        </div>
    </div>
</div>

{if $allow_add}
    <script type="text/template" id="product_features_picker_result_new_selector_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
        <div class="object-picker__results object-picker__results--features object-picker__results--new">
            {include file="views/product_features/components/picker/item.tpl" title_pre=__("add")}
        </div>
    </script>
{/if}

{if $predefined_variant_items}
    <script type="text/template" id="product_features_picker_result_predefined_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
        <div class="object-picker__selection-product-feature">
            <div class="object-picker__product-feature-label"> {literal}${data.text}{/literal}</div>
        </div>
    </script>
{/if}

<script type="text/template" id="product_features_picker_result_selector_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__selection-product-feature">
        <div class="object-picker__product-feature-label"> {literal}${data.internal_name}{/literal} <span class="object-picker__product-feature-description muted">{literal}${data.description}{/literal}</span></div>
    </div>
</script>
