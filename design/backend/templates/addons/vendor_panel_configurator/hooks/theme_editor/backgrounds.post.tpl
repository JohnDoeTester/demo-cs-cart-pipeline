{$is_demo_mode = $config.demo_mode|default:false}
{$show_theme_editor = (
    $smarty.const.AREA === "SiteArea::ADMIN_PANEL"|enum
    && $auth.act_as_area && $auth.act_as_area === "UserTypes::VENDOR"|enum
    || $is_demo_mode
)}
{if $show_theme_editor}{strip}
    {$query_params = [
        addon => "vendor_panel_configurator",
        selected_sub_section => "vendor_panel_configurator_vendor_panel_style",
        selected_section => "settings"
    ]}
    {$style_settings_url = fn_url(
        "`$config.admin_index`?dispatch=addons.update&`$query_params|http_build_query`",
        "SiteArea::ADMIN_PANEL"|enum)
    }
    <div class="ty-control-group te-bg-group">
        <div>
            <div class="te-color-picker-container te-colors clearfix">
                <span class="te-bg-title">{__("theme_editor.background_image")}&nbsp;</span>
                <div class="input-prepend">
                    <div class="te-fileuploader te-fileuploader--right clearfix">
                        <a href="{$style_settings_url}" {""}
                            target="_blank" {""}
                            class="te-btn ty-left fileinput-btn"
                        >
                            {include_ext file="common/icon.tpl"
                                class="icon-upload ty-icon-upload te-fileuploader__icon"
                            }
                            {__("theme_editor.browse")}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
{/strip}{/if}