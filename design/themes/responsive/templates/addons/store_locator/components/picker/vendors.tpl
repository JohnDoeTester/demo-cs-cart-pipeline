{*
    $item_ids                   array                   List of product ID
    $picker_id                  string                  Picker unique ID
    $input_name                 string                  Select input name
    $multiple                   bool                    Whether to multiple selection
    $show_advanced              bool                    Show advanced button
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
*}

{$picker_id = $picker_id|default:uniqid()}
{$picker_text_key = $picker_text_key|default:"value"}
{$input_name = $input_name|default:"object_picker_simple_`$picker_id`"}
{$multiple = $multiple|default:false}
{$show_advanced = $show_advanced|default:true}
{$autofocus = $autofocus|default:false}
{$autoopen = $autoopen|default:false}
{$allow_clear = $allow_clear|default:false}
{$item_ids = $item_ids|default:[]|array_filter}

{if $multiple && $show_advanced}
    {$empty_variant_text = $empty_variant_text|default:__("type_to_search_or_click_button")}
{else}
    {$empty_variant_text = $empty_variant_text|default:__("none")}
{/if}

<div class="object-picker {if $view_mode == "external"}object-picker--external{/if} object-picker--companies {$meta}" data-object-picker="object_picker_{$picker_id}">
    <div class="object-picker__select-group object-picker__select-group--companies {$select_group_class}">
        <div class="object-picker__simple {if $type == "list"}object-picker__simple--list{/if} {if $show_advanced}object-picker__simple--advanced{/if} object-picker__simple--companies {$simple_class}">
            <select {if $multiple}multiple{/if}
                    name="{$input_name}"
                    class="cm-object-picker object-picker__select object-picker__select--companies {$select_class}"
                    data-ca-object-picker-object-type="company"
                    data-ca-object-picker-escape-html="false"
                    data-ca-object-picker-ajax-url="{"companies.get_companies_list"|fn_url}"
                    data-ca-object-picker-ajax-delay="250"
                    data-ca-object-picker-autofocus="{$autofocus|to_json}"
                    data-ca-object-picker-autoopen="{$autoopen}"
                    data-ca-dispatch="{$submit_url}"
                    data-ca-target-form="{$submit_form}"
                    data-ca-object-picker-placeholder="{$empty_variant_text|escape:"javascript"}"
                    data-ca-object-picker-placeholder-value=""
                    data-ca-object-picker-width="{$width}"
                    data-ca-object-picker-extended-picker-id="object_extended_picker_{$picker_id}"
                    data-ca-object-picker-extended-picker-text-key="{$picker_text_key}"
                    {if $dropdown_parent_selector}
                        data-ca-object-picker-dropdown-parent-selector="{$dropdown_parent_selector}"
                    {/if}
                    {if $show_empty_variant}
                        data-ca-object-picker-allow-clear="{$allow_clear}"
                        data-ca-object-picker-predefined-variants="{[["id" => 0, "text" => {$empty_variant_text}, "data" => ["name" => {$empty_variant_text}]]]|to_json}"
                    {/if}
            >
                {foreach $item_ids as $item_id}
                    <option value="{$item_id}" selected="selected"></option>
                {/foreach}
            </select>
        </div>
    </div>
</div>