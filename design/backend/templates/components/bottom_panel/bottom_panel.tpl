{hook name="bottom_panel:bottom_panel_main"}

{$c_url = $config.current_url|fn_url}

{hook name="bottom_panel:edition"}
    {$edition = "store"}
{/hook}

{if $runtime.controller === "products"}
    {$page = "products"}
{elseif $runtime.controller === "checkout" && $runtime.mode === "checkout"}
    {$page = "checkout"}
{/if}

{if $runtime.customization_mode.block_manager && ($location_data.is_frontend_editing_allowed || $smarty.const.ACCOUNT_TYPE === "vendor")}
    {$active_mode = "build"}
{elseif $runtime.customization_mode.live_editor}
    {$active_mode = "text"}
{elseif $runtime.customization_mode.theme_editor}
    {$active_mode = "theme"}
{else}
    {$active_mode = "preview"}
{/if}

{$is_demo_mode = $config.demo_mode|default:false}
{$show_bp_modes_on_backend = $show_bp_modes_on_backend|default:false}
{$show_theme_editor = $show_theme_editor|default:(
    fn_check_permissions("customization", "update_mode", "admin", "", ["type" => "theme_editor"], $smarty.const.AREA, $auth.user_id)
    && (($auth.user_type === "UserTypes::ADMIN"|enum) || (($settings.Vendors.can_edit_styles === "YesNo::YES"|enum) && $auth.user_type === "UserTypes::VENDOR"|enum))
    || ($is_demo_mode && $smarty.const.AREA === "SiteArea::STOREFRONT"|enum)
)}

{capture name="settings_menu_main_links"}
    {if fn_check_permissions("themes", "manage", "admin", "", [], $smarty.const.AREA, $auth.user_id)
        && ($auth.user_type !== "UserTypes::VENDOR"|enum || $settings.Vendors.can_edit_styles === "YesNo::YES"|enum)
    }
        <a class="bp-dropdown-menu__item cm-no-ajax" href="{fn_url("themes.manage", $auth.user_type)}" title="{__("bottom_panel.change_theme")}">{__("bottom_panel.change_theme")}</a>
    {/if}
    {if fn_check_permissions("block_manager", "manage", "admin", "", [], $smarty.const.AREA, $auth.user_id)
        && ($auth.user_type !== "UserTypes::VENDOR"|enum || $settings.Vendors.can_edit_blocks === "YesNo::YES"|enum)
    }
        <a class="bp-dropdown-menu__item cm-no-ajax" href="{fn_url("block_manager.manage?selected_location={$location_data.location_id}&redirect_to_block_manager=1", $auth.user_type)}" title="{__("bottom_panel.edit_layout")}">{__("bottom_panel.edit_layout")}</a>
    {/if}
    {if fn_check_permissions("templates", "manage", "admin", "", [], $smarty.const.AREA, $auth.user_id)
        && (empty($auth.company_id) || fn_check_company_permissions("templates", "manage", "", []))
    }
        <a class="bp-dropdown-menu__item cm-no-ajax" href="{fn_url("templates.manage", $auth.user_type)}" title="{__("bottom_panel.edit_template")}">{__("bottom_panel.edit_template")}</a>
    {/if}
    {if fn_check_permissions("languages", "translations", "admin", "", [], $smarty.const.AREA, $auth.user_id)
        && (empty($auth.company_id) || fn_check_company_permissions("languages", "translations", "", []))
    }
        <a class="bp-dropdown-menu__item cm-no-ajax" href="{fn_url("languages.translations", $auth.user_type)}" title="{__("bottom_panel.edit_translations")}">{__("bottom_panel.edit_translations")}</a>
    {/if}
{/capture}

