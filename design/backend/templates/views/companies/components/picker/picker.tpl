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
{$close_on_select = $close_on_select|default:true}
{$select_id=$select_id|default:"companies_add_`$picker_id`"}
{$predefined_variants = $predefined_variants|default:[]}

{if $multiple && $show_advanced}
    {$empty_variant_text = $empty_variant_text|default:__("type_to_search_or_click_button")}
{else}
    {$empty_variant_text = $empty_variant_text|default:__("none")}
{/if}

{if $show_empty_variant}
    {$predefined_variants["0"] = $empty_variant_text}
{/if}

{foreach $predefined_variants as $id => $variant}
    {$predefined_variant_items[] = ["id" => $id, "text" => $variant, "data" => ["name" => $variant]]}
{/foreach}

<div class="object-picker {if $view_mode == "external"}object-picker--external{/if} object-picker--companies {$meta}" data-object-picker="object_picker_{$picker_id}">
    <div class="object-picker__select-group object-picker__select-group--companies {$select_group_class}">
        <div class="object-picker__simple {if $type == "list"}object-picker__simple--list{/if} {if $show_advanced}object-picker__simple--advanced{/if} object-picker__simple--companies {$simple_class}">
            <select {if $multiple}multiple{/if}
                    id="{$select_id}"
                    name="{$input_name}"
                    class="cm-object-picker object-picker__select object-picker__select--companies {$select_class}"
                    data-ca-object-picker-object-type="company"
                    data-ca-object-picker-escape-html="false"
                    data-ca-object-picker-ajax-url="{"companies.get_companies_list"|fn_url}"
                    data-ca-object-picker-ajax-delay="250"
                    data-ca-object-picker-template-selection-selector="#object_picker_selection_template_{$picker_id}"
                    data-ca-object-picker-template-selection-load-selector="#object_picker_selection_load_template_{$picker_id}"
                    data-ca-object-picker-autofocus="{$autofocus|to_json}"
                    data-ca-object-picker-close-on-select="{$close_on_select|to_json}"
                    data-ca-object-picker-autoopen="{$autoopen}"
                    data-ca-dispatch="{$submit_url}"
                    data-ca-target-form="{$submit_form}"
                    data-ca-object-picker-placeholder="{$empty_variant_text|escape:"javascript"}"
                    data-ca-object-picker-placeholder-value=""
                    data-ca-object-picker-width="{$width}"
                    data-ca-object-picker-extended-picker-id="object_extended_picker_{$picker_id}"
                    data-ca-object-picker-extended-picker-text-key="{$picker_text_key}"
                    {if $view_mode == "external"}
                        data-ca-object-picker-external-container-selector="#object_picker_external_seleceted_companies_{$picker_id}"
                    {/if}
                    {if $dropdown_parent_selector}
                        data-ca-object-picker-dropdown-parent-selector="{$dropdown_parent_selector}"
                    {/if}
                    {if $predefined_variant_items}
                        data-ca-object-picker-allow-clear="{$allow_clear}"
                        data-ca-object-picker-predefined-variants="{$predefined_variant_items|to_json}"
                    {/if}
            >
                {foreach $item_ids as $item_id}
                    <option value="{$item_id}" selected="selected"></option>
                {/foreach}
            </select>
        </div>
    </div>
</div>

<script type="text/template" id="object_picker_selection_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="cm-object-picker-object object-picker__selection {if $type == "list"}object-picker__selection--list{/if} object-picker__selection--companies">
        {include file="views/companies/components/picker/item.tpl"
            type="selection"
            title_pre={$selection_title_pre}
            title_post={$selection_title_post}
        }
    </div>
</script>

<script type="text/template" id="object_picker_selection_load_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__skeleton object-picker__skeleton--companies">...
    </div>
</script>