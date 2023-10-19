{script src="js/lib/bootstrap_switch/js/bootstrapSwitch.js"}

{include file="common/previewer.tpl"}

{capture name="mainbox"}

{capture name="upload_theme"}
    {include file="views/themes/components/upload_theme.tpl"}
{/capture}

{$theme = $available_themes.current}
{$theme_name = $available_themes.current.theme_name}

{if $smarty.const.AREA === "SiteArea::ADMIN_PANEL"|enum}
    {$act_as_vendor = $auth.user_type === "UserTypes::VENDOR"|enum && $auth.act_as_user}
{/if}

{if $conflicts}
<div class="row-fluid">
    <div id="conflicts">
        <h4>{__("settings_overwrite_title")}</h4>
        <p>{__("settings_overwrite_text", ["[theme_name]" => $requested_theme_name]) nofilter}:</p>
        <form method="post" action="{"themes.set"|fn_url}">
            <input type="hidden" name="theme_name" value="{$smarty.get.theme_name}">
            <input type="hidden" name="style" value="{$smarty.get.style}">
            <div class="table-wrapper">
                <table class="table table-condensed table--relative">
                    <thead>
                        <tr>
                            <th width="1">{include file="common/check_items.tpl"}</th>
                            <th></th>
                            <th width="20%">{__("current_setting_value")}</th>
                            <th width="20%">{__("new_setting_value")}</th>
                        </tr>
                    </thead>
                    <tbody>
                    {foreach from=$conflicts key="section_name" item="setting_section"}
                        {foreach from=$setting_section.settings key="setting_name" item="setting"}
                            <tr>
                                <td>
                                    <input type="checkbox" name="settings_values[{$setting.object_id}]" value="{$setting.object_id}" class="cm-item" checked="checked">
                                </td>
                                <td>
                                    <strong>{$setting_section.name}</strong>: {$setting.name}
                                </td>
                                <td>
                                    {$setting.current_value_readable}
                                </td>
                                <td>
                                    {$setting.new_value_readable}
                                </td>
                            </tr>
                        {/foreach}
                    {/foreach}
                    </tbody>
                </table>
            </div>
            <div class="clearfix right">
                <a class="btn" href="{"themes.manage"|fn_url}">{__("cancel")}</a>
                <button class="btn btn-primary" type="submit" name="allow_overwrite" value="Y">{__("overwrite_selected_settings")}</button>
            </div>
        </form>
    </div>
</div>
{else}

<div class="themes" id="themes_list">

<div class="row-fluid">
    <div class="span12">
        <h4>{__("current_theme")}</h4>
    </div>
</div>
<div class="row-fluid">
    {capture name="add_new_picker"}
        <form action="{""|fn_url}" method="post" name="clone_theme_{$theme_name}_form" class="cm-ajax cm-comet cm-form-dialog-closer form-horizontal form-edit cm-skip-check-items">
            <input type="hidden" name="theme_data[theme_src]" value="{$theme_name}">
            <input type="hidden" name="result_ids" value="themes_list,elm_sidebar">

            <div class="add-new-object-group">
                <div class="tabs cm-j-tabs">
                    <ul class="nav nav-tabs">
                        <li id="tab_clone_theme_{$theme_name}" class="cm-js active"><a>{__("general")}</a></li>
                    </ul>
                </div>

                <div class="cm-tabs-content" id="content_tab_clone_theme_{$theme_name}">
                    <fieldset>
                        <div class="control-group">
                            <label class="control-label cm-required" for="elm_theme_dir_{$theme_name}">{__("directory")}</label>
                            <div class="controls">
                                <input type="text" id="elm_theme_dir_{$theme_name}" name="theme_data[theme_dest]" value="{$theme_name}_clone" />
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="elm_theme_title_{$theme_name}">{__("name")}</label>
                            <div class="controls">
                                <input type="text" id="elm_theme_title_{$theme_name}" name="theme_data[title]" value="{$theme.title}" />
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="elm_theme_desc_{$theme_name}">{__("description")}</label>
                            <div class="controls">
                                <textarea name="theme_data[description]" id="elm_theme_desc_{$theme_name}" cols="50" rows="4" class="span9">{$theme.description}</textarea>
                            </div>
                        </div>

                    </fieldset>
                </div>
            </div>

            <div class="buttons-container">
                {include file="buttons/save_cancel.tpl" but_name="dispatch[themes.clone]" cancel_action="close" save=true}
            </div>

        </form>
    {/capture}
    <div class="span12 section-headers">
        <h4 class="lead">{$theme.title}{if $layout.style_name}: {$layout.style_name}{/if}</h4>
        <span class="muted">{__("theme_styles_and_layouts")}</span>
    </div>
