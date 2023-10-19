{if "MULTIVENDOR"|fn_allowed_for
    && !$runtime.simple_ultimate
    && $auth.user_type == "UserTypes::VENDOR"|enum
}
    {include file="addons/vendor_panel_configurator/config.tpl"}

    {$select_languages = $select_languages scope=parent}
{/if}
