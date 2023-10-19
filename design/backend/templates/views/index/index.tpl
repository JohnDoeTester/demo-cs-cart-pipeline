{script src="js/tygh/tabs.js"}
{script src="js/tygh/backend/dashboard.js"}

{capture name="mainbox"}
    {if $config.simple_dashboard && $show_dashboard_preloader}
        {include file="views/index/components/simple_dashboard_preloader.tpl"}
    {elseif $config.simple_dashboard}
        {include file="views/index/components/simple_dashboard.tpl"}
    {elseif $show_dashboard_preloader}
        {include file="views/index/components/dashboard_preloader.tpl"}
    {else}
        {include file="views/index/components/dashboard.tpl"}
    {/if}
{/capture}

{include
    file="common/mainbox.tpl"
    buttons=$smarty.capture.buttons
    no_sidebar=true
    title=__("dashboard")
    content=$smarty.capture.mainbox
    tools=$smarty.capture.tools
    box_id="dashboard_content"
    select_storefront=true
    show_all_storefront=true
    storefront_switcher_param_name="storefront_id"
    selected_storefront_id=$storefront_id
}

{if "ULTIMATE"|fn_allowed_for}
    <input type="hidden" name="switch_company_id" id="switch_company_id" value="{$storefront_id}">
{else}
    <input type="hidden" name="storefront_id" id="storefront_id" value="{$storefront_id}">
{/if}

{hook name="index:welcome_dialog"}
{if $show_welcome}
    <div class="hidden cm-dialog-auto-open cm-dialog-auto-size" title="{__("installer_complete_title")}" id="after_install_dialog" data-ca-dialog-class="welcome-screen-dialog">
        {assign var="company" value="1"|fn_get_company_data}
        {if "ULTIMATE"|fn_allowed_for}
            {$link_storefront = "http://{$company.storefront}"}
        {else}
            {$link_storefront = "{$config.http_location|fn_url}"}
        {/if}
        <div class="welcome-screen">
            <p>
                {$user_data = $auth.user_id|fn_get_user_info}
                {__("welcome_screen.administrator_info", ['[email]' => $user_data.email])}
            </p>
            <div class="welcome-location-wrapper clearfix">
                <div class="welcome-location-block pull-left center">
                    <h4 class="install-title">{__("admin_panel")}</h4>
                    <div class="welcome-screen-location welcome-screen-admin">
                        <div class="welcome-screen-overlay">
                            <a class="btn cm-dialog-closer welcome-screen-overlink">{__("welcome_screen.go_admin_panel")}</a>
                        </div>
                    </div>
                    <div class="welcome-screen-arrow"></div>
                    <p>
                        {__("welcome_screen.go_settings_wizard")}
                    </p>
                    {$c_url = $config.current_url|escape:"url"}
                    <a class="cm-dialog-opener cm-ajax btn btn-primary strong" data-ca-target-id="content_settings_wizard" title="{__("settings_wizard")}" href="{"settings_wizard.view?return_url=`$c_url`"|fn_url}" target="_blank">{__("welcome_screen.run_settings_wizard")}</a>
                </div>
                <div class="welcome-location-block pull-right center">
                    <h4 class="install-title">{__("storefront")}</h4>
                    <div class="welcome-screen-location welcome-screen-store">
                        <div class="welcome-screen-overlay">
                            <a class="btn welcome-screen-overlink" href="{$link_storefront}" target="_blank">{__("welcome_screen.go_storefront")}</a>
                        </div>
                    </div>
                    <div class="welcome-screen-arrow"></div>
                    <p>
                        {__("welcome_screen.learn_more_configuration")}
                    </p>
                    <a class="kbase-link" href="{$config.resources.knowledge_base}" target="_blank">{__("welcome_screen.knowledge_base")}</a>
                </div>
            </div>
            <div class="welcome-screen-social center">
                <p>
                    {__("welcome_screen.thanks", ["[product]" => $smarty.const.PRODUCT_NAME])}
                </p>
                {include file="common/share.tpl"}
            </div>
            {if "ULTIMATE:FREE"|fn_allowed_for}
                {$smarty.session.stats|reset|default:"" nofilter}
            {/if}
        </div>
    </div>
{/if}
{/hook}