</div>
<div class="row-fluid">
    {if $theme.screenshot}
        <div id="theme_image" class="span4">
            {if $theme.styles[$layout.style_id].image}
                <img class="screenshot" src="{$theme.styles[$layout.style_id].image}">
            {else}
                <img class="screenshot" src="{$images_dir}/user_styles.png" alt="">
            {/if}

        <!--theme_image--></div>
    {/if}
    <div class="span8 theme-description" id="theme_description_container">
        {hook name="themes:current_theme_options"}
            <div class="table-responsive-wrapper">
                <table class="table table-middle table--relative table-responsive">
                    <thead>
                        <tr>
                            <th>{__("layout")}</th>
                            <th>{__("theme_editor.style")}</th>
                            {hook name="themes:list_extra_th"}{/hook}
                            <th> </th>
                        </tr>
                    </thead>
                    <tbody>
                        {$has_styles = !!$theme.styles}
                        {foreach $theme.layouts as $available_layout}
                            <tr>
                                <td data-th="{__("layout")}">{$available_layout.name}</td>
                                <td data-th="{__("theme_editor.style")}">
                                    {$styles_descr = []}
                                    {foreach $available_themes.current.styles as $style}
                                        {$styles_descr[$style.style_id] = $style.name}
                                    {/foreach}

                                    {if $has_styles}
                                        {include file="common/select_popup.tpl" id=$available_layout.layout_id status=$available_layout.style_id items_status=$styles_descr update_controller="themes.styles" status_target_id="theme_description_container,themes_list" statuses=$available_themes.current.styles btn_meta="btn-text o-status-`$o.status`"|lower default_status_text=__("none")}
                                    {else}
                                        <span class="muted">{__("theme_no_styles_text")}</span>
                                    {/if}
                                </td>
                                {hook name="themes:list_extra_td"}{/hook}
                                <td class="right btn-toolbar btn-toolbar--theme-editor" data-th="&nbsp;">
                                    {if $available_layout.is_default}
                                        {$default_layout_name = $available_layout.name}
                                        {$but_meta = "btn-small btn-primary cm-post"}
                                    {else}
                                        {$but_meta = "btn-small cm-post"}
                                    {/if}
                                    {if $has_styles}
                                        {include file="buttons/button.tpl"
                                            but_href="customization.update_mode?type=theme_editor&status=enable&s_layout={$available_layout.layout_id}&s_storefront={$storefront->storefront_id}"
                                            but_text=__("theme_editor")
                                            but_role="action"
                                            but_meta=$but_meta
                                            but_target="_blank"
                                        }
                                    {else}
                                        {include file="buttons/button.tpl"
                                            title=__("theme_editor_not_supported")
                                            but_text=__("theme_editor")
                                            but_role="btn"
                                            but_meta="btn btn-small disabled cm-tooltip"
                                        }
                                    {/if}
                                    {if !$act_as_vendor}
                                        {include file="buttons/button.tpl"
                                            but_href="customization.update_mode?type=block_manager&status=enable&s_layout={$available_layout.layout_id}&s_storefront={$storefront->storefront_id}"
                                            but_text=__("edit_layout_on_site")
                                            but_role="action"
                                            but_meta=$but_meta
                                            but_target="_blank"
                                        }
                                    {/if}
                                    {include file="buttons/button.tpl"
                                        but_href="customization.update_mode?type=live_editor&status=enable&s_layout={$available_layout.layout_id}&s_storefront={$storefront->storefront_id}"
                                        but_text=__("edit_content_on_site")
                                        but_role="action"
                                        but_meta=$but_meta
                                        but_target="_blank"
                                    }
                                </td>
                            <tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {/hook}
    <!--theme_description_container--></div>
