{*
Notification receivers editor.

$is_editable                 bool                                   Whether receivers can be edited
$object_id                   string                                 Identificator of the group the edited event relates to
$receiver_type               enum(\Tygh\Enum\UserTypes)             Receiver type
$receivers                   array                                  Selected receiver search conditions
$values                      array                                  Readable receiver values
$object_type                 string                                 Edited object type ('group' or 'event').
$manage_button_text          string                                 Text to display on manage receivers button
*}
{$object_type = $object_type|default:"group"}
<div class="dropdown">
    <a class="btn dropdown-toggle notification-group__toggle-editor" data-toggle="dropdown">
        {if $manage_button_text}
            {$manage_button_text}
        {elseif $is_editable}
            {__("manage")}
        {else}
            {__("view")}
        {/if}
        <span class="caret mobile-hide"></span>
    </a>
    <div class="dropdown-menu notification-group__editor cm-notification-receivers-editor pull-right"
         data-ca-notification-receivers-editor-cancel-button-selector="[data-ca-notification-receivers-editor-cancel]"
         data-ca-notification-receivers-editor-update-button-selector="[data-ca-notification-receivers-editor-update]"
         data-ca-notification-receivers-editor-receiver-picker-selector="[data-ca-notification-receivers-editor-picker]"
         data-ca-notification-receivers-editor-object-type="{$object_type}"
         data-ca-notification-receivers-editor-object-id="{$object_id}"
         data-ca-notification-receivers-editor-submit-url="{"notification_settings.update_receivers?receiver_type={$receiver_type}"|fn_url}"
         data-ca-notification-receivers-editor-load-url="{"notification_settings.manage?receiver_type={$receiver_type}"|fn_url}"
         data-ca-notification-receivers-editor-result-ids="{$object_type}_{$object_id}"
    >
        <div class="notification-group-editor__body">
            {$seleced_usergroups = []}
            {$selected_users = []}
            {$selected_emails = []}
            {$selected_fields = []}
            {foreach $receivers as $condition}
                {if $condition->getMethod() === "ReceiverSearchMethods::USERGROUP_ID"|enum
                    || $condition->getMethod() === "ReceiverSearchMethods::ORDER_MANAGER"|enum
                    || $condition->getMethod() === "ReceiverSearchMethods::VENDOR_OWNER"|enum
                }
                    {$selected_usergroups[] = $condition->getCriterion()}
                {elseif $condition->getMethod() === "ReceiverSearchMethods::USER_ID"|enum}
                    {$selected_users[] = $condition->getCriterion()}
                {elseif $condition->getMethod() === "ReceiverSearchMethods::EMAIL"|enum}
                    {$selected_emails[] = $condition->getCriterion()}
                {elseif $condition->getMethod() === "ReceiverSearchMethods::VENDOR_EMAIL"|enum}
                    {$selected_fields[] = $condition->getCriterion()}
                {/if}
            {/foreach}

            {hook name="notification_settings:receiver_pickers"}
                {if $receiver_search_methods["ReceiverSearchMethods::USERGROUP_ID"|enum]}
                    {include file="views/notification_settings/components/receivers_picker.tpl"
                        label_text = __("usergroups")
                        receiver_search_method = "ReceiverSearchMethods::USERGROUP_ID"|enum
                        selected_items = $selected_usergroups
                        object_type = $object_type
                        object_id = $object_id
                        load_items_url = "notification_settings.get_usergroups?type={$receiver_type}&group={$object_id}"
                        allow_add = false
                        is_disabled = !$is_editable
                    }
                {/if}

                {if $receiver_search_methods["ReceiverSearchMethods::USER_ID"|enum]}
                    {include file="views/notification_settings/components/receivers_picker.tpl"
                        label_text = __("users")
                        receiver_search_method = "ReceiverSearchMethods::USER_ID"|enum
                        selected_items = $selected_users
                        object_type = $object_type
                        object_id = $object_id
                        load_items_url = "notification_settings.get_users?type={$receiver_type}&group={$object_id}"
                        allow_add = false
                        template_result_selector = "#template_result_add_user"
                        is_disabled = !$is_editable
                    }
                {/if}

                {if $receiver_search_methods["ReceiverSearchMethods::USER_ID"|enum]}
                    {include file="views/notification_settings/components/receivers_picker.tpl"
                        label_text = __("emails")
                        receiver_search_method = "ReceiverSearchMethods::EMAIL"|enum
                        selected_items = $selected_emails
                        object_type = $object_type
                        object_id = $object_id
                        allow_add = true
                        template_result_new_selector = "#template_result_add_email"
                        show_selected_items = true
                        is_disabled = !$is_editable
                    }
                {/if}

                {if $receiver_search_methods["ReceiverSearchMethods::VENDOR_EMAIL"|enum]}
                    {include file="views/notification_settings/components/receivers_picker.tpl"
                        label_text = __("vendor_emails")
                        receiver_search_method = "ReceiverSearchMethods::VENDOR_EMAIL"|enum
                        selected_items = $selected_fields
                        object_type = $object_type
                        object_id = $object_id
                        load_items_url = "notification_settings.get_vendor_emails?type=`$receiver_type`&group=`$object_id`"
                        allow_add = false
                        show_selected_items = true
                        is_disabled = !$is_editable
                    }
                {/if}
            {/hook}
        </div>
        {if $is_editable}
            <div class="notification-group-editor__footer">
                <a class="btn dropdown-toggle notification-group-editor__btn"
                   data-ca-notification-receivers-editor-cancel
                >{__("cancel")}</a>
                <a class="btn btn-primary notification-group-editor__btn"
                   data-ca-notification-receivers-editor-update
                >{__("apply")}</a>
            </div>
        {/if}
    </div>
</div>
