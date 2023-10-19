{if $actual_change_log || $latest_change_log}
    <div class="hidden cm-hide-save-button" id="content_upgrades">
        <div class="form-horizontal form-edit">

            {* Actual version *}
            {if $actual_change_log}
                {$version_compare = $smarty.const.PRODUCT_VERSION|version_compare:$actual_change_log.compatibility}
                {include file="common/subheader.tpl" title=__("addons.latest_version") target="#addon_actual_version"}
                <div id="addon_actual_version" class="collapse in collapse-visible">
                    <div class="control-group">
                        <label class="control-label">{__("version")}:</label>
                        <div class="controls">
                            <div class="spaced-child">
                                <p class="inline-block">
                                    {$actual_change_log.version}
                                </p>
                                {if $version_compare >= 0 & $addon.marketplace.upgrade_available}
                                    {btn type="text"
                                        class="btn"
                                        text=__("addons.upgrade_to_version", ["[version]" => $actual_change_log.version])
                                        href="{"upgrade_center.manage"|fn_url}"
                                    }
                                {else}
                                    {btn type="button"
                                        class="btn disabled"
                                        text=__("addons.upgrade_to_version", ["[version]" => $actual_change_log.version])
                                    }
                                {/if}
                            </div>
                        </div>

                    </div>
                    {if $actual_change_log.available_since}
                        <div class="control-group">
                            <label class="control-label">{__("release_date")}:</label>
                            <div class="controls">
                                <p>{$actual_change_log.available_since|date_format:$settings.Appearance.date_format}</p>
                            </div>
                        </div>
                    {elseif $actual_change_log.timestamp}
                        <div class="control-group">
                            <label class="control-label">{__("release_date")}:</label>
                            <div class="controls">
                                <p>{$actual_change_log.timestamp|date_format:$settings.Appearance.date_format}</p>
                            </div>
                        </div>
                    {/if}
                    <div class="control-group">
                        <label class="control-label">{__("compatibility")}:</label>
                        <div class="controls">
                            <div class="spaced-child">
                                {if $addon.is_core_addon || $version_compare >= 0}
                                    <p class="inline-block">
                                        {__("addon_is_compatible", ["[product]" => $smarty.const.PRODUCT_NAME])}
                                    </p>
                                {elseif $version_compare < 0}
                                    <p class="inline-block">
                                        {__("addon_required_version", ["[version]" => $actual_change_log.compatibility])}
                                    </p>
                                    {btn type="text"
                                        class="btn"
                                        text=__("addons.upgrade_to_product_version", [
                                            "[product]" => $smarty.const.PRODUCT_NAME,
                                            "[version]" => $actual_change_log.compatibility
                                        ])
                                        href="{"upgrade_center.manage"|fn_url}"
                                    }
                                {/if}
                            </div>
                        </div>
                    </div>
                    {if $actual_change_log.readme}
                        <div class="control-group">
                            <label class="control-label">{__("what_is_new")}:</label>
                            <div class="controls">
                                {$actual_change_log.readme nofilter}
                            </div>
                        </div>
                    {/if}
                </div>
            {/if}

            {* Latest version *}
            {if $latest_change_log}
                {include file="common/subheader.tpl" title=__("addons.latest_available_for_installation_version") target="#addon_latest_version"}
                <div id="addon_latest_version" class="collapse in collapse-visible">
                    <div class="control-group">
                        <label class="control-label">{__("version")}:</label>
                        <div class="controls">
                            <div class="spaced-child">
                                <p class="inline-block">
                                    {$latest_change_log.version}
                                </p>
                                {if $addon.marketplace.upgrade_available}
                                    {btn type="text"
                                        class="btn"
                                        text=__("addons.upgrade_to_version", ["[version]" => $latest_change_log.version])
                                        href="{"upgrade_center.manage"|fn_url}"
                                    }
                                {else}
                                    {btn type="text"
                                        class="btn disabled"
                                        text=__("addons.upgrade_to_version", ["[version]" => $latest_change_log.version])
                                    }
                                {/if}
                            </div>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label">{__("release_date")}:</label>
                        <div class="controls">
                            <p>
                                {if $latest_change_log.available_since}
                                    {$latest_change_log.available_since|date_format:$settings.Appearance.date_format}
                                {else}
                                    {$latest_change_log.timestamp|date_format:$settings.Appearance.date_format}
                                {/if}
                            </p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label">{__("compatibility")}:</label>
                        <div class="controls">
                            <p>
                                {$version_compare = $smarty.const.PRODUCT_VERSION|version_compare:$compatibility}
                                {if $addon.is_core_addon || $version_compare <= 0}
                                    {__("addon_is_compatible", ["[product]" => $smarty.const.PRODUCT_NAME])}
                                {elseif $version_compare > 0}
                                    {__("addon_required_version", ["[version]" => $compatibility])}
                                {/if}
                            </p>
                        </div>
                    </div>
                    {if $latest_change_log.readme}
                        <div class="control-group">
                            <label class="control-label">{__("what_is_new")}:</label>
                            <div class="controls">
                                {$latest_change_log.readme nofilter}
                            </div>
                        </div>
                    {/if}
                </div>
            {/if}
        </div>
    <!--content_updates--></div>
{/if}