</div>
{if $theme_logos}
    {include file="views/themes/components/logos_list.tpl" logos=$theme_logos company_id=$id default_layout_name=$default_layout_name|default:""}
{/if}

{capture name="tabsbox"}
{if $can_manage_themes}
<div id="content_installed_themes">
    <div id="themes_manage" class="themes-current clearfix">

    <div class="themes-available">
    {if $available_themes.installed}
    {foreach $available_themes.installed|array_reverse:true as $theme_id => $installed_theme}
        <div class="row-fluid">
        {if $installed_theme}
            <div class="theme-subtitle clearfix">
                <h4 id="anchor_{$installed_theme.title|replace:" ":"_"}">{$installed_theme.title}{if $theme_id == $theme_name} <span class="label label-success">{__("active")}</span>{/if}</h4>
                {if $installed_theme.dependent_themes}
                    {$can_remove = false}
                    {$tooltip = __("cannot_remove_theme_has_dependent_themes", ["[dependent_themes]" => ', '|implode:$installed_theme.dependent_themes])}
                {else}
                    {$can_remove = true}
                    {$tooltip = __("remove_theme")}
                {/if}
                {hook name="themes:remove_theme"}
                <a class="{if $can_remove}cm-confirm cm-post {/if}cm-tooltip btn pull-right btn-small"{if !$can_remove} disabled="disabled"{/if} data-ce-tooltip-position="top"{if $can_remove} href="{"themes.delete?theme_name=`$theme_id`"|fn_url}"{/if} title="{$tooltip}"> {include_ext file="common/icon.tpl" class="icon-trash"}</a>
                {/hook}
                <span class="label pull-right">{$installed_theme.layouts|count} {__("layouts")}</span>
                <span class="label pull-right">{$installed_theme.styles|count} {__("theme_editor.styles")}</span>
            </div>
            <div class="themes-list">
            {if $installed_theme.styles}
                {foreach $installed_theme.styles as $style}
                    <div class="span3">
                        <div class="theme {if $style.style_id == $layout.style_id && $layout.theme_name == $theme_id}theme-selected{/if}">
                            <div class="theme-title">
                               <span title="{$installed_theme.title}">{$installed_theme.title}: {$style.name}</span>
                            </div>
                            {if $theme_id != $runtime.layout.theme_name || $style.style_id != $layout.style_id}
                                <div class="theme-use">
                                    {if $theme_id != $runtime.layout.theme_name}
                                        {$but_text = __("activate")}
                                    {else}
                                        {$but_text = __("use_this_style")}
                                    {/if}

                                    {include file="buttons/button.tpl" but_href="themes.set?theme_name=`$theme_id`&amp;style=`$style.style_id`" but_text=$but_text but_role="action" but_meta="btn-primary cm-post"}
                                </div>
                            {/if}

                            {if $style.image}
                                <a id="image_img_{$theme_id}_{$style.style_id}" href="{$style.image}" data-ca-image-id="img_{$theme_id}_{$style.style_id}" class="cm-previewer">
                                    {if $style.style_id == $layout.style_id && $layout.theme_name == $theme_id}
                                        <span class="theme-in-use">{__("currently_in_use")}</span>
                                    {/if}
                                    <img class="screenshot" src="{$style.image}" alt="">
                                </a>
                            {else}
                                <div>
                                    {if $style.style_id == $layout.style_id && $layout.theme_name == $theme_id}
                                        <span class="theme-in-use">{__("currently_in_use")}</span>
                                    {/if}
                                    <img class="screenshot" src="{$images_dir}/user_styles.png" alt="">
                                </div>
                            {/if}
                        </div>
                    </div>
                {/foreach}

            {else}
                <div class="span3">
                    <div class="theme">
                        <div class="theme-title">
                           <span title="{$theme.title}">{$installed_theme.title}</span>
                        </div>
                        {if $theme_id != $runtime.layout.theme_name}
                            <div class="theme-use">
                                {include file="buttons/button.tpl" but_href="themes.set?theme_name=`$theme_id`" but_text=__("activate") but_role="action" but_meta="btn-primary cm-post"}
                            </div>
                        {/if}

                        {if $installed_theme.screenshot}
                            <a id="image_img_{$theme_id}" href="{$installed_theme.screenshot}" data-ca-image-id="img_{$theme_id}" class="cm-previewer"><img class="screenshot" src="{$installed_theme.screenshot}" alt=""></a>
                        {/if}
                    </div>
                </div>
            {/if}
        </div>
        {/if}
        <!--/row--></div>
    {/foreach}
    {else}
        <div class="no-items">
            {__("no_themes_available")}
        </div>
    {/if}
    </div>
