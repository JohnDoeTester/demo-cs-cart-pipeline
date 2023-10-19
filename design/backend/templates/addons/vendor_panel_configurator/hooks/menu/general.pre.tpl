{if "MULTIVENDOR"|fn_allowed_for
    && !$runtime.simple_ultimate
    && $auth.user_type == "UserTypes::VENDOR"|enum
}
    {include file="addons/vendor_panel_configurator/config.tpl"}

    {$navigation_accordion = $navigation_accordion scope=parent}
    {$show_company = $show_company scope=parent}
    {$show_menu_descriptions = $show_menu_descriptions scope=parent}
    {$show_addon_icon = $show_addon_icon scope=parent}
    {$show_menu_caret = $show_menu_caret scope=parent}
    {$enable_sticky_scroll = $enable_sticky_scroll scope=parent}
    {$enable_search_collapse = $enable_search_collapse scope=parent}
    {$enable_onclick_menu = $enable_onclick_menu scope=parent}
    {$show_languages_in_header_menu = $show_languages_in_header_menu scope=parent}
    {$show_currencies_in_header_menu = $show_currencies_in_header_menu scope=parent}
{/if}
