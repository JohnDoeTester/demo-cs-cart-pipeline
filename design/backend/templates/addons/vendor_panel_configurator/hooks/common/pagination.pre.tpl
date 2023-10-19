{if "MULTIVENDOR"|fn_allowed_for
    && !$runtime.simple_ultimate
    && $auth.user_type == "UserTypes::VENDOR"|enum
}
    {include file="addons/vendor_panel_configurator/config.tpl"}

    {$show_pagination_open = $show_pagination_open scope=parent}
{/if}