</div>
</div>
<div id="content_browse_all_available_themes">

{hook name="themes:install_themes"}

    {split data=$available_themes.repo size=3 assign="splitted_themes" simple=true}
    <div class="themes-available">

    {if $available_themes.repo}
    {foreach from=$splitted_themes item="repo_themes"}
    <div class="row-fluid">
        <div class="themes-list">
        {foreach from=$repo_themes item="repo_theme" key="theme_id"}
            {if $repo_theme}
                <div class="span3">
                    <div class="theme">

                        <div class="theme-title">
                        <span title="{$theme.title}">{$repo_theme.title}</span>
                        </div>

                        <div class="theme-use">
                        {include file="buttons/button.tpl" but_href="themes.install?theme_name=`$theme_id`" but_text=__("install") but_role="action" but_meta="btn-primary cm-comet cm-ajax cm-post" but_target_id="themes_list"}
                        </div>

                        {if $repo_theme.screenshot}
                        <a id="image_img_{$theme_id}" href="{$repo_theme.screenshot}" data-ca-image-id="img_{$theme_id}" class="cm-previewer"><img class="screenshot" src="{$repo_theme.screenshot}" alt="" width="250"></a>
                        {/if}

                        <div class="theme-actions">
                            {capture name="tools_list"}

                                {if $repo_theme.screenshot}
                                <li><a id="image_img_{$theme_id}" href="{$repo_theme.screenshot}" data-ca-image-id="img_button_{$theme_id}" class="cm-previewer">{__("preview")}</a></li>
                                {/if}

                                {* <li><a href={$config.resources.demo_store_url}?demo_theme[C]={$theme_name}>{__("live_preview")}</a></li> *}
                                <li><a class="cm-comet cm-ajax cm-post" data-ca-target-id="themes_list" href="{"themes.install?theme_name=`$theme_id`"|fn_url}" data-ca-target-id="themes_list">{__("install")}</a></li>
                            {/capture}
                            {dropdown content=$smarty.capture.tools_list placement="right"}
                        </div>
                    </div>
                </div>
            {/if}
        {/foreach}
        </div>
    </div>
    {/foreach}
    {else}
        <div class="no-items">
            {__("no_themes_available")}
        </div>
    {/if}
    </div>
{/hook}

    {$theme_name = $available_themes.current.theme_name}
    <div class="hidden" id="content_elm_clone_theme_{$theme_name}" title="{__("clone_theme")}">
        {$smarty.capture.add_new_picker nofilter}
    </div>

</div>
{/if}
{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section}
<!--themes_list--></div>
{/if}

