{*
    $item_ids                   array                   List of profile ID
    $picker_id                  string                  Picker unique ID
    $input_name                 string                  Select input name
    $multiple                   bool                    Whether to multiple selection
    $show_advanced              bool                    Show advanced button
    $autofocus                  bool                    Whether to auto focus on input
    $autoopen                   bool                    Whether to auto open dropdown
    $empty_variant_text         string                  Empty variant text
    $view_mode                  enum (simple|external)  View mode
    $meta                       string                  Object picker class
    $select_group_class         string                  Select group class
    $simple_class               string                  Simple class
    $select_class               string                  Select class
*}

{$picker_id = $picker_id|default:uniqid()}
{$picker_text_key = $picker_text_key|default:"value"}
{$input_name = $input_name|default:"object_picker_simple_`$picker_id`"}
{$multiple = $multiple|default:false}
{$show_advanced = $show_advanced|default:true}
{$autofocus = $autofocus|default:false}
{$autoopen = $autoopen|default:false}
{$item_ids = $item_ids|default:[]|array_filter}
{$ids="ids[]="|implode:$item_ids}
{$url = $url|default:"profiles.get_manager_list?company_id=`$company_id`&ids[]=`$ids`"}
{$dropdown_css_class=$dropdown_css_class|default:"select2-dropdown-profiles"}

{if $multiple && $show_advanced}
    {$empty_variant_text = $empty_variant_text|default:__("type_to_search_or_click_button")}
{else}
    {$empty_variant_text = $empty_variant_text|default:__("type_to_search")}
{/if}

{if $show_empty_variant}
    {$predefined_variants[]=["id" => 0, "text" => $empty_variant_text]}
{/if}

<div class="object-picker {if $view_mode == "external"}object-picker--external{/if} object-picker--profiles {$meta}" data-object-picker="object_picker_{$picker_id}">
    <div class="object-picker__select-group object-picker__select-group--profiles {$select_group_class}">
        {if $show_advanced}
            <div class="object-picker__advanced object-picker__advanced--profiles {$advanced_class}">
                {include file="pickers/users/picker.tpl" 
                    display="radio" 
                    but_meta="object-picker__advanced-btn object-picker__advanced-btn--profiles `$object_picker_advanced_btn_class` btn" 
                    but_icon="icon-reorder"
                    extra_url=$extra_url 
                    view_mode="button" 
                    user_info=$order_info.issuer_data 
                    data_id="issuer_info" 
                    input_name="update_order[issuer_id]"
                    show_but_text=false
                    picker_id="object_picker_advanced_{$picker_id}"
                    extra_var=$extra_var|default:""
                    shared_force=$users_shared_force
                    no_container=$no_container|default:false 
                }
            </div>
        {/if}
        
        <div class="object-picker__simple {if $type == "list"}object-picker__simple--list{/if} {if $show_advanced}object-picker__simple--advanced{/if} object-picker__simple--profiles {$simple_class}">
            <select {if $multiple}multiple{/if}
                    name="{$input_name}"
                    class="cm-object-picker object-picker__select object-picker__select--profiles {$select_class}"
                    data-ca-object-picker-object-type="profiles"
                    data-ca-object-picker-dropdown-css-class={$dropdown_css_class}
                    data-ca-object-picker-escape-html="false"
                    data-ca-object-picker-ajax-url="{$url|fn_url}"
                    data-ca-object-picker-ajax-delay="250"
                    data-ca-object-picker-autofocus="{$autofocus|to_json}"
                    data-ca-object-picker-autoopen="{$autoopen}"
                    data-ca-dispatch="{$submit_url}"
                    data-ca-target-form="{$submit_form}"
                    data-ca-object-picker-placeholder="{$empty_variant_text|escape:"javascript"}"
                    data-ca-object-picker-placeholder-value=""
                    data-ca-object-picker-width="{$width}"
                    data-ca-object-picker-extended-picker-id="object_picker_advanced_{$picker_id}"
                    data-ca-object-picker-extended-picker-text-key="{$picker_text_key}"
                    {if $predefined_variants}
                        data-ca-object-picker-predefined-variants="{$predefined_variants|to_json}"
                    {/if}
                    {if $view_mode == "external"}
                        data-ca-object-picker-external-container-selector="#object_picker_external_seleceted_profiles_{$picker_id}"
                    {/if}
            >
                {foreach $item_ids as $item_id}
                    <option value="{$item_id}" selected="selected"></option>
                {/foreach}
            </select>
        </div>
    </div>
</div>