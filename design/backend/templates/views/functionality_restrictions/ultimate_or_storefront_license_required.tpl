{if "ULTIMATE"|fn_allowed_for && $store_mode != "ultimate"}
    <div id="restriction_promo_dialog" class="restriction-promo">
        {$suffix = ""|fn_get_product_state_suffix}
        {__("max_storefronts_reached.`$suffix`", [
            "[product]" => $smarty.const.PRODUCT_NAME,
            "[ultimate_license_url]" => $config.resources.ultimate_license_url
        ]) nofilter}
        <div class="restriction-promo__wrapper">
            <div class="restriction-features">
                <div class="restriction-feature restriction-feature_storefronts">
                    <h2>{__("ultimate_license", ["[product]" => $smarty.const.PRODUCT_NAME])}</h2>

                    {__("new_text_ultimate_license_required", [
                        "[product]" => $smarty.const.PRODUCT_NAME,
                        "[ultimate_license_url]" => $config.resources.ultimate_license_url
                    ]) nofilter}

                </div>
            </div>
        </div>

        <div class="center">
            <a class="restriction-update-btn restriction-update-btn--single" href="{$config.resources.ultimate_license_url}" target="_blank">{__("upgrade_license")}</a>
        </div>

    </div>
{/if}