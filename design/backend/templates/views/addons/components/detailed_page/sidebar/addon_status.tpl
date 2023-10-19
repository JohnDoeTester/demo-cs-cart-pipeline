<div class="sidebar-row">

    {* Addon icon *}
    <div class="shift-button" id="addon_icon">
        {include file="views/addons/components/addons/addon_icon.tpl"
            addon=$addon
            icon_large=true
        }
    <!--addon_icon--></div>

    {* Addon status *}
        <div class="control-group sidebar__stats" id="addon_status">
            <label class="control-label sidebar__label">{__("status")}:</label>
            <div class="controls">
                {if $addon_install_datetime && $addon.snapshot_correct}
                    {if $is_storefront_admin}
                        {if $addon.status == "ObjectStatuses::ACTIVE"|enum}
                            {__("active")}
                        {else}
                            {__("addons.disabled")}
                        {/if}
                    {else}
                        {$submit_url = "addons.update_status?id={$addon.addon}&return_url={"addons.update&addon={$addon.addon}"|escape:url}"}
                        {include file="common/switcher.tpl"
                            meta = "company-switch-storefront-status-button storefront__status"
                            checked = $addon.status == "ObjectStatuses::ACTIVE"|enum
                            extra_attrs = [
                                "data-ca-submit-url" => $submit_url,
                                "data-ca-opened-status" => {"ObjectStatuses::ACTIVE"|enum},
                                "data-ca-closed-status" => {"ObjectStatuses::DISABLED"|enum},
                                "data-ca-result-ids" => "addon_icon,addon_status"
                            ]
                        }
                    {/if}
                {elseif $addon_install_datetime && !$addon.snapshot_correct}
                    {__("addons.disabled")}
                {else}
                    {__("addons.not_installed")}
                {/if}
        </div>
    <!--addon_status--></div>

    {* Favorite status *}
    <div class="control-group sidebar__stats" id="addon_favorite">
        <label class="control-label sidebar__label">{__("favorites")}:</label>
        <div class="controls sidebar__controls">
            {include file="views/addons/components/addons/addon_favorite.tpl"
                a=$addon
                result_ids="addon_favorite"
                detailed=true
            }
        </div>
    <!--addon_favorite--></div>

    {* Addon version *}
    <div class="control-group sidebar__stats">
        <label class="control-label sidebar__label">{__("version")}:</label>
        <div class="controls sidebar__controls">
            <p>{$addon_version}</p>
        </div>
    </div>

    {* Update status *}
    {if $addon.marketplace.upgrade_available}
        <div class="control-group sidebar__stats">
            <label class="control-label sidebar__label">{__("upgrade")}:</label>
            <div class="controls sidebar__controls">
                <p class="text-success">
                    {__("available")}
                </p>
            </div>
        </div>
    {/if}

    {* License status *}
    {if !$addon.is_core_addon && isset($license_expires)}
    <div class="control-group sidebar__stats">
        <label class="control-label sidebar__label">{__("license_expires")}:</label>
        <div class="controls sidebar__controls">
            {if $license_expires === "0"}
                <p>{__("never")}</p>
            {else}
                <p>{$license_expires|date_format:$settings.Appearance.date_format}</p>
            {/if}
        </div>
    </div>
    {/if}

    {* Verified status *}
    {if $addon.is_core_addon || $addon.identified}
        <div class="control-group sidebar__stats">
            <label class="control-label sidebar__label">{__("developer")}:</label>
            <div class="controls sidebar__controls">
                {if $addon.is_core_addon}
                    <p>{__("core_addon")}</p>
                {else}
                    <p class="text-success">{__("verified")}</p>
                {/if}
            </div>
        </div>
    {/if}
</div>