{capture name="settings_menu_additional_links"}
    {if fn_check_permissions("templates", "manage", "admin", "", [], $smarty.const.AREA, $auth.user_id)
        && (empty($auth.company_id) || fn_check_company_permissions("templates", "manage", "", []))
    }
        <a class="bp-dropdown-menu__item cm-no-ajax" href="{fn_url("menus.manage", $auth.user_type)}" title="{__("bottom_panel.edit_menus")}">{__("bottom_panel.edit_menus")}</a>
    {/if}
    {if $page === "products"
        && fn_check_permissions("tabs", "manage", "admin", "", [], $smarty.const.AREA, $auth.user_id)
        && (empty($auth.company_id) || fn_check_company_permissions("tabs", "manage", "", []))
    }
        <a class="bp-dropdown-menu__item cm-no-ajax" href="{fn_url("tabs.manage", $auth.user_type)}" title="{__("bottom_panel.edit_product_tabs")}">{__("bottom_panel.edit_product_tabs")}</a>
    {/if}
{/capture}

{capture name="settings_menu"}
    {if $quick_menu}
        {foreach $quick_menu as $sect}
            <div class="bp-dropdown-menu__group">
                <span class="bp-dropdown-menu__item-text bp-dropdown-menu__item-text--head">{$sect.section.name}</span>
                {foreach $sect.subsection as $subsect}
                    <a class="bp-dropdown-menu__item cm-no-ajax" href="{fn_url($subsect.url, $auth.user_type)}" title="{$subsect.name}">{$subsect.name}</a>
                {/foreach}
            </div>
        {/foreach}
    {/if}
    {if $smarty.capture.settings_menu_main_links|trim}
        <div class="bp-dropdown-menu__group">
            {$smarty.capture.settings_menu_main_links nofilter}
        </div>
    {/if}
    {if $smarty.capture.settings_menu_additional_links|trim}
        <div class="bp-dropdown-menu__group">
            {$smarty.capture.settings_menu_additional_links nofilter}
        </div>
    {/if}
    {hook name="bottom_panel:extra_link_in_settings_menu"}
    {/hook}
{/capture}


{$utm = "utm_source=`$smarty.const.PRODUCT_NAME|lower|strip:''|replace:'-':'_'`&utm_medium=`$edition`"}

{if $runtime.is_multiple_storefronts}
    {if $smarty.request.storefront_id}
        {$storefront_id=$smarty.request.storefront_id}
    {else}
        {$storefront_id=$selected_storefront_id|default:$app["storefront.switcher.selected_storefront_id"]}
    {/if}
{/if}

