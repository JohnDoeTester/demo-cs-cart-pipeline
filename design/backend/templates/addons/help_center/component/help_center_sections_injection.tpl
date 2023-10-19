{$sections_injection = []}
{$service_url="`$config.helpdesk.url|rtrim:'/'`/"}
{$new_section_default = [
    id => "ask",
    name => __("help_center.ask"),
    icon => "icon-inbox",
    blocks => []
]}

{$ask_section_is_new = false}
{if $ask_data_last_update && $ask_data_last_update > $help_center_last_view}
    {$ask_section_is_new = true}
{/if}

{foreach $sections as $section}
    {if $auth.helpdesk_user_id
        && $settings.Upgrade_center.license_number
        && $ask_data.subscription.status === "ObjectStatuses::ACTIVE"|enum
        && $section
        && $section|count > 0
    }
        {* 1. Basic state: tickets list *}
        {$new_section = [
            id => $new_section_default.id,
            name => $new_section_default.name,
            icon => $new_section_default.icon,
            blocks => $new_section_default.blocks,
            action_name => __("help_center.customer_care.submit_ticket"),
            action_url => "`$service_url`index.php?dispatch=communication.tickets&submit_ticket=Y&ticket_source=I",
            all_items_name => __("help_center.customer_care.view_all_tickets"),
            all_items_url => "{$service_url}index.php?dispatch=communication.tickets"
        ]}
        {if $new_section["id"] === "ask" && $ask_section_is_new}
            {$new_section["new"] = true}
        {/if}
        {foreach $section as $section_data}
            {$new_section.blocks[$section_data@index] = [
                id => $section_data.ticket_id,
                url => "`$service_url`index.php?dispatch=communication.messages&ticket_id=`$section_data.ticket_id`",
                name => $section_data.ticket,
                status => ($section_data.status === "resolved") ? "success" : "warning",
                status_text => ($section_data.status === "resolved") ? __("help_center.customer_care.status.resolved") : __("help_center.customer_care.status.open"),
                class => 'help-center-block__title--secondary'
            ]}
        {/foreach}
        {$sections_injection[] = $new_section}
    {elseif $auth.helpdesk_user_id
        && $settings.Upgrade_center.license_number
        && $ask_data.subscription.status === "ObjectStatuses::ACTIVE"|enum
    }
        {* 2. No open tickets state *}
        {$new_section = [
            id => $new_section_default.id,
            name => $new_section_default.name,
            icon => $new_section_default.icon,
            class => "help-center-section--empty-state",
            blocks => [
                [
                    id => "no_open_tickets",
                    image_url => "design/backend/media/images/addons/help_center/helpdesk.svg",
                    name => __("help_center.customer_care"),
                    description => __("help_center.customer_care.no_support_tickets"),
                    action_url => "`$service_url`index.php?dispatch=communication.tickets&submit_ticket=Y&ticket_source=I",
                    action_name => __("help_center.customer_care.submit_ticket"),
                    action_secondary_url => "{$service_url}index.php?dispatch=communication.tickets",
                    action_secondary_name => __("help_center.customer_care.view_all_tickets")
                ]
            ]
        ]}
        {$sections_injection[] = $new_section}
    {elseif $auth.helpdesk_user_id
        && $settings.Upgrade_center.license_number
    }
        {* 3. Subscription has expired state *}
        {$new_section = [
            id => $new_section_default.id,
            name => $new_section_default.name,
            icon => $new_section_default.icon,
            class => "help-center-section--empty-state",
            blocks => [
                [
                    id => "subscription_expired",
                    image_url => "design/backend/media/images/addons/help_center/helpdesk.svg",
                    name => __("help_center.customer_care.service_unavailable"),
                    description => __("help_center.customer_care.service_unavailable_description"),
                    action_url => "`$service_url`customer-care-subscription.html",
                    action_name => __("help_center.customer_care.prolong_subscription")
                ]
            ]
        ]}
        {$sections_injection[] = $new_section}
    {elseif !$auth.helpdesk_user_id}
        {* 4. No auth state *}
        {$new_section = [
            id => $new_section_default.id,
            name => $new_section_default.name,
            icon => $new_section_default.icon,
            class => "help-center-section--empty-state",
            blocks => [
                [
                    id => "no_auth_state",
                    image_url => "design/backend/media/images/addons/help_center/helpdesk.svg",
                    name => __("help_center.customer_care"),
                    description => __("help_center.customer_care.sign_in_text"),
                    action_url => $app["helpdesk.connect_url"]|fn_url,
                    action_name => __("helpdesk_account.sign_in")
                ]
            ]
        ]}
        {$sections_injection[] = $new_section}
    {/if}
{/foreach}

{* Injection sections data *}
<script type="text/template" data-ca-help-center="sectionsInjection" data-no-defer="true" data-no-execute="§">{$sections_injection|@json_encode nofilter}</script>
