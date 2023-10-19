<div id="sw_design_extra">

    <div class="sw-columns-block">
        {include file="views/storefronts/components/picker/presets.tpl"
            config = ["current_url" => fn_url("index.index")]
            item_ids=[$runtime.company_data.company_id]
            show_empty_variant=true
            empty_variant_text=__("all_vendors")
            selected_storefront_id=$app.storefront->storefront_id
            show_all_storefront=false
            storefront_picker_link_suffix="#sw_wizard_opener"
        }
    </div>

    <div class="sw-columns-block sw-themes sw-theme-and-logo">
        {$theme = $available_themes.current}
        {$theme_name = $available_themes.current.theme_name}
        {$style = $theme.styles[$layout.style_id]}

        <div class="first-column themes-available" id="sw_selected_design">
            <div>
                <h2 class="sw-block-title">{__("sw.selected_design")}</h2>
                <span class="pull-right sw-current-theme" title="{$theme.title}">{$theme.title}: {$style.name}</span>
            </div>

            <div class="theme theme-selected">
                <div class="theme-use">
                    <div>
                        {include file="buttons/button.tpl" but_href="customization.update_mode?type=theme_editor&status=enable&s_layout=`$layout.layout_id`" but_text=__("sw.edit_design") but_role="action" but_target="_blank" but_meta="btn-primary cm-post"}
                    </div>
                    <div>
                        {include file="buttons/button.tpl" but_href="customization.update_mode?type=live_editor&status=enable" but_text=__("edit_content_on_site") but_role="action" but_meta="btn-primary cm-post" but_target="_blank"}
                    </div>
                </div>
                <div class="sw-current-theme-preview">
                    <img class="screenshot" src="{$style.image|default:"`$images_dir`/user_styles.png"}" alt="">
                </div>
            </div>
        <!--sw_selected_design--></div>

        <div class="second-column sw-logo-manage" id="sw_logos_form">
            <form name="logos_form" class="cm-ajax" action="{""|fn_url}" method="post" enctype="multipart/form-data">
                <input type="hidden" name="dispatch" value="setup_wizard.update_logos" />
                <input type="hidden" name="result_ids" value="sw_logos_form" />
                <div class="sw-tabs cm-sw-tabs">
                    <h2 class="sw-block-title">{__("sw.upload_logo")}</h2>
                    <ul class="pull-right sw-pills">
                        {foreach $cse_logo_types as $type => $logo}
                            <li {if $type == "theme"}class="active"{/if}><a data-ca-target-id="elm_sw_logo_section_{$type}" title="{__("sw.logo_`$type`")}"><span>{__("sw.logo_`$type`")}</span></a></li>
                        {/foreach}
                    </ul>
                    <br /><span>({__("sw.logo_recommended_size_text", ["[height]" => 210, "[width]" => 45])})</span>

                    {foreach $cse_logo_types as $type => $logo}

                        {if $cse_logos && $cse_logos.$type}
                            {$id=$cse_logos.$type.logo_id}
                            {$image=$cse_logos.$type.image}
                        {else}
                            {$id=0}
                            {$image=[]}
                        {/if}

                        <div class="{if $type != "theme"}hidden{/if} cm-sw-tab-contents" id="elm_sw_logo_section_{$type}">
                            <input type="text" class="hidden" name="sw_logotypes_image_data[{$id}][type]" value="M">
                            <input type="text" class="hidden" name="sw_logotypes_image_data[{$id}][object_id]" value="{$id}">
                            <div class="attach-images">
                                <div class="upload-box clearfix">

                                    {include file="views/setup_wizard/components/fileuploader.tpl" var_name="sw_logotypes_image_icon[`$id`]"}

                                    <div class="pull-right">
                                        <button class="btn btn-primary ladda-button" type="submit" data-style="slide-right"><span class="ladda-label">{__("save")}</span></button>
                                    </div>

                                    <div class="image-wrap pull-left">
                                        <div class="sw-image">
                                            <div class="sw-bg-image cm-sw-logo" data-ca-image-area="{$type}" style="background-image: url('{$image.image_path}'); background-repeat: no-repeat; background-position: center center;">
                                                <div class="cm-sw-dark-bg sw-dark-bg sw-bg-switcher"></div>
                                                <div class="cm-sw-light-bg sw-light-bg sw-bg-switcher"></div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    {/foreach}
                </div>
            </form>
        <!--sw_logos_form--></div>
    </div>

    <div class="sw-columns-block sw-themes sw-themes-list" id="sw_themes_form">

        <div class="themes-available">
            <h2 class="sw-block-title">{__("sw.select_right_design")}</h2>

            {foreach $available_themes.installed as $theme_name => $installed_theme}

                {if $installed_theme}

                    {if $installed_theme.styles}
                        {foreach $installed_theme.styles as $style}
                            <div class="sw-theme-preview">

                                <div class="theme {if $style.style_id == $layout.style_id && $layout.theme_name == $theme_name}theme-selected{/if}">
                                    <div class="theme-title">
                                        <span title="{$installed_theme.title}">{$installed_theme.title}: {$style.name}</span>
                                    </div>
                                    {if $style.style_id == $layout.style_id && $layout.theme_name == $theme_name}
                                        <div class="theme-use">
                                            <div class="sw_selected_design_text">
                                                <span>{__("sw.selected_design")}</span>
                                            </div>
                                        </div>
                                    {else}
                                        <div class="theme-use">
                                            <div>
                                                {include file="buttons/button.tpl" but_href="setup_wizard.update_themes?theme_name=`$theme_name`&style=`$style.style_id`" but_text=__("sw.activate") but_role="action" but_meta="btn-primary ladda-button cm-ajax cm-post" but_target_id="sw_themes_form,sw_selected_design,sw_logos_form"}
                                                <div class="sw-notifications-box"></div>
                                            </div>
                                        </div>
                                    {/if}
                                    <img class="screenshot" src="{$style.image|default:"`$images_dir`/user_styles.png"}" alt="">
                                </div>
                            </div>
                        {/foreach}

                    {/if}
                {/if}

            {foreachelse}
                <div class="no-items">
                    {__("no_themes_available")}
                </div>
            {/foreach}

        </div>
    <!--sw_themes_form--></div>
<!--sw_design_extra--></div>
