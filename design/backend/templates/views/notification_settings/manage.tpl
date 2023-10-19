{$can_update_settings = fn_check_view_permissions("notification_settings.update", "POST")}
{$can_edit_email_templates = fn_check_view_permissions("email_templates.manage", "GET")}
{$can_edit_internal_templates = fn_check_view_permissions("internal_templates.manage", "GET")}

{capture name="sidebar"}
    {include file="views/notification_settings/components/navigation_section.tpl" active_section=$active_section}
{/capture}

{hook name="notification_settings:section_title"}
    {if $receiver_type ==  "UserTypes::CUSTOMER"|enum}
        {$page_title = __("customer_notifications")}
    {elseif $receiver_type ==  "UserTypes::ADMIN"|enum}
        {$page_title = __("admin_notifications")}
    {elseif $receiver_type ==  "UserTypes::VENDOR"|enum}
        {$page_title = __("vendor_notifications")}
    {/if}
{/hook}

{capture name="mainbox"}
    <form action="{""|fn_url}" method="post" name="notifications_form" class="form-horizontal form-edit form-setting">
        <input type="hidden" id="receiver_type" name="receiver_type" value="{$receiver_type}" />
        {$rec = 70 / count($transports[$receiver_type])}
        <table class="table table-responsive table--sticky notification-settings__table">
            <thead class="notification-settings__header">
            <tr>
                <th class="table__head-sticky" width="40%">{__("event.notification_type")}</th>
                {foreach $transports[$receiver_type] as $transport => $value}
                    <th class="table__head-sticky">{__("event.transport.$transport")}</th>
                {/foreach}
                <th class="table__head-sticky" width="15%"></th>
            </tr>
            </thead>
            {foreach $event_groups as $group_name => $events}

                {capture name="events_group"}
                    {foreach $events as $event_id => $event}

                        {$array_transports = $event["receivers"][$receiver_type]}
                        {if !$array_transports}
                            {continue}
                        {/if}
                        {$template_code = $event["receivers"][$receiver_type]["template_code"]|default:""}
                        {$template_area = $event["receivers"][$receiver_type]["template_area"]|default:""}
                        <tr>
                            <td class="notification-settings__name" data-th="{__("notification")}">
                                {if $template_code && $template_area && $can_edit_email_templates}
                                    <a href="{fn_url("email_templates.update?code={$template_code}&area={$template_area}&event_id={$event_id}&receiver={$receiver_type}")}">
                                {/if}
                                {__($event["name"]["template"], $event["name"]["params"])}
                                {if ($template_code) && $template_area && $can_edit_email_templates}
                                    </a>
                                {/if}
                                {if $event.description}
                                    <p class="muted">{__($event.description.template, $event.description.params)}</p>
                                {/if}
                            </td>
                            {foreach $transports[$receiver_type] as $transport => $value}
                                <td data-th="{__("event.transport.$transport")}">
                                    {if $transport|array_key_exists:$array_transports}
                                        {foreach $array_transports as $transport_name => $is_enabled}
                                            {if $transport_name == $transport}
                                                <input type="hidden"
                                                    name="notification_settings[{$event_id}][{$receiver_type}][{$transport_name}]"
                                                    value="{"YesNo::NO"|enum}"
                                                />
                                                <input name="notification_settings[{$event_id}][{$receiver_type}][{$transport_name}]"
                                                    class="checkbox--nomargin"
                                                    type="checkbox"
                                                    value="{"YesNo::YES"|enum}"
                                                    {if $is_enabled}checked{/if}
                                                    {if !$can_update_settings}disabled{/if}
                                                />
                                            {/if}
                                        {/foreach}
                                    {else}
                                        <span>&mdash;</span>
                                    {/if}
                                </td>
                            {/foreach}
                            <td data-th="">
                                {if $event.is_configurable && $group_settings.$group_name.$receiver_type.methods}
                                    {include file="views/notification_settings/components/receivers_editor.tpl"
                                        is_editable = $can_update_settings
                                        object_type = "event"
                                        object_id = $event_id
                                        receivers = $event.receiver_search_conditions.$receiver_type
                                        receiver_search_methods = $group_settings.$group_name.$receiver_type.methods
                                        manage_button_text = "{__("receivers")} ({$event.receiver_search_conditions.$receiver_type|count})"
                                    }
                                {/if}
                            </td>
                        </tr>
                        {$event_receivers = [$group_name => $event.receiver_search_conditions_readable.$receiver_type]}
                        {$event_receivers_search_conditions = [$group_name => $event.receiver_search_conditions.$receiver_type]}
                    {/foreach}
                {/capture}

                {if $smarty.capture.events_group|trim}
                <tbody>
                    <tr class="notification-settings__group">
                        <td data-th=""><h4>{__($group_name)}</h4></td>
                        <td data-th="" colspan="{count($transports[$receiver_type])}"></td>
                        <td data-th=""></td>
                    </tr>
                    {if $group_settings.$group_name.$receiver_type.is_configurable && $group_settings.$group_name.$receiver_type.methods}
                        <tr class="row-gray notification-settings__receiver" id="group_{$group_name}">
                            <td data-th="" colspan="{count($transports[$receiver_type]) + 1}">
                                {include file="views/notification_settings/components/receivers.tpl"
                                    object_type = "group"
                                    object_id = $group_name
                                    show_heading = true
                                    receivers = $event_receivers_search_conditions.$group_name
                                    values = $event_receivers.$group_name
                                }
                            </td>
                            <td data-th="">
                                {include file="views/notification_settings/components/receivers_editor.tpl"
                                    is_editable = $can_update_settings
                                    object_type = "group"
                                    object_id = $group_name
                                    receivers = $event_receivers_search_conditions.$group_name
                                    receiver_search_methods = $group_settings.$group_name.$receiver_type.methods
                                }
                            </td>
                        <!--group_{$group_name}--></tr>
                    {/if}

                    {if $group_name === 'profile'}
                        <tr class="row-gray notification-settings__receiver" id="group_{$group_name}">
                            <td data-th="" colspan="{count($transports[$receiver_type]) + 1}">
                                <div class="notification-group__existing-receivers">
                                    <strong>{__("receivers")}:</strong>
                                    <span class="notification-group-existing-receivers__item">
                                        {__('notification_settings.profile.receivers')}
                                    </span>
                                </div>
                            </td>
                            <td data-th=""></td>
                        </tr>
                    {/if}

                    {$smarty.capture.events_group nofilter}
                </tbody>
                {/if}
            {/foreach}
            <tfoot>
            {if $can_edit_email_templates || $can_edit_internal_templates}
                <tr>
                    <td colspan="{count($transports[$receiver_type]) + 2}"><h4>{__("other_notification")}</h4></td>
                </tr>
                <tr>
                    <td colspan="{count($transports[$receiver_type]) + 2}">
                        <p>{__("other_notifications.title")}</p>
                        {if $can_edit_email_templates}
                            <p>
                                <a href="{fn_url("email_templates.manage")}">{__("other_notifications.email_templates")}</a>
                            </p>
                        {/if}
                        {if $can_edit_internal_templates}
                            <p>
                                <a href="{fn_url("internal_templates.manage")}">{__("other_notifications.internal_templates")}</a>
                            </p>
                        {/if}
                    </td>
                </tr>
            {/if}
            </tfoot>
        </table>
    </form>
    {capture name="buttons"}
        {include file="buttons/save.tpl" but_name="dispatch[notification_settings.m_update]" but_role="submit-link" but_target_form="notifications_form"}
    {/capture}

    <template id="template_result_add_email">
        <div class="object-selector-result-wrapper">
            <span class="object-selector-result">
                <span class="object-selector-result__icon-wrapper">
                    {include_ext file="common/icon.tpl"
                        class="icon-plus-sign object-selector-result__icon"
                    }
                </span>
                <span class="object-selector-result__text">
                    <span class="object-selector-result__prefix">{__("add")}</span>
                    {literal}
                    <span class="object-selector-result__body">${data.text}</span>
                    {/literal}
                </span>
            </span>
        </div>
    </template>

    <template id="template_result_add_user">
        <div class="object-selector-result-wrapper">
            <span class="object-selector-result">
                {literal}
                <span class="object-selector-result__text">
                    <span class="object-selector-result__body">${data.name}</span>
                </span>
                <span class="object-selector-result__append">${data.email}</span>
                {/literal}
                {if !$runtime.simple_ultimate}
                {literal}
                    <div class="object-selector-result__group">${data.company_name}</div>
                {/literal}
                {/if}
            </span>
        </div>
    </template>
{/capture}

{include file="common/mainbox.tpl"
    title=$page_title|default:__("notifications")
    buttons=$smarty.capture.buttons
    content=$smarty.capture.mainbox
    sidebar_position="right"
    sidebar=$smarty.capture.sidebar
}
