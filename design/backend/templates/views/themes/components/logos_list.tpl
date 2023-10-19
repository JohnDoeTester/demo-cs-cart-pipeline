<div class="logos-section">
    <form action="{""|fn_url}" method="post" name="update_logos_form" id="update_logos_form" enctype="multipart/form-data">
        <div class="row-fluid">
            <div class="span12" id="title_theme_logo">
                <h4>{if $default_layout_name}{$default_layout_name}: {/if}{__("logos")}</h4>
            </div>
        </div>
        {include file="views/themes/components/logo_item.tpl" type="theme" logo=$logos.theme}
        <div class="row-fluid">
            <div class="span12">
                {include file="buttons/button.tpl"
                    but_text=__("show_extra_logos")
                    but_role="action"
                    but_id="on_attach_additional_logos"
                    but_meta="cm-combination {if $show_all_logos} hidden{/if}"
                }
            </div>
        </div>
        <div id="attach_additional_logos" name="attach_additional_logos"{if !$show_all_logos} class="hidden"{/if}>
            {foreach $logos as $type => $logo}
                {if $type == "theme"}
                    {continue}
                {/if}

                {include file="views/themes/components/logo_item.tpl"
                    type=$type
                    logo=$logo
                    show_alt=($type != "favicon")
                    show_hidpi_checkbox=($type != "favicon")
                    description=($type == "favicon") ? __("theme_editor.favicon_size") : ''
                }
            {/foreach}
            <div class="row-fluid">
                <div class="span12">
                    {include file="buttons/button.tpl"
                        but_text=__("hide_extra_logos")
                        but_role="action"
                        but_id="off_attach_additional_logos"
                        but_meta="cm-combination"
                    }
                </div>
            </div>
        </div>
    </form>
</div>
