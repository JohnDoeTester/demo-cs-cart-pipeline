{if $smarty.const.ACCOUNT_TYPE === "vendor"}
    {$navigation_accordion              = $config["vendor_panel_style"]["navigation_accordion"]|default:true             scope=parent}
    {$select_languages                  = $config["vendor_panel_style"]["select_languages"]|default:false                scope=parent}
    {$enable_onclick_menu               = $config["vendor_panel_style"]["enable_onclick_menu"]|default:true              scope=parent}
    {$enable_sticky_scroll              = $config["vendor_panel_style"]["enable_sticky_scroll"]|default:false            scope=parent}
    {$enable_search_collapse            = $config["vendor_panel_style"]["enable_search_collapse"]|default:false          scope=parent}

    {$show_company                      = $config["vendor_panel_style"]["show_company"]|default:false                    scope=parent}
    {$show_menu_descriptions            = $config["vendor_panel_style"]["show_menu_descriptions"]|default:false          scope=parent}
    {$show_menu_caret                   = $config["vendor_panel_style"]["show_menu_caret"]|default:false                 scope=parent}
    {$show_addon_icon                   = $config["vendor_panel_style"]["show_addon_icon"]|default:false                 scope=parent}
    {$show_languages_in_header_menu     = $config["vendor_panel_style"]["show_languages_in_header_menu"]|default:false   scope=parent}
    {$show_currencies_in_header_menu    = $config["vendor_panel_style"]["show_currencies_in_header_menu"]|default:false  scope=parent}
    {$show_last_viewed_items            = $config["vendor_panel_style"]["show_last_viewed_items"]|default:false          scope=parent}
    {$show_pagination_open              = $config["vendor_panel_style"]["show_pagination_open"]|default:false            scope=parent}
    {$show_list_price_column            = $config["vendor_panel_style"]["show_list_price_column"]|default:false          scope=parent}

    {$image_width                       = $config["vendor_panel_style"]["image_width"]|default:"80"                      scope=parent}
    {$image_height                      = $config["vendor_panel_style"]["image_height"]|default:"80"                     scope=parent}

    {$is_open_state_sidebar_save        = $config["vendor_panel_style"]["is_open_state_sidebar_save"]|default:true       scope=parent}

    {* Page: addons.update&addon=vendor_panel_style&selected_section=settings *}
    {* Element color *}
    {if $runtime.vendor_panel_style.element_color}
        {$mainColor = $dashboard_element_color|default:$runtime.vendor_panel_style.element_color scope=parent}
    {elseif $config["vendor_panel_style"]["main_color"]}
        {$mainColor = $config["vendor_panel_style"]["main_color"] scope=parent}
    {else}
        {$mainColor = "#024567" scope=parent} {* Admin main color #0388cc + 20% darken *}
    {/if}

    {* Sidebar color *}
    {if $runtime.vendor_panel_style.sidebar_color}
        {$menuSidebarColor = $dashboard_sidebar_color|default:$runtime.vendor_panel_style.sidebar_color scope=parent}
    {elseif $config["vendor_panel_style"]["menu_sidebar_color"]}
        {$menuSidebarColor = $config["vendor_panel_style"]["menu_sidebar_color"] scope=parent}
    {else}
        {$menuSidebarColor = "#eef1f3" scope=parent}
    {/if}

    {* Menu sidebar background *}
    {if $runtime.vendor_panel_style.main_pair.icon.image_path}
        {$menuSidebarBg = "url(`$runtime.vendor_panel_style.main_pair.icon.image_path`)" scope=parent}
    {elseif $config["vendor_panel_style"]["menu_sidebar_bg"]}
        {$menuSidebarBg = "url(`$config['vendor_panel_style']['menu_sidebar_bg']`)" scope=parent}
    {else}
        {$menuSidebarBg = "none" scope=parent}
    {/if}

    {if $is_gray_main_color}
        {$isGrayMainColor = $is_gray_main_color|default:false scope=parent}
    {/if}
{/if}