<div class="bp__container">
    <div id="bp_bottom_panel"
        class="bp-panel bp-panel--{$edition} bp-panel--{$smarty.const.ACCOUNT_TYPE}"
        data-ca-bottom-pannel="true"
        data-bp-mode="demo"
        data-bp-is-bottom-panel-open="true"
        data-bp-nav-active={$smarty.const.ACCOUNT_TYPE}
        data-bp-modes-active="{$active_mode}">
        <a href="{if $smarty.const.ACCOUNT_TYPE === "customer"}{fn_url("", "C")}{else}{fn_url("", "A")}{/if}"
            class="bp-logo"
            data-bp-tooltip="true">
            {include file="backend:common/image.tpl"
                image=$logo
                image_css_class="bp-logo-img--no-color"
                show_detailed_link=false
            }
            <div class="bp-tooltip bp-tooltip--left">
            {if $smarty.const.ACCOUNT_TYPE === "customer"}
                {__("bottom_panel.go_to_home_page")}
            {else}
                {__("bottom_panel.go_to_dashboard")}
            {/if}
            </div>
        </a>
        <div class="bp-nav">
            {$redirect_area = ($auth.user_type === "UserTypes::VENDOR"|enum) ? "V" : "A"}
            <a href="{"bottom_panel.redirect?url=`$config.current_url|urlencode`&area=`$smarty.const.AREA`&to_area=C{if $storefront_id}&storefront_id=`$storefront_id`{/if}"|fn_url:$redirect_area}"
                class="bp-nav__item cm-no-ajax
                {if $smarty.const.ACCOUNT_TYPE === "customer"}
                    bp-nav__item--active
                {/if}"
                data-bp-nav-item="customer">
                <span class="bp-nav__item-text">{__("bottom_panel.storefront")}</span>
            </a>
            {if "THEMES_PANEL"|defined || $auth.user_type === "UserTypes::ADMIN"|enum}
                <a href="{fn_url("bottom_panel.login_as_admin?url=`$config.current_url|urlencode`&area=`$smarty.const.AREA`&user_id=`$auth.user_id`", "C")}" class="bp-nav__item cm-no-ajax cm-post
                    {if $smarty.const.ACCOUNT_TYPE === "admin"}
                        bp-nav__item--active
                    {/if}"
                    data-bp-nav-item="admin">
                    <span class="bp-nav__item-text">{__("bottom_panel.admin_panel")}</span>
                </a>
            {/if}
            {if "MULTIVENDOR"|fn_allowed_for}
                {if "THEMES_PANEL"|defined || $auth.user_type === "UserTypes::VENDOR"|enum}
                    <a href="{fn_url("bottom_panel.login_as_vendor?url=`$config.current_url|urlencode`&area=`$smarty.const.AREA`&user_id=`$auth.user_id`", "C")}" class="bp-nav__item cm-no-ajax cm-post
                        {if $smarty.const.ACCOUNT_TYPE === "vendor"}
                            bp-nav__item--active
                        {/if}"
                        data-bp-nav-item="vendor">
                        <span class="bp-nav__item-text">{__("bottom_panel.vendor_panel")}</span>
                    </a>
                {/if}
            {/if}
            <div id="bp-nav__active" class="bp-nav__active
                {if $smarty.const.ACCOUNT_TYPE === "customer"}
                    bp-nav__active--activated
                {/if}"></div>
        </div>

        {hook name="bottom_panel:bp_modes"}
            {if
                $smarty.const.ACCOUNT_TYPE === "customer"
                && (
                    !"MULTIVENDOR:ULTIMATE"|fn_allowed_for
                    || empty($auth.storefront_id)
                    || (int) $auth.storefront_id === (int) $runtime.storefront_id
                )
                || $show_bp_modes_on_backend && $smarty.const.ACCOUNT_TYPE === "vendor"
                && (
                    $auth.act_as_user
                    && $auth.act_as_area === "UserTypes::VENDOR"|enum
                )
                || $is_demo_mode
                && (
                    $auth.user_id
                    && $smarty.const.AREA === "SiteArea::ADMIN_PANEL"|enum
                    && $auth.user_type === "UserTypes::VENDOR"|enum
                )
            }
                <div class="bp-modes">
                    <a
                        {if $active_mode === "text"}
                            href="{fn_url("customization.disable_mode?type=live_editor&return_url={$config.current_url|urlencode}")}"
                        {elseif $active_mode === "theme"}
                            href="{fn_url("customization.disable_mode?type=theme_editor&return_url={$config.current_url|urlencode}")}"
                        {elseif $active_mode === "build"}
                            href="{fn_url("customization.disable_mode?type=block_manager&return_url={$config.current_url|urlencode}")}"
                        {else}
                            href="{fn_url("")}"
                        {/if}
                        id="settings_block_manager"
                        class="cm-no-ajax bp-modes__item bp-modes__item--preview
                        {if $active_mode === "preview"}bp-modes__item--active{/if}"
                            data-bp-modes-item="preview"
                            data-bp-tooltip="true">
                        {include file="backend:components/bottom_panel/icons/bp-modes__item--preview.svg"}
                        <div class="bp-tooltip">{__("bottom_panel.preview_mode")}</div>
                    </a>
                    {if
                        fn_check_permissions("customization", "update_mode", "admin", "", ["type" => "live_editor"], $smarty.const.AREA, $auth.user_id)
                        && $auth.user_type === "UserTypes::ADMIN"|enum
                        || ($is_demo_mode && $smarty.const.AREA === "SiteArea::STOREFRONT"|enum)
                    }
                        <a href="{fn_url("customization.update_mode?type=live_editor&status=enable&return_url={$c_url|urlencode}")}"
                            id="settings_live_editor"
                            class="cm-no-ajax bp-modes__item bp-modes__item--text
                            {if $active_mode === "text"}bp-modes__item--active{/if}"
                            data-bp-modes-item="text"
                            data-bp-tooltip="true">
                            {include file="backend:components/bottom_panel/icons/bp-modes__item--text.svg"}
                            <div class="bp-tooltip">{__("bottom_panel.text_mode")}</div>
                        </a>
                    {/if}
                    {if $show_theme_editor}
                        <a href="{if fn_is_theme_editor_available_for_user($auth) || $is_demo_mode}{fn_url("customization.update_mode?type=theme_editor&status=enable&return_url={$c_url|urlencode}")}{else}#{/if}"
                            id="settings_theme_editor"
                            class="cm-no-ajax bp-modes__item bp-modes__item--theme
                            {if $active_mode === "theme"}bp-modes__item--active{/if}
                            {if !fn_is_theme_editor_available_for_user($auth) && !$is_demo_mode}bp-modes__item--disabled{/if}"
                            data-bp-modes-item="theme"
                            data-bp-tooltip="true">
                            {include file="backend:components/bottom_panel/icons/bp-modes__item--theme.svg"}
                            <div class="bp-tooltip">
                                {__("bottom_panel.theme_mode")}
                                {if !fn_is_theme_editor_available_for_user($auth) && !$is_demo_mode}
                                    <div class="bp-tooltip__secondary">
                                        {__("bottom_panel.theme_mode.not_available")}
                                    </div>
                                {/if}
                            </div>
                        </a>
                    {/if}
                    {if
                        fn_check_permissions("customization", "update_mode", "admin", "", ["type" => "block_manager"], $smarty.const.AREA, $auth.user_id)
                        && (empty($auth.company_id) || fn_check_company_permissions("customization", "update_mode", "", ["type" => "block_manager"]))
                        && $auth.user_type !== "UserTypes::CUSTOMER"|enum
                        || $is_demo_mode
                    }
                        <a href="{if $location_data.is_frontend_editing_allowed || $smarty.const.ACCOUNT_TYPE === "vendor"}{fn_url("customization.update_mode?type=block_manager&status=enable&return_url={$c_url|urlencode}")}{else}#{/if}"
                            id="settings_block_manager"
                            class="cm-no-ajax bp-modes__item bp-modes__item--build
                            {if $active_mode === "build"}bp-modes__item--active{/if}
                            {if !$location_data.is_frontend_editing_allowed && $smarty.const.ACCOUNT_TYPE === "customer"}bp-modes__item--disabled{/if}"
                            data-bp-modes-item="build"
                            data-bp-tooltip="true">
                            {include file="backend:components/bottom_panel/icons/bp-modes__item--build.svg"}
                            <div class="bp-tooltip">
                                {__("bottom_panel.build_mode")}
                                {if $smarty.const.ACCOUNT_TYPE === "customer"&  !$location_data.is_frontend_editing_allowed}
                                    <div class="bp-tooltip__secondary">
                                        {__("bottom_panel.build_mode.not_available")}
                                    </div>
                                {/if}
                            </div>
                        </a>
                    {/if}
                    <div id="bp-modes__active" class="bp-modes__active
                        {if $active_mode === "preview"}
                            bp-modes__active--preview
                        {/if}"
                    ></div>
                </div>
            {/if}
        {/hook}
        {hook name="bottom_panel:extra_element_on_panel"}
        {/hook}
        <div class="bp-actions {if !$smarty.capture.settings_menu|trim}bp-actions--one-column{/if}">
            {if 
                $smarty.capture.settings_menu|trim
                && (!"MULTIVENDOR:ULTIMATE"|fn_allowed_for || empty($auth.storefront_id) || (int) $auth.storefront_id === (int) $runtime.storefront_id)
            }
                <div class="bp-dropdown bp-actions__item">
                    <button class="bp-dropdown-button bp-dropdown-button--animation" data-bp-toggle="dropdown"
                        data-bp-tooltip="true">
                        {include file="backend:components/bottom_panel/icons/bp-dropdown-button--settings.svg"}
                        <span class="bp-tooltip">{__("bottom_panel.settings")}</span>
                    </button>
                    <div class="bp-dropdown-menu">
                        {$smarty.capture.settings_menu nofilter}
                    </div>
                </div>
            {/if}
            {if $auth.user_type !== "UserTypes::VENDOR"|enum}
                <div class="bp-dropdown bp-actions__item">
                    <button class="bp-dropdown-button" data-bp-toggle="dropdown" data-bp-tooltip="true">
                        {include file="backend:components/bottom_panel/icons/bp-dropdown-button--help.svg"}
                        <span class="bp-tooltip">{__("bottom_panel.help")}</span>
                    </button>
                    <div class="bp-dropdown-menu">
                        <div class="bp-dropdown-menu__group">
                            <a class="bp-dropdown-menu__item cm-no-ajax" target="_blank" href="{$config.resources.docs_url|fn_link_attach:$utm|fn_url}">{__("bottom_panel.documentation")}</a>
                            <a class="bp-dropdown-menu__item cm-no-ajax" target="_blank" href="{$config.resources.forum|fn_link_attach:$utm|fn_url}">{__("bottom_panel.community_forums")}</a>
                            <a class="bp-dropdown-menu__item cm-no-ajax" target="_blank" href="{$config.resources.video_tutorials|fn_link_attach:$utm|fn_url}">{__("bottom_panel.video_tutorials")}</a>
                            <a class="bp-dropdown-menu__item cm-no-ajax" target="_blank" href="{$config.resources.faq|fn_link_attach:$utm|fn_url}">{__("bottom_panel.faq")}</a>
                        </div>
                        <div class="bp-dropdown-menu__group">
                            <a class="bp-dropdown-menu__item cm-no-ajax" target="_blank" href="{$config.resources.helpdesk_url|fn_link_attach:$utm|fn_url}">{__("bottom_panel.customer_help_desk")}</a>
                            <a class="bp-dropdown-menu__item cm-no-ajax" target="_blank" href="{$config.resources.developers_catalog|fn_link_attach:$utm|fn_url}">{__("bottom_panel.hire_a_developers")}</a>
                        </div>
                        {hook name="bottom_panel:extra_link_in_help_menu"}
                        {/hook}
                    </div>
                </div>
            {/if}
        </div>
        <button id="bp_off_bottom_panel" class="bp-close"
            data-bp-tooltip="true"
            data-bp-save-state="true">
            {include file="backend:components/bottom_panel/icons/bp-close.svg"}
            <span class="bp-tooltip bp-tooltip--right">{__("bottom_panel.hide_bottom_admin_panel")}</span>
        </button>
    </div>
    <div id="bp_bottom_buttons" class="bp-bottom-buttons
        {if !$pb_is_bottom_panel_open}
            bp-bottom-buttons--active
        {/if}">
        <button id="bp_on_bottom_panel"
            class="bp-bottom-button bp-bottom-button--logo
            {if $pb_is_bottom_panel_open}
                bp-bottom-button--disabled bp-bottom-button--disabled-panel
            {/if}"
            data-bp-bottom-buttons="panel"
            data-bp-tooltip="true">
            {include file="backend:common/image.tpl"
                image=$logo
                image_css_class="bp-logo-img bp-bottom-button-img"
                show_detailed_link=false
            }
            <span class="bp-tooltip bp-tooltip--left">{__("bottom_panel.show_bottom_admin_panel")}</span>
        </button>
        {hook name="bottom_panel:extra_element_on_closed_panel"}
        {/hook}
    </div>
</div>
{/hook}
{script src="js/tygh/bottom_panel.js"}
