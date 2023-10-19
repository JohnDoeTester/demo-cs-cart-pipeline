{*
    Picker for multiple select.

    $is_disabled                  bool   Whether picker is blocked
    $label_text                   string Input field label
    $placeholder                  string Input field placeholder
    $load_items_url               string URL to load data from
    $allow_add                    bool   Whether it's allowed to create new variants for picker
    $template_result_new_selector string `templateResult` selector
    $template_result_selector     string `templateSelection` selector
    $items                        array  All variants
    $item_ids                     array  Selected picker variants
    $type                         string Picker type
    $storefront_id                int    Storefront ID
    $input_name                   string Select element name
    $close_on_select              bool   If hide picker when selected
    $class_prefix                 string Class prefix
*}
{$is_disabled = $is_disabled|default:false}
{$allow_add = $allow_add|default:false}
{$placeholder = __("type_to_search")}

<div class="{$class_prefix}__input multiple-select__picker-container">
    <label for="{$type}_selector_{$storefront_id}"
           class="{$class_prefix}__label"
    >{$label_text}</label>

    <select multiple
            id="{$type}_selector_{$storefront_id}"
            class="cm-object-picker object-picker__select {$class_prefix}__picker"
            name={$input_name}
            data-ca-{$type}-picker
            data-ca-{$type}-editor-receiver-search-method="{$type}"
            data-ca-object-picker-object-type="{$type}"
            data-ca-object-picker-escape-html="false"
            {if $load_items_url}
                data-ca-object-picker-ajax-url="{$load_items_url|fn_url}"
                data-ca-object-picker-ajax-delay="250"
            {/if}
            data-ca-object-picker-autofocus="false"
            data-ca-object-picker-autoopen="false"
            data-ca-object-picker-close-on-select="false"
            data-ca-object-picker-placeholder="{$placeholder}"
            data-ca-object-picker-placeholder-value=""
            data-ca-object-picker-allow-clear="{if $is_disabled}false{else}true{/if}"
            {if $allow_add}
                data-ca-object-picker-enable-create-object="true"
                data-ca-object-picker-template-result-new-selector="{$template_result_new_selector}"
            {/if}
            {if $template_result_selector}
                data-ca-object-picker-template-result-selector="{$template_result_selector}"
            {/if}
            {if $is_disabled}
                disabled
            {/if}
    >
        {foreach $items as $k => $item}
            {if $id_field}
                {$id = $item.$id_field}
            {else}
                {$id = $k}
            {/if}
            
            {if $name_field}
                {$item_name = $item.$name_field}
            {else}
                {$item_name = $item}
            {/if}

            {if $id|in_array:$item_ids}
                <option value="{$id}" selected>{$item_name}</option>
            {elseif !$load_items_url}
                <option value="{$id}">{$item_name}</option>
            {/if}
        {/foreach}
    </select>
</div>
