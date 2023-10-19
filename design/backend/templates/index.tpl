{hook name="index:index_container"}
{$pb_is_bottom_panel_open = (
        $auth.user_type === "UserTypes::ADMIN"|enum
        || $auth.user_type === "UserTypes::VENDOR"|enum
        || "THEMES_PANEL"|defined
    )
    && $smarty.cookies.pb_is_bottom_panel_open|default:"1"
}
<!DOCTYPE html>
<html lang="en"
    dir="{$language_direction}"
    class="{if $pb_is_bottom_panel_open}bp-panel-active{/if} {$html_class}"
>
<head>
{strip}
<title>
    {if $page_title}
        {$page_title}
    {else}
        {if $navigation.selected_tab}{if $navigation.selected_tab_title}{$navigation.selected_tab_title}{else}{__($navigation.selected_tab)}{/if}{if $navigation.subsection} :: {if $navigation.subsection_title}{$navigation.subsection_title}{else}{__($navigation.subsection)}{/if}{/if} - {/if}{__("admin_panel")}
    {/if}
</title>
{/strip}
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />

{* Favicons *}
<link rel="apple-touch-icon" sizes="180x180" href="{$images_dir}/favicons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="{$images_dir}/favicons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="{$images_dir}/favicons/favicon-16x16.png">
<link rel="manifest" href="{$images_dir}/favicons/site.webmanifest">
<link rel="mask-icon" href="{$images_dir}/favicons/safari-pinned-tab.svg" color="#0fa4d6">
<meta name="msapplication-TileColor" content="#2b5797">
<meta name="theme-color" content="#ffffff">

{include file="common/styles.tpl"}
{if "DEVELOPMENT"|defined && $smarty.const.DEVELOPMENT == true}
<script data-no-defer>
window.jsErrors = [];
/*window.onerror = function(errorMessage) {
    document.write('<div data-ca-debug="1" style="border: 2px solid red; margin: 2px;">' + errorMessage + '</div>');
}*/
</script>
{/if}
</head>
{include file="buttons/helpers.tpl"}

{$class = "{if $smarty.cookies.layout_status == 1}menu-toggled{/if}{if $smarty.const.ACCOUNT_TYPE === "vendor"} vendor-area{/if}"}
<body {if $class}class="{$class}"{/if} data-ca-scroll-to-elm-offset="120">
    {hook name="index:body_content"}

    {include file="common/loading_box.tpl"}

    {if $is_bottom_panel_available}
        {include file="components/bottom_panel/bottom_panel.tpl" logo = $logos.theme.image}
    {/if}
    {include file="common/notification.tpl"}
    {include file=$content_tpl assign="content"}

    <div class="main-wrap {if $is_bottom_panel_available}bp-tygh-main-container{/if} {$main_container_class}"
        data-ca-element="mainContainer" id="main_column{if !$auth.user_id || $view_mode == 'simple'}_login{/if}">
    {if $view_mode != "simple"}
        <div class="admin-content">
            {include file="menu.tpl"}

            <div class="admin-content-wrap">
                {hook name="index:main_content"}{/hook}

                {if $is_setup_wizard_panel_available}
                    {include file="views/setup_wizard/components/widget_content.tpl"}
                {/if}

                {$content nofilter}
                {$stats|default:"" nofilter}
            </div>
        </div>
        {else}
        {$content nofilter}
    {/if}

    <!--main_column{if !$auth.user_id || $view_mode == 'simple'}_login{/if}--></div>

    {include file="common/comet.tpl"}


    {if $smarty.request.meta_redirect_url|fn_check_meta_redirect}
        <meta http-equiv="refresh" content="1;url={$smarty.request.meta_redirect_url|fn_check_meta_redirect|fn_url}" />
    {/if}

    {if $auth.user_id && 'settings'|fn_check_permissions:'change_store_mode':'admin':'POST'}
        {include file="views/settings/store_mode.tpl" show=$show_sm_dialog}
        {include file="views/settings/trial_expired.tpl" show=$show_trial_dialog}
        {include file="views/settings/license_errors.tpl" show=$show_license_errors_dialog}
    {/if}

    {hook name="index:after_content"}{/hook}

    {include file="common/loading_box.tpl"}
    {include file="common/scripts.tpl"}
    {/hook}
</body>
</html>
{/hook}