{capture name="sidebar"}
    {if $can_manage_themes}
    {hook name="themes:manage_sidebar"}
    <div class="container themes-side">

        {hook name="themes:settings"}
        <div class="sidebar-row">
            <ul class="unstyled list-with-btns">
                <li>
                    <div class="list-description">
                        {__("rebuild_cache_automatically")} {include_ext file="common/icon.tpl"
                            class="cm-tooltip icon-question-sign"
                            title=__("rebuild_cache_automatically_tooltip")
                        }
                    </div>
                    <div class="switch switch-mini cm-switch-change list-btns" id="rebuild_cache_automatically">
                        <input type="checkbox" name="compile_check" value="1" {if $dev_modes.compile_check}checked="checked"{/if}/>
                    </div>
                </li>
            </ul>
        </div>
        <script>

            (function (_, $) {
                $(_.doc).on('switch-change', '.cm-switch-change', function (e, data) {
                    var value = data.value;
                    $.ceAjax('request', fn_url("themes.update_dev_mode"), {
                        method: 'post',
                        data: {
                            dev_mode: data.el.prop('name'),
                            state: value ? 1 : 0
                        }
                    });
                });

                $.ceEvent('on', 'ce.ajaxdone', function(){
                    if ($('.switch .switch-mini').length == 0) {
                        $('.switch')['bootstrapSwitch']();
                    }
                });
            }(Tygh, Tygh.$));
        </script>
        <hr>
        {/hook}

        {hook name="themes:options"}
        <div class="form-horizontal sidebar-row clearfix">
            <h6>{__("theme_information")}</h6>
            <div class="control-group">
                <div class="control-label muted">{__("name")}</div>
                <div class="controls right">{$theme.title}</div>
            </div>
            <div class="control-group">
                <div class="control-label muted" title="/{$storefront->theme_name}">{__("directory")}</div>
                <div class="controls right"><a class="pull-right" href="{"templates.manage?selected_path={$storefront->theme_name}"|fn_url}">/{$storefront->theme_name}</a></div>
            </div>
            <div class="control-group">
                <div class="control-label muted">{__("layouts")}</div>
                <div class="controls right"><a href="{"block_manager.manage"|fn_url}">{$theme.layouts|count}</a></div>
            </div>
            <div class="control-group">
                <div class="control-label muted">{__("theme_editor.styles")}</div>
                <div class="controls right"><a href="#anchor_{$theme.title|replace:" ":"_"}">{$theme.styles|count}</a> </div>
            </div>
            <div class="control-group">
                <div class="control-label muted" >{__("developer")}</div>
                <div class="controls right">{$theme.developer}</div>
            </div>
        </div>
        {/hook}

        <hr>
        <div class="sidebar-row marketplace">
            <h6>{__("marketplace")}</h6>
            <p class="marketplace-link">{__("marketplace_find_more", ["[href]" => $config.resources.marketplace_url]) nofilter}</p>
        </div>
    </div>
    {/hook}
    {/if}
{/capture}

{capture name="adv_buttons"}
    {hook name="themes:adv_buttons"}
    {if ("ULTIMATE"|fn_allowed_for && $runtime.company_id) || ("MULTIVENDOR"|fn_allowed_for && !$runtime.company_id)}
        {include file="common/popupbox.tpl" id="upload_theme" text=__("upload_theme") title=__("upload_theme") content=$smarty.capture.upload_theme act="general" link_class="cm-dialog-auto-size" icon="icon-plus" link_text=""}
    {/if}
    {/hook}
{/capture}

{capture name="buttons"}
    {if $can_manage_themes}
    {capture name="tools_list"}
        {hook name="themes:tools_list"}
        <li>{btn type="dialog" text=__("clone_theme") target_id="content_elm_clone_theme_`$theme_name`"}</li>
        {/hook}
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
    {/if}
    {if $theme_logos}
        {include file="buttons/save.tpl" but_name="dispatch[themes.update_logos]" but_role="action" but_target_form="update_logos_form" but_meta="cm-submit"}
    {/if}
{/capture}

{/capture}
{include file="common/mainbox.tpl"
    title=__("themes")
    content=$smarty.capture.mainbox
    sidebar=$smarty.capture.sidebar
    adv_buttons=$smarty.capture.adv_buttons
    buttons=$smarty.capture.buttons
    select_storefront=true
    show_all_storefront=false
}
