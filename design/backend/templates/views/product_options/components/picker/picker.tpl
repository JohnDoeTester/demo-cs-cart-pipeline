{*
    $item_ids                   array                   List of product option ID
    $picker_id                  string                  Picker unique ID
    $input_name                 string                  Select input name
    $multiple                   bool                    Whether to multiple selection
    $autofocus                  bool                    Whether to auto focus on input
    $autoopen                   bool                    Whether to auto open dropdown
    $allow_clear                bool                    Show clear button
    $allow_add                  bool                    Enable add option
    $create_option_to_end       bool                    Insert the new option at the end of the results
    $empty_variant_text         string                  Empty variant text
    $meta                       string                  Object picker class
    $select_group_class         string                  Select group class
    $simple_class               string                  Simple class
    $select_class               string                  Select class
    $dropdown_css_class         string                  Dropdown class
*}

{$picker_id = $picker_id|default:uniqid()}
{$input_id = $input_id|default:"object_picker_simple_`$picker_id`"}
{$input_name = $input_name|default:"object_picker_simple_`$picker_id`"}
{$multiple = $multiple|default:false}
{$autofocus = $autofocus|default:false}
{$allow_clear = $allow_clear|default:false}
{$create_option_to_end = $create_option_to_end|default:"false"}
{$item_ids = $item_ids|default:[]|array_filter}
{$dropdown_css_class = "object-picker__dropdown object-picker__dropdown--product-options `$dropdown_css_class`"|default:"object-picker__dropdown object-picker__dropdown--product-options"}

{$empty_variant_text = $empty_variant_text|default:__("none")}

{$meta = "cm-object-product-options-add-container `$meta`"}

<div class="object-picker object-picker--product-options {$meta}" data-object-picker="object_picker_{$picker_id}">
    <div class="object-picker__select-group object-picker__select-group--product-options {$select_group_class}">
        <div class="object-picker__simple {if $type == "list"}object-picker__simple--list{/if} object-picker__simple--product-options {$simple_class}">
            <select
                    {if $multiple}
                        multiple
                    {/if}
                    id="{$input_id}"
                    name="{$input_name}"
                    {if $form}
                        form="{$form}"
                    {/if}
                    class="cm-object-picker object-picker__select object-picker__select--product-options {$select_class}"
                    data-ca-object-picker-object-type="product_options"
                    data-ca-object-picker-escape-html="false"
                    data-ca-object-picker-ajax-url="{"product_options.get_available_options_list?product_id=`$smarty.request.product_id`"|fn_url nofilter}"
                    data-ca-object-picker-ajax-delay="250"
                    data-ca-object-picker-autofocus="{$autofocus|to_json}"
                    {if $autoopen}
                        data-ca-object-picker-autoopen="{$autoopen}"
                    {/if}
                    {if $submit_url}
                        data-ca-dispatch="{$submit_url}"
                    {/if}
                    {if $submit_form}
                        data-ca-target-form="{$submit_form}"
                    {/if}
                    data-ca-object-picker-placeholder="{$empty_variant_text|escape:"javascript"}"
                    data-ca-object-picker-placeholder-value=""
                    {if $width}
                        data-ca-object-picker-width="{$width}"
                    {/if}
                    data-ca-object-picker-dropdown-css-class="{$dropdown_css_class}"
                    {if $show_empty_variant}
                        data-ca-object-picker-allow-clear="{$allow_clear}"
                        data-ca-object-picker-predefined-variants="{[["id" => 0, "text" => {$empty_variant_text}]]|to_json}"
                    {/if}
                    {if $allow_add}
                        data-ca-object-picker-enable-create-object="true"
                        data-ca-object-picker-template-result-new-selector="#product_options_picker_result_new_selector_template_{$picker_id}"
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
    <script type="text/template" id="product_options_picker_result_new_selector_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
        <div class="object-picker__results object-picker__results--options object-picker__results--new">
            {include file="views/product_options/components/picker/item.tpl" title_pre=__("add")}
        </div>
    </script>
{/if}
