{hook name="addons:subscription_tab"}
<div class="hidden cm-hide-save-button" id="content_subscription">
    <form action="{""|fn_url}" method="post" name="update_addon_{$_addon}_subs_form" class=" form-edit form-horizontal" enctype="multipart/form-data">
        <input type="hidden" name="selected_section" value="{$smarty.request.selected_section}" />
        <input type="hidden" name="addon" value="{$smarty.request.addon}" />
        <input type="hidden" name="storefront_id" value="{$smarty.request.storefront_id}" />
        {if $smarty.request.return_url}
            <input type="hidden" name="redirect_url" value="{$smarty.request.return_url}" />
        {/if}

        {include file="common/subheader.tpl" title=__("license") target="#license"}
        <div id="license" class="collapse in collapse-visible">
            <div class="control-group">
                <label class="control-label">{__("license_number")}:</label>
                <div class="controls">
                    <input type="text" name="marketplace_license_key"
                            value="{$addon.marketplace_license_key}"
                            size="30"/>
                    <p class="muted description">{__("addon_license_key_tooltip")}</p>
                </div>
            </div>
        </div>

    </form>
<!--content_subscription--></div>
{/hook}