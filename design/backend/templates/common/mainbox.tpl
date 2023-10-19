{if !$sidebar_position}
    {$sidebar_position = "right"}
{/if}

{if !$sidebar_icon}
    {$sidebar_icon = "icon-chevron-left"}
{/if}

{if !isset($select_storefront)}
    {if (fn_allowed_for('MULTIVENDOR:ULTIMATE'))}
        {if !$runtime.is_multiple_storefronts}
            {$select_storefront = false}
        {/if}
        {$selected_storefront_id = $selected_storefront_id|default:$app["storefront"]->storefront_id}
    {else}
        {$select_storefront = $runtime.is_multiple_storefronts}
    {/if}
{/if}

{if $anchor}
<a name="{$anchor}"></a>
{/if}

{if "THEMES_PANEL"|defined}
    {$sticky_padding_on_actions_panel = 80}
    {$sticky_top_on_actions_panel = 80}
{else}
    {$sticky_padding_on_actions_panel = 45}
    {$sticky_top_on_actions_panel = 45}
{/if}

{$enable_sticky_scroll = $enable_sticky_scroll|default:true}
{$navigation_accordion = $navigation_accordion|default:false}

<script>
// Init ajax callback (rebuild)
var menu_content = {$convertible_data|default:"''"|unescape nofilter};
</script>

{capture name="sidebar_content" assign="sidebar_content"}
    {if $navigation && $navigation.dynamic.sections}
        <div class="sidebar-row">
            <ul class="nav nav-list">
                {foreach from=$navigation.dynamic.sections item=m key="s_id" name="first_level"}
                    {hook name="index:dynamic_menu_item"}
                        {if $m.type == "divider"}
                            <li class="divider"></li>
                            {else}
                                {if $m.href|fn_check_view_permissions:{$method|default:"GET"}}
                                    <li class="{if $m.js == true}cm-js{/if}{if $smarty.foreach.first_level.last} last-item{/if}{if $navigation.dynamic.active_section == $s_id} active{/if}"><a href="{$m.href|fn_url}">{$m.title}</a></li>
                                {/if}
                        {/if}
                    {/hook}
                {/foreach}
            </ul>
        </div>
    <hr>
    {/if}
    {$sidebar nofilter}

    {notes assign="notes"}{/notes}
    {if $notes}
        {foreach from=$notes item="note" key="sidebox_title"}
            {capture name="note_title"}
                {if $title == "_note_"}{__("notes")}{else}{$title}{/if}
            {/capture}
            {include file="common/sidebox.tpl" content=$note title=$smarty.capture.note_title}
        {/foreach}
    {/if}
{/capture}

<!-- Actions -->
{hook name="index:actions_wrapper"}
    <div class="actions nav__actions {if $enable_sticky_scroll}cm-sticky-scroll{/if}"
        data-ca-stick-on-screens="sm-large,md,md-large,lg,uhd"
        data-ca-top="{$sticky_top_on_actions_panel}"
        data-ca-padding="{$sticky_padding_on_actions_panel}"
        id="actions_panel">
        <div class="actions__wrapper {if $runtime.is_current_storefront_closed || $runtime.are_all_storefronts_closed}navbar-inner--disabled{/if}">
        {hook name="index:actions"}
        <div class="btn-bar-left nav__actions-back mobile-hidden">
            {include file="common/last_viewed_items.tpl"}
        </div>
        <div class="btn-bar-left overlay-navbar-open-container mobile-visible">
            <a role="button" class="btn mobile-visible mobile-menu-toggler" data-ca-mobile-menu-is-convert-dropdown="true">
                {include_ext file="common/icon.tpl" class="icon icon-align-justify mobile-visible-inline overlay-navbar-open"}
                <span class="cc-notify-marker-container" data-ca-notifications-mark></span>
            </a>
        </div>
        <div class="title nav__actions-title {if $select_storefront}title--storefronts{/if}">
            {if isset($title_start) && isset($title_end)}
                <h2 class="title__heading {if $select_storefront}title__heading--storefronts{/if}"
                    title="{$title_alt|default:"`$title_start` `$title_end`"|strip_tags|strip|html_entity_decode}"
                >
                    <span class="title__part-start mobile-hidden">{$title_start}: </span>
                    <span class="title__part-end">{$title_end|strip_tags|html_entity_decode}</span>
                </h2>
            {else}
                <h2 class="title__heading {if $select_storefront}title__heading--storefronts{/if}" title="{$title_alt|default:$title|strip_tags|strip|html_entity_decode}">{$title|default:"&nbsp;"|sanitize_html nofilter}</h2>
            {/if}

            <!--mobile quick search-->
            <div class="mobile-visible pull-right search-mobile-group cm-search-mobile-group"
                data-ca-search-mobile-back="#search_mobile_back"
                data-ca-search-mobile-btn="#search_mobile_btn"
                data-ca-search-mobile-block="#search_mobile_block"
                data-ca-search-mobile-input="#gs_text_mobile"
            >
                <button class="btn search-mobile-btn" id="search_mobile_btn">{include_ext file="common/icon.tpl" class="icon-search search-mobile-icon"}</button>
                <div class="search search-mobile-block cm-search-mobile-search hidden" id="search_mobile_block">
                    <button class="search-mobile-back" type="button" id="search_mobile_back">{include_ext file="common/icon.tpl" class="icon-remove"}</button>
                    <button class="search_button search-mobile-button" type="submit" id="search_button_mobile" form="global_search">{include_ext file="common/icon.tpl" class="icon-search"}</button>
                    <label for="gs_text_mobile" class="search-mobile-label"><input type="text" class="cm-autocomplete-off search-mobile-input" id="gs_text_mobile" name="q" value="{$smarty.request.q}" form="global_search" placeholder="{__("admin_search_field")}" disabled /></label>
                </div>
            </div>
            <!--mobile end quick search-->

            {if $languages|sizeof > 1}
            <!--language-->
            <span class="title__lang-selector mobile-visible">
                {include
                    file="common/select_object.tpl"
                    style="dropdown"
                    link_tpl=$config.current_url|fn_link_attach:"sl="
                    link_suffix="descr_sl="
                    items=$languages
                    selected_id=$smarty.const.CART_LANGUAGE
                    display_icons=true
                    key_name="name"
                    key_selected="lang_code"
                    class="languages btn"
                    disable_dropdown_processing=true
                }
            </span>
            <!--end language-->
            {/if}

            </div>

            {if $select_storefront}
                {include file="views/storefronts/components/picker/presets.tpl"
                    input_name=$storefronts_picker_name
                    item_ids=[$runtime.company_data.company_id]
                    show_empty_variant=$show_empty_variant
                    empty_variant_text=__("all_vendors")
                    select_storefront=$select_storefront
                    show_all_storefront=$show_all_storefront
                }
            {/if}

            <div class="{$main_buttons_meta} btn-bar btn-toolbar nav__actions-bar dropleft" {if $content_id}id="tools_{$content_id}_buttons"{/if}>
                {hook name="index:toolbar"}
                {/hook}

                {if $navigation.dynamic.actions}
                    {capture name="tools_list"}
                        {foreach from=$navigation.dynamic.actions key=title item=m name="actions"}
                            <li><a href="{$m.href|fn_url}" class="{$m.meta}" target="{$m.target}">{__($title)}</a></li>
                        {/foreach}
                    {/capture}
                    {include file="common/tools.tpl" hide_actions=true tools_list=$smarty.capture.tools_list link_text=__("choose_action")}
                {/if}

                {$buttons nofilter}

                {if $adv_buttons}
                <div class="nav__actions-adv-buttons adv-buttons" {if $content_id}id="tools_{$content_id}_adv_buttons"{/if}>
                {$adv_buttons nofilter}
                {if $content_id}<!--tools_{$content_id}_adv_buttons-->{/if}</div>
                {/if}

            {if $content_id}<!--tools_{$content_id}_buttons-->{/if}</div>
            {/hook}
        </div>
    <!--actions_panel--></div>
{/hook}

