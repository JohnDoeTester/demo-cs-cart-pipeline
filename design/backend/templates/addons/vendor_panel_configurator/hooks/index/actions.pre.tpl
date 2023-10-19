{if "MULTIVENDOR"|fn_allowed_for
    && !$runtime.simple_ultimate
    && $auth.user_type == "UserTypes::VENDOR"|enum
}
    {include file="addons/vendor_panel_configurator/config.tpl"}

    {$navigation_accordion = $navigation_accordion scope=parent}
    {$show_last_viewed_items = $show_last_viewed_items scope=parent}
{/if}
