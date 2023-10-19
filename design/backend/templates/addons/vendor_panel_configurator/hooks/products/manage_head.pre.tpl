{if "MULTIVENDOR"|fn_allowed_for
    && !$runtime.simple_ultimate
    && $auth.user_type == "UserTypes::VENDOR"|enum
}
    {include file="addons/vendor_panel_configurator/config.tpl"}

    {$show_list_price_column = $show_list_price_column scope=parent}
    {$image_width = $image_width scope=parent}
    {$image_height = $image_height scope=parent}
{/if}
