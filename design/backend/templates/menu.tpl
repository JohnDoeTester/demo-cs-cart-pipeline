{if $smarty.const.BLOCK_MANAGER_MODE}
    {$li_tag = "div"}
{else}
    {$li_tag = "li"}
{/if}

{hook name="menu:general"}

    {if "THEMES_PANEL"|defined}
        {$sticky_top = -5}
        {$sticky_padding = 35}
    {else}
        {$sticky_top = -40}
        {$sticky_padding = 0}
    {/if}

    {$navigation_accordion = $navigation_accordion|default:false}
    {$enable_onclick_menu = $enable_onclick_menu|default:false}
    {$enable_sticky_scroll = $enable_sticky_scroll|default:true}
    {$enable_search_collapse = $enable_search_collapse|default:true}
    {$show_company = $show_company|default:true}
    {$show_menu_descriptions = $show_menu_descriptions|default:true}
    {$show_menu_caret = $show_menu_caret|default:true}
    {$show_addon_icon = $show_addon_icon|default:true}
    {$show_languages_in_header_menu = $show_languages_in_header_menu|default:true}
    {$show_currencies_in_header_menu = $show_currencies_in_header_menu|default:true}

    {function menu_attrs attrs=[]}
        {foreach $attrs as $attr => $value}
            {$attr}="{$value}"
        {/foreach}
    {/function}

    {capture name="languages_menu"}
        {if $menu_languages|sizeof > 1}
            {include file="common/select_object.tpl"
                style="dropdown"
                link_tpl=$config.current_url|fn_link_attach:"sl="
                items=$menu_languages
                selected_id=$smarty.const.CART_LANGUAGE
                display_icons=true
                key_name="name"
                key_selected="lang_code"
                class="languages"
                is_submenu=!$show_languages_in_header_menu
            }
        {/if}
    {/capture}

    {capture name="currencies_menu"}
        {if $currencies|sizeof > 1}
            {include file="common/select_object.tpl" style="dropdown" link_tpl=$config.current_url|fn_link_attach:"currency=" items=$currencies selected_id=$secondary_currency display_icons=false key_name="description" key_selected="currency_code" class="curriencies" is_submenu=!$show_currencies_in_header_menu}
        {/if}
    {/capture}

    <div class="navbar-admin-top {if $enable_sticky_scroll}cm-sticky-scroll{/if}" data-ca-stick-on-screens="sm-large,md,md-large,lg,uhd" data-ca-top="{$sticky_top}" data-ca-padding="{$sticky_padding}">
        <!--Navbar-->
        <div class="navbar navbar-inverse mobile-hidden" id="header_navbar">
            <div class="navbar-inner{if $runtime.is_current_storefront_closed || $runtime.are_all_storefronts_closed} navbar-inner--disabled{/if}">
            {if $runtime.company_data.company}
                {$name = $runtime.company_data.company}
            {else}
                {$name = $settings.Company.company_name}
            {/if}

            {if "ULTIMATE"|fn_allowed_for}
                {if $runtime.is_current_storefront_closed || $runtime.are_all_storefronts_closed}
                    {$storefront_status_icon = "icon-lock"}
                {elseif $runtime.have_closed_storefronts}
                    {$storefront_status_icon = "icon-unlock-alt"}
                {/if}

                <div class="nav-ult">
                    {hook name="menu:storefront_icon"}
                        {if !$runtime.company_data.company_id}
                            {$name = __("all_vendors")}
                        {/if}
                    <li class="nav-company">
                        {if $runtime.are_all_storefronts_closed}
                            {$title = __("no_active_storefronts")}
                        {else}
                            {$title = __("view_storefront")}
                        {/if}
                        {$storefront_url = fn_url("profiles.act_as_user?user_id={$auth.user_id}&area=C")}
                        <a href="{$storefront_url}" target="_blank" class="brand" title="{$title}">
                            {include_ext file="common/icon.tpl" class="icon-shopping-cart"}
                        </a>
                    </li>
                    {/hook}
                    {if $runtime.company_id || $runtime.forced_company_id}
                        <ul class="nav">
                            <li class="dropdown">
                                <a href="{"companies.update?company_id=`$runtime.company_data.company_id`"|fn_url}">{__("storefront")}: {$runtime.company_data.company}</a>
                            </li>
                        </ul>
                    {/if}
                </div>
            {/if}

            {if "MULTIVENDOR"|fn_allowed_for && !$runtime.simple_ultimate}

                {if $runtime.are_all_storefronts_closed}
                    {$storefront_status_icon = "icon-lock"}
                {elseif $runtime.have_closed_storefronts}
                    {$storefront_status_icon = "icon-unlock-alt"}
                {/if}

                {if $runtime.is_multiple_storefronts}
                    {if $smarty.request.storefront_id}
                        {$storefront_id=$smarty.request.storefront_id}
                    {else}
                        {$storefront_id=$app.storefront->storefront_id}
                    {/if}
                {/if}

                {if $show_company}
                    <ul class="nav">
                        {hook name="menu:storefront_icon"}
                        <li class="nav-company">
                            {if $auth.user_type == "UserTypes::ADMIN"|enum}
                                {$storefront_url = fn_url("profiles.act_as_user?user_id={$auth.user_id}&area=C{if $storefront_id}&storefront_id={$storefront_id}{/if}")}
                            {else}
                                {$storefront_url = ($runtime.company_id) ? "companies.products?company_id={$runtime.company_id}{if $storefront_id}&storefront_id={$storefront_id}{/if}" : ""}
                                {$storefront_url = fn_url($storefront_url, "C")}
                                {if $runtime.storefront_access_key}
                                    {$storefront_url = $storefront_url|fn_link_attach:"store_access_key={$runtime.storefront_access_key}"}
                                {/if}
                            {/if}
                            <a href="{$storefront_url}" target="_blank" class="brand" title="{__("view_storefront")}">
                                {include_ext file="common/icon.tpl" class="icon-shopping-cart"}
                            </a>
                            {if $storefront_status_icon && fn_check_view_permissions("storefronts.manage", "GET")}
                            <a href="{"storefronts.manage"|fn_url}" class="brand">
                                {include_ext file="common/icon.tpl" class="`$storefront_status_icon` dropdown-menu__icon"}
                            </a>
                            {/if}
                            {if $runtime.customization_mode.live_editor}
                                {assign var="company_name" value=$runtime.company_data.company}
                            {else}
                                {assign var="company_name" value=$runtime.company_data.company|truncate:43:"...":true}
                            {/if}
                        </li>
                        {if $auth.company_id}
                            <li class="dropdown nav__company-name">
                                <a href="{"companies.update?company_id=`$runtime.company_id`"|fn_url}">{__("vendor")}: {$runtime.company_data.company}</a>
                            </li>
                        {elseif $runtime.company_id && fn_check_view_permissions("companies.update", "GET")}
                            <li class="dropdown">
                                <a href="{"companies.update?company_id=`$runtime.company_id`"|fn_url}">{$company_name}</a>
                            </li>
                        {elseif $runtime.company_id}
                            <li class="dropdown">
                                <a class="unedited-element">{$company_name}</a>
                            </li>
                        {/if}
                        {/hook}
                    </ul>
                {/if}
            {/if}

                <ul id="mainrightnavbar" class="nav hover-show navbar-right">
                {if $auth.user_id && $navigation.static}

                    {foreach from=$navigation.static.top key=first_level_title item=m name="first_level_top"}
                        <li class="dropdown dropdown-top-menu-item{if $first_level_title == $navigation.selected_tab} active{/if} navbar-right__item navigate-items">
                            <a id="elm_menu_{$first_level_title}" href="{if $m.href}{$m.href|fn_url}{else}#{/if}" class="dropdown-toggle navbar-right__item-link {$first_level_title}">
                                {__($first_level_title)}
                                {if $m.items}
                                    <b class="caret"></b>
                                {/if}
                            </a>
                            {if $m.items}
                            <ul class="dropdown-menu navbar-right__submenu">
                                {foreach from=$m.items key=second_level_title item="second_level" name="sec_level_top"}
                                    <li class="navbar-right__submenu-item {if $second_level.subitems}dropdown-submenu{/if}{if $second_level_title == $navigation.subsection} active{/if} {if $second_level.is_promo}cm-promo-popup{/if} {$second_level.attrs.class}" {menu_attrs attrs=$second_level.attrs.main}>
                                        {if $second_level.type == "title"}
                                            {if $second_level.subitems}<div class="dropdown-submenu__link-overlay"></div>{/if}
                                            <a id="elm_menu_{$first_level_title}_{$second_level_title}" class="navbar-right__subitem-link {if $second_level.subitems}dropdown-submenu__link{/if} {if $second_level.attrs.class_href}{$second_level.attrs.class_href}{/if}" {menu_attrs attrs=$second_level.attrs.href}>
                                                {$second_level.title|default:__($second_level_title)}
                                                {if $second_level.attrs.class === "is-addon"}{include_ext file="common/icon.tpl" class="icon-is-addon"}{/if}
                                            </a>
                                        {elseif $second_level.type != "divider"}
                                            {if $second_level.subitems}<div class="dropdown-submenu__link-overlay"></div>{/if}
                                            <a id="elm_menu_{$first_level_title}_{$second_level_title}" class="navbar-right__subitem-link {if $second_level.subitems}dropdown-submenu__link{/if} {if $second_level.attrs.class_href}{$second_level.attrs.class_href}{/if}" href="{$second_level.href|fn_url}" {menu_attrs attrs=$second_level.attrs.href}>
                                                {$second_level.title|default:__($second_level_title)}
                                                {if $second_level.attrs.class === "is-addon"}{include_ext file="common/icon.tpl" class="icon-is-addon"}{/if}
                                            </a>
                                        {/if}
                                        {if $second_level.subitems}
                                            <ul class="dropdown-menu">
                                                {foreach from=$second_level.subitems key=subitem_title item=sm}
                                                    {if $sm.type != "divider"}
                                                    <li class="{if $sm.active}active{/if} {if $sm.is_promo}cm-promo-popup{/if} {$second_level.attrs.class}" {menu_attrs attrs=$sm.attrs.main}>
                                                        {if $sm.type == "title"}
                                                            {$sm.title|default:__($subitem_title)}
                                                        {elseif $sm.type != "divider"}
                                                            <a id="elm_menu_{$first_level_title}_{$second_level_title}_{$subitem_title}" {if $sm.attrs.class}class="{$sm.attrs.class}"{/if} href="{$sm.href|fn_url}" {menu_attrs attrs=$sm.attrs.href}>
                                                                {$sm.title|default:__($subitem_title)}
                                                                {if $sm.attrs.class === "is-addon"}{include_ext file="common/icon.tpl" class="icon-is-addon"}{/if}
                                                            </a>
                                                        {/if}
                                                    </li>
                                                    {elseif $sm.type == "divider"}
                                                        <li class="divider"></li>
                                                    {/if}
                                                {/foreach}
                                            </ul>
                                        {/if}
                                    </li>
                                    {if $second_level.type == "divider"}
                                        <li class="divider"></li>
                                    {/if}
                                {/foreach}
                            </ul>
                            {/if}
                        </li>
                    {/foreach}
                {/if}
                    <!-- end navbar-->

                {if $auth.user_id}

                    {if $menu_languages|sizeof > 1 || $currencies|sizeof > 1}
                        <li class="divider-vertical"></li>
                    {/if}

                    <!--language-->
                    {if $show_languages_in_header_menu}
                        {$smarty.capture.languages_menu nofilter}
                    {/if}
                    <!--end language-->

                    {hook name="menu:notification_center"}
                    <!-- Notification Center -->
                        {include file="components/notifications_center/opener.tpl"}
                    <!-- /Notification Center -->
                    {/hook}

                    <!--Curriencies-->
                        {if $show_currencies_in_header_menu}
                            {$smarty.capture.currencies_menu nofilter}
                        {/if}
                    <!--end curriencies-->

                    <li class="divider-vertical"></li>

                    <!-- user menu -->
                    <li class="dropdown dropdown-top-menu-item dropdown--open-enable nav__user-menu{if $enable_onclick_menu} hover-show--disabled{/if}">
                        {hook name="index:top_links"}
                            <a class="dropdown-toggle dropdown-top-menu-item-link nav__user-menu-link"
                                {if $enable_onclick_menu} data-toggle="dropdown"{/if}
                            >
                                {include_ext file="common/icon.tpl"
                                    class="icon-user nav__profile-icon"
                                }
                                <span class="nav__profile-text">
                                    {$user_info.firstname} {$user_info.lastname}
                                </span>
                                {if $show_menu_caret}
                                    <b class="caret"></b>
                                {/if}
                            </a>
                        {/hook}
                        <ul class="dropdown-menu pull-right nav__user-menu-dropdown">
                            <li class="disabled">
                                <a><strong>{__("signed_in_as")}</strong><br>{$user_info.email}</a>
                            </li>
                            <li class="divider"></li>
                            {hook name="menu:profile"}
                            {if !$show_languages_in_header_menu}
                                {$smarty.capture.languages_menu nofilter}
                            {/if}
                            {if !$show_currencies_in_header_menu}
                                {$smarty.capture.currencies_menu nofilter}
                            {/if}
                            <li><a href="{"profiles.update?user_id=`$auth.user_id`"|fn_url}">{__("edit_profile")}</a></li>
                            {if "MULTIVENDOR"|fn_allowed_for && !$runtime.simple_ultimate && $auth.user_type == "UserTypes::ADMIN"|enum && fn_check_view_permissions("companies.get_companies_list", "GET") && fn_check_view_permissions("profiles.login_as_vendor", "POST")}
                                <li id="company_picker_dropdown_menu"
                                    class="js-company-switcher"
                                    data-ca-switcher-param-name="company_id"
                                    data-ca-switcher-data-name="company_id">
                                    {include file="views/companies/components/picker/picker.tpl"
                                        input_name=$companies_picker_name
                                        item_ids=[$runtime.company_data.company_id]
                                        type="list"
                                        show_advanced=false
                                        selection_title_pre=__("log_in_as_vendor")
                                        dropdown_parent_selector="#company_picker_dropdown_menu"
                                    }
                                </li>
                            {/if}
                            {if "MULTIVENDOR"|fn_allowed_for
                                && $auth.user_type === "UserTypes::VENDOR"|enum
                                && fn_check_view_permissions("companies.update", "GET")
                            }
                                <li><a href="{"companies.update&company_id=`$runtime.company_id`"|fn_url}">{__("seller_info")}</a></li>
                            {/if}
                            {hook name="menu:profile_menu_extra_item"}
                            {/hook}
                            <li><a href="{"auth.logout"|fn_url}">{__("sign_out")}</a></li>
                            {if !$runtime.company_id}
                                <li class="divider"></li>
                                {if fn_check_view_permissions("upgrade_center.manage", "POST")}
                                    <li class="disabled">
                                        <a>{include file="common/product_release_info.tpl" is_time_shown=false}</a>
                                    </li>
                                {/if}
                                <li>
                                    {include file="common/popupbox.tpl" id="group`$id_prefix`feedback" edit_onclick=$onclick text=__("feedback_values") act="link" picker_meta="cm-clear-content" link_text=__("send_feedback", ["[product]" => $smarty.const.PRODUCT_NAME]) content=$smarty.capture.update_block href="feedback.prepare" no_icon_link=true but_name="dispatch[feedback.send]" opener_ajax_class="cm-ajax"}
                                </li>
                            {/if}
                            {/hook}
                        </ul>
                    </li>
                    <!--end user menu -->

                {/if}
                </ul>

            </div>
        <!--header_navbar--></div>

        <!--Subnav-->
        <div class="subnav header-subnav" id="header_subnav">
            <!--quick search-->
            <div class="search nav__search pull-right">
                {hook name="index:global_search"}
                    <form id="global_search" method="get" action="{""|fn_url}" class="search__form">
                        <input type="hidden" name="dispatch" value="search.results" />
                        <input type="hidden" name="compact" value="Y" />
                        <label for="gs_text" class="search__group">
                            <input type="text" class="cm-autocomplete-off search__input {if $enable_search_collapse}search__input--collapse{/if}" id="gs_text" name="q" placeholder="{__("admin_search_general")}" value="{$smarty.request.q}" />
                            <button class="btn search__button" type="submit" id="search_button"></button>
                        </label>
                    </form>
                {/hook}

            </div>
            <!--end quick search-->

            <!-- quick menu -->
            {include file="common/quick_menu.tpl"}
            <!-- end quick menu -->

            <ul class="nav nav__menu hover-show nav-pills">
                <li class="mobile-hidden nav__header-main-menu-item">
                    <a href="{""|fn_url}" class="home nav__menu-item">
                        {include_ext file="common/icon.tpl"
                            class="icon-home nav__home-icon"
                        }
                        <span class="nav__home-text">{__("home")}</span>
                    </a>
                </li>

                <div class="menu-heading mobile-visible">

                    <button class="btn btn-primary mobile-visible-inline mobile-menu-closer">{__("close")}</button>


                    <!-- title of heading -->
                    <div class="menu-heading__title-block">
                        <span class="menu-heading__title-text">{$user_info.email}</span>
                        <span class="caret menu-heading__title-caret"></span>
                    </div>

                    <div class="menu-heading__dropdowned closed">
                    <ul class="dropdown-menu menu-heading__dropdowned-menu">
                        {* user menu *}
                        <li class="disabled">
                            <a><strong>{__("signed_in_as")}</strong><br>{$user_info.email}</a>
                        </li>
                        <li class="divider"></li>
                        {hook name="menu:profile"}
                            <li><a href="{"profiles.update?user_id=`$auth.user_id`"|fn_url}">{__("edit_profile")}</a></li>
                            {if "MULTIVENDOR"|fn_allowed_for
                                && $auth.user_type === "UserTypes::VENDOR"|enum
                                && fn_check_view_permissions("companies.update", "GET")
                            }
                                <li><a href="{"companies.update&company_id=`$runtime.company_id`"|fn_url}">{__("seller_info")}</a></li>
                            {/if}
                            <li><a href="{"auth.logout"|fn_url}">{__("sign_out")}</a></li>
                        {/hook}
                        {* end user menu *}

                        {if "ULTIMATE"|fn_allowed_for}
                            {if fn_check_view_permissions("companies.manage", "GET")}
                                <li class="divider"></li>
                                <li><a href="{"companies.manage?switch_company_id=0"|fn_url}">{__("manage_stores")}...</a></li>
                            {/if}
                        {/if}

                        {* feedback *}
                        {if !$runtime.company_id}
                            <li class="divider"></li>
                            <li>
                                {include file="common/popupbox.tpl" id="group`$id_prefix`feedback" edit_onclick=$onclick text=__("feedback_values") act="link" picker_meta="cm-clear-content" link_text=__("send_feedback", ["[product]" => $smarty.const.PRODUCT_NAME]) content=$smarty.capture.update_block href="feedback.prepare" no_icon_link=true but_name="dispatch[feedback.send]" opener_ajax_class="cm-ajax"}
                            </li>
                        {/if}
                        {* end feedback *}
                    </ul>
                    </div>
                </div>

                <ul class="nav hover-show nav-pills nav-child mobile-visible nav-first">
                {if $runtime.company_data.storefront}
                    <li class="dropdown">
                        {$storefront_url = fn_url("profiles.act_as_user?user_id={$auth.user_id}&area=C")}
                        <a  href="{$storefront_url}"
                            target="_blank"
                            title="{__("view_storefront")}"
                            class="dropdown-toggle nav__menu-item"
                        >{__("view_storefront")}</a>
                    </li>
                {elseif "MULTIVENDOR"|fn_allowed_for}
                    <li class="dropdown">
                        {if $auth.user_type == "UserTypes::ADMIN"|enum}
                            {$storefront_url = fn_url("profiles.act_as_user?user_id={$auth.user_id}&area=C")}
                        {else}
                            {$storefront_url = fn_url("", "C")}
                            {if $runtime.storefront_access_key}
                                {$storefront_url = $storefront_url|fn_link_attach:"store_access_key={$runtime.storefront_access_key}"}
                            {/if}
                        {/if}
                        <a  href="{$storefront_url}"
                            target="_blank"
                            title="{__("view_storefront")}"
                            class="dropdown-toggle nav__menu-item"
                        >{__("view_storefront")}</a>
                    </li>
                {/if}
                    <li class="dropdown"><a href="{""|fn_url}" class="dropdown-toggle nav__menu-item">{__("home")}</a></li>
                </ul>

                {if $auth.user_id && $navigation.static.central}
                <hr class="mobile-visible navbar-hr" />
                <ul class="nav hover-show nav-pills nav-child nav__header-main-menu"
                    {if $smarty.const.BLOCK_MANAGER_MODE}
                        data-ca-block-manager="main"
                        data-ca-block-manager-has-layout="0"
                        data-ca-block-manager-table={$block_manager_table}
                        data-ca-block-manager-id-name="{$block_manager_id_name}"
                        data-ca-block-manager-dispatch="custom_menu"
                        data-ca-block-manager-blocks-place="true"
                        data-ca-block-manager-grid-id="header_main_menu"
                    {/if}
                id="header_main_menu">

                {foreach $navigation.static.central as $first_level_title => $m name="first_level"}
                    {capture name="first_level"}
                        {hook name="menu:first_level"}
                        {if $m.status !== "ObjectStatuses::DISABLED"|enum || $smarty.const.BLOCK_MANAGER_MODE}
                            {$is_active_menu_class = ($first_level_title === $navigation.selected_tab) ? "active" : ""}

                            <{$li_tag}
                                {if $navigation_accordion}
                                    class="accordion-group nav__header-main-menu-item {$is_active_menu_class}"
                                {else}
                                    class="dropdown nav__header-main-menu-item {$is_active_menu_class}"
                                {/if}
                            >
                                <a href="{if $m.href}{fn_url($m.href)}{else}#{$first_level_title}{/if}"
                                    {if $m.new_window === "YesNo::YES"|enum}target="_blank"{/if}
                                    {if $navigation_accordion && ($m.items && !$m.href || $smarty.const.BLOCK_MANAGER_MODE)}
                                        data-toggle="collapse" data-parent="#header_main_menu" class="nav__menu-item nav__menu-item--accordion cm-no-ajax {$first_level_class} {$is_active_menu_class}"
                                    {elseif $m.items && !$m.href}
                                        class="dropdown-toggle nav__menu-item {$first_level_class} {$is_active_menu_class}"
                                    {else}
                                        class="nav__menu-item {$first_level_class} {$is_active_menu_class}"
                                    {/if}
                                    data-ca-menu="navMenuItem"
                                >
                                    {$m.title|default:__($first_level_title)}
                                    {if $m.items && $show_menu_caret}
                                        <b class="caret"></b>
                                    {/if}
                                </a>
                                {if $m.items || $smarty.const.BLOCK_MANAGER_MODE}
                                <ul
                                    {if $navigation_accordion}
                                        class="collapse nav__header-main-menu-submenu {$is_active_menu_class}
                                            {if $first_level_title === $navigation.selected_tab && !$m.href}
                                                in
                                            {/if}
                                        "
                                    {else}
                                        class="dropdown-menu nav__header-main-menu-submenu {$is_active_menu_class}"
                                    {/if}
                                    {if $smarty.const.BLOCK_MANAGER_MODE}
                                        data-ca-block-manager-table={$block_manager_table}
                                        data-ca-block-manager-id-name="{$block_manager_id_name}"
                                        data-ca-block-manager-blocks-place="true"
                                        data-ca-block-manager-grid-id="{if $m.href}{fn_url($m.href)}{else}{$first_level_title}{/if}"
                                    {/if}
                                    id="{if $m.href}{fn_url($m.href)}{else}{$first_level_title}{/if}"
                                >
                                    {foreach $m.items as $second_level_title => $second_level name="sec_level"}

                                        {capture name="second_level"}
                                            {hook name="menu:second_level"}
                                            {if $second_level.status !== "ObjectStatuses::DISABLED"|enum || $smarty.const.BLOCK_MANAGER_MODE}
                                                {$is_active_submenu_class = ($second_level_title === $navigation.subsection && $first_level_title === $navigation.selected_tab) ? "active" : ""}

                                                <{$li_tag} {menu_attrs attrs=$second_level.attrs.main}
                                                    {if $navigation_accordion}
                                                        class="{$second_level_title} accordion-group nav__header-main-menu-subitem {$is_active_submenu_class}"
                                                    {else}
                                                        class="{$second_level_title} nav__header-main-menu-subitem
                                                        {if $second_level.subitems} dropdown-submenu{/if}
                                                        {$is_active_submenu_class}
                                                        "
                                                    {/if}
                                                >
                                                    {if $second_level.subitems && !$navigation_accordion}<div class="dropdown-submenu__link-overlay"></div>{/if}
                                                    <a {menu_attrs attrs=$second_level.attrs.href}
                                                        {if $second_level.is_promo}
                                                            class="cm-promo-popup nav__menu-subitem {$second_level_class}"
                                                        {elseif $navigation_accordion && $second_level.subitems}
                                                            href="#{$second_level_title}_second_level"
                                                            class="{$second_level.attrs.class} nav__menu-subitem nav__menu-subitem--accordion {$second_level_class} {$is_active_submenu_class}"
                                                            data-toggle="collapse"
                                                            data-parent="#{if $m.href}{fn_url($m.href)}{else}{$first_level_title}{/if}"
                                                        {else}
                                                            {if $second_level.new_window === "YesNo::YES"|enum}target="_blank"{/if}
                                                            href="{$second_level.href|fn_url}"
                                                            class="{$second_level.attrs.class} nav__menu-subitem
                                                                {if $second_level.subitems}
                                                                    dropdown-submenu__link
                                                                {/if}
                                                                {$second_level_class}
                                                                {$is_active_submenu_class}
                                                            "
                                                        {/if}
                                                    >
                                                        <span class="nav__menu-subitem_text">{$second_level.title|default:__($second_level_title)}{if $second_level.attrs.class === "is-addon" && $show_addon_icon}{include_ext file="common/icon.tpl" class="icon-is-addon"}{/if}</span>
                                                        {if __($second_level.description) != "_`$second_level_title`_menu_description"}{if $settings.Appearance.show_menu_descriptions === "Y" && $show_menu_descriptions}<span class="hint nav__menu-subitem_hint">{__($second_level.description)}</span>{/if}{/if}
                                                    </a>

                                                    {if $second_level.subitems}
                                                        <ul
                                                            {if $navigation_accordion}
                                                                class="collapse nav__header-main-menu-submenu-second-level
                                                                    {if $second_level_title == $navigation.subsection && $first_level_title == $navigation.selected_tab}
                                                                        in
                                                                    {/if}
                                                                "
                                                            {else}
                                                                class="dropdown-menu nav__header-main-menu-submenu-second-level nav__header-main-menu-submenu-second-level--dropdown-menu"
                                                            {/if}
                                                            id="{$second_level_title}_second_level"
                                                        >
                                                            {foreach from=$second_level.subitems key=subitem_title item=sm}
                                                                <li class="nav__header-main-menu-subitem-second-level {if $sm.active}active{/if} {if $sm.is_promo}cm-promo-popup{/if} {$second_level.attrs.class}" {menu_attrs attrs=$sm.attrs.main}><a class="dropdown-submenu__link-second-level" href="{$sm.href|fn_url}" {menu_attrs attrs=$sm.attrs.href}>{$sm.title|default:__($subitem_title)}</a></li>
                                                            {/foreach}
                                                        </ul>
                                                    {/if}
                                                </{$li_tag}>
                                            {/if}
                                            {/hook}
                                        {/capture}
                                        {include file="views/block_manager/frontend_render/block.tpl"
                                            content=$smarty.capture.second_level
                                            block=$second_level
                                            tag="li"
                                            is_clearfix=false
                                            location_data=$location_data
                                            snapping_id=$second_level.id_path
                                            object_type="menu_item"
                                            parent_grid=[
                                                location_id => $second_level.section
                                            ]
                                            prefix="header_main_menu"
                                            popup_title="{__("admin_menu.edit_item_title")}: `$second_level.title|default:__($second_level_title)|truncate:100`"
                                            show_delete=!$second_level.is_main
                                            is_popup=true
                                            block_menu_compact=true
                                            return_url=$config.current_url|escape:url
                                            extra_params=[
                                                id_path => $second_level.id_path
                                            ]
                                        }
                                    {/foreach}
                                    {include file="components/menu/add_item.tpl"
                                        menu_name=$m.title|default:__($first_level_title)
                                        id=$first_level_title
                                        is_subitem=true
                                        has_items=$second_level.subitems
                                        extra_params=[
                                            id_path => $first_level_title
                                        ]
                                    }
                                </ul>
                                {/if}
                            </{$li_tag}>
                        {/if}
                        {/hook}
                    {/capture}
                    {include file="views/block_manager/frontend_render/block.tpl"
                        content=$smarty.capture.first_level
                        block=$m
                        tag="li"
                        is_clearfix=false
                        location_data=$location_data
                        snapping_id=$m.id_path
                        object_type="menu_item"
                        parent_grid=[
                            location_id => $m.section
                        ]
                        prefix="header_main_menu"
                        popup_title="{__("admin_menu.edit_item_title")}: `$m.title|default:__($first_level_title)|truncate:100`"
                        show_delete=!$m.is_main
                        is_popup=true
                        block_menu_compact=true
                        return_url=$config.current_url|escape:url
                        extra_params=[
                            id_path => $m.id_path,
                            menu_level => 1
                        ]
                    }
                {/foreach}
                {include file="components/menu/add_item.tpl"
                    menu_name=$m.title|default:__($first_level_title)
                    id=$first_level_title
                    has_items=$m.items
                    extra_params=[
                        id_path => 0,
                        menu_level => 1
                    ]
                }
                </ul>
                {/if}

                {if $auth.user_id && $navigation.static.top}
                <hr class="mobile-visible navbar-hr" />
                <ul class="nav hover-show nav-pills nav-child mobile-visible nav__header-main-menu">
                {foreach from=$navigation.static.top key=first_level_title item=m name="first_level_top"}
                    <li class="dropdown dropdown-top-menu-item{if $first_level_title == $navigation.selected_tab} active{/if} navigate-items nav__header-main-menu-item">
                        <a id="elm_menu_{$first_level_title}" href="#" class="dropdown-toggle nav__menu-item {$first_level_title}">
                            {__($first_level_title)}
                            <b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu nav__header-main-menu-submenu">

                            {foreach from=$m.items key=second_level_title item="second_level" name="sec_level_top"}
                                <li class="nav__header-main-menu-subitem {if $second_level.subitems}dropdown-submenu{/if}{if $second_level_title == $navigation.subsection} active{/if} {if $second_level.is_promo}cm-promo-popup{/if} {$second_level.attrs.class}" {menu_attrs attrs=$second_level.attrs.main}>
                                    {if $second_level.type == "title"}
                                        <a id="elm_menu_{$first_level_title}_{$second_level_title}" class="nav__menu-subitem {if $second_level.attrs.class_href}{$second_level.attrs.class_href}{/if}" {menu_attrs attrs=$second_level.attrs.href}>{$second_level.title|default:__($second_level_title)}</a>
                                    {elseif $second_level.type != "divider"}
                                        <a id="elm_menu_{$first_level_title}_{$second_level_title}" class="nav__menu-subitem {if $second_level.attrs.class_href}{$second_level.attrs.class_href}{/if}" href="{$second_level.href|fn_url}" {menu_attrs attrs=$second_level.attrs.href}>
                                            {$second_level.title|default:__($second_level_title)}
                                            {if $second_level.attrs.class === "is-addon"}{include_ext file="common/icon.tpl" class="icon-is-addon"}{/if}
                                        </a>
                                    {/if}
                                    {if $second_level.subitems}
                                        <ul class="dropdown-menu">
                                            {foreach from=$second_level.subitems key=subitem_title item=sm}
                                                <li class="nav__header-main-menu-subitem-second-level {if $sm.active}active{/if} {if $sm.is_promo}cm-promo-popup{/if} {$second_level.attrs.class}" {menu_attrs attrs=$sm.attrs.main}>
                                                    {if $sm.type == "title"}
                                                        {$sm.title|default:__($subitem_title)}
                                                    {elseif $sm.type != "divider"}
                                                        <a id="elm_menu_{$first_level_title}_{$second_level_title}_{$subitem_title}" href="{$sm.href|fn_url}" class="dropdown-submenu__link-second-level" {menu_attrs attrs=$sm.attrs.href}>{$sm.title|default:__($subitem_title)}</a>
                                                    {/if}
                                                </li>
                                                {if $sm.type == "divider"}
                                                    <li class="divider"></li>
                                                {/if}
                                            {/foreach}
                                        </ul>
                                    {/if}
                                </li>
                                {if $second_level.type == "divider"}
                                    <li class="divider"></li>
                                {/if}
                            {/foreach}
                        </ul>
                    </li>
                {/foreach}
                </ul>
                {/if}

                <hr class="mobile-visible navbar-hr" />
                <ul class="nav hover-show nav-pills nav-child mobile-visible nav__header-main-menu">
                    {$style = ($navigation_accordion) ? "accordion" : "dropdown"}

                    <!--language-->
                    {if $menu_languages|sizeof > 1}
                        {include file="common/select_object.tpl"
                            style=$style
                            selected_tab=$navigation.selected_tab
                            link_tpl=$config.current_url|fn_link_attach:"sl="
                            items=$menu_languages
                            selected_id=$smarty.const.CART_LANGUAGE
                            display_icons=true
                            key_name="name"
                            key_selected="lang_code"
                            class="languages mobile-visible"
                            dropdown_menu_class="dropdown-top-menu-item nav__header-main-menu-item"
                            dropdown_menu_item_link_class="nav__menu-subitem"
                            button_class="nav__menu-item"
                            plain_name=__("language")
                        }
                    {/if}
                    <!--end language-->

                    <!--curriencies-->
                    {if $currencies|sizeof > 1}
                        {include file="common/select_object.tpl"
                            style=$style
                            selected_tab=$navigation.selected_tab
                            link_tpl=$config.current_url|fn_link_attach:"currency="
                            items=$currencies
                            selected_id=$secondary_currency
                            display_icons=false
                            key_name="description"
                            key_selected="currency_code"
                            class="`$class` mobile-visible"
                            dropdown_menu_class="dropdown-top-menu-item nav__header-main-menu-item"
                            dropdown_menu_item_link_class="nav__menu-subitem"
                            button_class="nav__menu-item"
                            plain_name=__("currency")
                        }
                    {/if}
                    <!--end curriencies-->

                    {hook name="menu:notification_center_mobile"}
                    <!-- Notification Center -->
                    {include file="components/notifications_center/mobile_select_object.tpl"}
                    <!-- /Notification Center -->
                    {/hook}
                </ul>
                <hr class="mobile-visible navbar-hr" />

            </ul>
        <!--header_subnav--></div>
    </div>

    {* Template for mobile sidebar menu *}
    <div class="overlayed-mobile-menu mobile-visible">
        <div class="overlayed-mobile-menu__content">
            <div class="overlayed-mobile-menu__title-container">
                <h3 class="overlayed-mobile-menu-title"></h3>
            </div>

            <div class="overlayed-mobile-menu-closer">
                <button class="mobile-visible-inline overlay-navbar-close btn btn-primary">{__("go_back")}</button>
            </div>
        </div>

        <div class="overlayed-mobile-menu__content">
        </div>
        <div class="overlayed-mobile-menu-container"></div>
    </div>
    {* End of template for mobile sidebar menu *}
{/hook}
