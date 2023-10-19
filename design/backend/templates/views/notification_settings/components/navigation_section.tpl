<div class="sidebar-row">
    <ul class="nav nav-list">
        <li class="{if $active_section == "customer_notifications"} active{/if}"><a href="{"notification_settings.manage?receiver_type={"UserTypes::CUSTOMER"|enum}"|fn_url}">{__("customer_notifications")}</a></li>
        <li class="{if $active_section == "admin_notifications"} active{/if}"><a href="{"notification_settings.manage?receiver_type={"UserTypes::ADMIN"|enum}"|fn_url}">{__("admin_notifications")}</a></li>
        {if "MULTIVENDOR"|fn_allowed_for}
                <li class="{if $active_section == "vendor_notifications"} active{/if}"><a href="{"notification_settings.manage?receiver_type={"UserTypes::VENDOR"|enum}"|fn_url}">{__("vendor_notifications")}</a></li>
        {/if}
        {hook name="notification_settings:dynamic_menu_user_types"}
        {/hook}
        {if ($settings.Appearance.email_templates == "new")}
            {if fn_check_view_permissions("email_templates.snippets", "GET")}
                <li class="{if $active_section == "code_snippets"} active{/if}"><a href="{"email_templates.snippets"|fn_url}">{__("code_snippets")}</a></li>
            {/if}
            {if fn_check_view_permissions("documents.manage", "GET")}
                <li class="{if $active_section == "documents"} active{/if}"><a href="{"documents.manage"|fn_url}">{__("documents")}</a></li>
            {/if}
        {/if}
    </ul>
</div>
<hr>