<div class="admin-content-wrapper {$mainbox_content_wrapper_class|default:""}">

<!-- Sidebar left -->
{if !$no_sidebar && $sidebar_content|trim != "" && $sidebar_position == "left"}
<div class="sidebar sidebar-left cm-sidebar {$sidebar_meta}" id="elm_sidebar">
    <div class="sidebar-toggle">
        <span class="sidebar-text">{__("sidebar")}</span>
        {include_ext file="common/icon.tpl"
            class="`$sidebar_icon` sidebar-icon"
        }
    </div>
    <div class="sidebar-wrapper">
    {$sidebar_content nofilter}
    </div>
<!--elm_sidebar--></div>
{/if}

{* DO NOT REMOVE HTML comment below *}
<!--Content-->
<div class="content page-content {if $no_sidebar} content-no-sidebar{/if}{if $sidebar_content|trim == ""} no-sidebar{/if} {if "ULTIMATE"|fn_allowed_for}ufa{/if}" {if $box_id}id="{$box_id}"{/if}>
    <div class="content-wrap">
    {hook name="index:content_top"}
        {if $select_languages && $languages|sizeof > 1}
            <div class="content-variant-wrap content-variant-wrap--language language-wrap">
                <h6 class="muted">{__("language")}:</h6>
                {include file="common/select_object.tpl"
                    style="graphic"
                    link_tpl=$config.current_url|fn_link_attach:"descr_sl="
                    items=$languages
                    selected_id=$smarty.const.DESCR_SL
                    key_name="name"
                    suffix="content"
                    display_icons=true
                }
            </div>
        {/if}

        {if $tools}{$tools nofilter}{/if}

        {if $title_extra}<div class="title">-&nbsp;</div>
            {$title_extra nofilter}
        {/if}

        {if $extra_tools && $extra_tools|trim}
            <div class="extra-tools">
                {$extra_tools nofilter}
            </div>
        {/if}
    {/hook}

    {if $content_id}<div id="content_{$content_id}">{/if}
        {$content|default:"&nbsp;" nofilter}
    {if $content_id}<!--content_{$content_id}--></div>{/if}

    {if $box_id}<!--{$box_id}-->{/if}</div>
</div>
{* DO NOT REMOVE HTML comment below *}
<!--/Content-->


<!-- Sidebar -->
{if !$no_sidebar && $sidebar_content|trim != "" && $sidebar_position == "right"}
{hook name="index:right_sidebar"}
{$is_open_state_sidebar_save = $is_open_state_sidebar_save|default:false}

<div class="sidebar cm-sidebar{if $is_open_state_sidebar_save} cm-sidebar-open-state-save{/if} {$sidebar_meta}" id="elm_sidebar">
    <div class="sidebar-toggle">
        <span class="sidebar-text">{__("sidebar")}</span>
        {include_ext file="common/icon.tpl"
            class="`$sidebar_icon` sidebar-icon"
        }
    </div>
    <div class="sidebar-wrapper">
    {$sidebar_content nofilter}
    </div>
<!--elm_sidebar--></div>
{/hook}
{/if}

</div>

<script>
    var ajax_callback_data = menu_content;
</script>
{script src="js/tygh/sidebar.js"}
