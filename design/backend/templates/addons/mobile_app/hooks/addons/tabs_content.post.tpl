{if $_addon === "mobile_app"}
    {capture name="layouts"}
        <div class="clearfix">
            {include file="common/subheader.tpl" title=__("layouts")}
            {$url = "block_manager.manage"}

            {if $runtime.is_multiple_storefronts}
                {if "ULTIMATE"|fn_allowed_for}
                    {$url = "`$url`?switch_company_id=`$selected_storefront_id`"}
                {else}
                    {$url = "`$url`?s_storefront=`$selected_storefront_id`"}
                {/if}
            {/if}

            {if $layouts_id}
                {$url = "`$url`?s_layout=`$layouts_id`"}
            {/if}

            <div class="control-group">
                <a href="{fn_url($url)}"
                   target="_blank"
                >{__("mobile_app.edit_app_layouts")}</a>
            </div>
        </div>
    {/capture}

    <div id="content_changeable_settings" class="hidden">

        {__("mobile_app.section.changeable_settings_description")}

        <hr>

        <div class="cm-j-tabs cm-track tabs" data-ca-tabs-input-name>
            <ul class="nav nav-tabs">
                <li id="mobile_app_tab_translations" class="cm-js {if $active_tab === "mobile_app_tab_translations"}active{/if}">
                    <a>{__("translations")}</a>
                </li>
                <li id="mobile_app_tab_promotion" class="cm-js {if $active_tab === "mobile_app_tab_promotion"}active{/if}">
                    <a>{__("mobile_app.promotion")}</a>
                </li>
                <li id="mobile_app_tab_layouts" class="cm-js {if $active_tab === "mobile_app_tab_layouts"}active{/if}">
                    <a>{__("layouts")}</a>
                </li>
            </ul>
        </div>

        <div class="cm-tabs-content">
            <div id="content_mobile_app_tab_translations" class="hidden">
                {$smarty.capture.translations nofilter}
            </div>
            <div id="content_mobile_app_tab_promotion" class="hidden">
                {$smarty.capture.promotion nofilter}
            </div>
            <div id="content_mobile_app_tab_layouts" class="hidden">
                {$smarty.capture.layouts nofilter}
            </div>
        </div>

    </div>
{/if}
