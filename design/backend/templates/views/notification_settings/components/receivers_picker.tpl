{*
    Receivers picker for Notification receivers editor.

    $is_disabled                  bool                                   Whether receivers picker is blocked
    $object_type                  string
    $object_id                    string                                 Identificator of the group the edited event relates to
    $label_text                   string                                 Input field label
    $placeholder                  string                                 Input field placeholder
    $receiver_search_method       enum(\Tygh\Enum\ReceiverSearchMethods) Receiver search method
    $load_items_url               string                                 URL to load receivers from
    $allow_add                    bool                                   Whether it's allowed to create new variants for picker
    $template_result_new_selector string                                 `templateResult` selector
    $template_result_selector     string                                 `templateSelection` selector
    $selected_items               array                                  Selected picker variants
*}
{$is_disabled = $is_disabled|default:false}
{$placeholder = __("type_to_search")}

<div class="notification-group-editor__input-group">
    <label for="{$receiver_search_method}_selector_{$object_type}_{$object_id}"
           class="notification-group-editor__label"
    >{$label_text}</label>

    <select multiple
            id="{$receiver_search_method}_selector_{$object_type}_{$object_id}"
            class="cm-object-picker object-picker__select notification-group-editor__picker"
            data-ca-notification-receivers-editor-picker
            data-ca-notification-receivers-editor-receiver-search-method="{$receiver_search_method}"
            data-ca-object-picker-object-type="{$receiver_search_method}"
            data-ca-object-picker-escape-html="false"
            {if $load_items_url}
                data-ca-object-picker-ajax-url="{$load_items_url|fn_url}"
                data-ca-object-picker-ajax-delay="250"
            {/if}
            data-ca-object-picker-autofocus="false"
            data-ca-object-picker-autoopen="false"
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
        {foreach $selected_items as $item}
            <option value="{$item}" selected>{$item}</option>
        {/foreach}
    </select>
</div>
