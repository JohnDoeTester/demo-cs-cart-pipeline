{if "MULTIVENDOR"|fn_allowed_for && $store_mode != "plus" && $store_mode != "ultimate"}
    <div id="restriction_promo_dialog" class="restriction-promo">

        <div class="restriction-promo__text">
            {__("text_mve_ultimate_or_plus_license_required", [
                "[product]" => $smarty.const.PRODUCT_NAME,
                "[mve_plus_license_url]" => $config.resources.mve_plus_license_url,
                "[mve_ultimate_license_url]" => $config.resources.mve_ultimate_license_url
            ]) nofilter}
        </div>

        <div class="restriction-promo__features">
            <div class="restriction-promo__wrapper-mv">

                <div class="restriction-features">
                    <div class="restriction-feature restriction-feature_ultimate">
                        <h2>{__("mve_ultimate_license", ["[product]" => $smarty.const.PRODUCT_NAME])}</h2>

                        {__("text_mve_ultimate_license_required", [
                            "[product]" => $smarty.const.PRODUCT_NAME,
                            "[mve_ultimate_license_url]" => $config.resources.mve_ultimate_license_url
                        ]) nofilter}

                    </div>
                </div>

                <div class="center mobile-visible">
                    <a class="restriction-update-btn" href="{$config.resources.mve_ultimate_license_url}" target="_blank">{__("upgrade_license")}</a>
                </div>
            </div>

            <div class="restriction-promo__wrapper-mv">

                <div class="restriction-features">
                    <div class="restriction-feature restriction-feature_plus">
                        <h2>{__("mve_plus_license", ["[product]" => $smarty.const.PRODUCT_NAME])}</h2>

                        {__("text_mve_plus_license_required", [
                            "[product]" => $smarty.const.PRODUCT_NAME,
                            "[mve_plus_license_url]" => $config.resources.mve_plus_license_url
                        ]) nofilter}

                    </div>
                </div>

                <div class="center mobile-visible">
                    <a class="restriction-update-btn" href="{$config.resources.mve_plus_license_url}" target="_blank">{__("upgrade_license")}</a>
                </div>
            </div>
        </div>

        <div class="restriction-promo__buttons mobile-hidden">
            <div class="restriction-promo__wrapper-mv">
                <div class="center">
                    <a class="restriction-update-btn" href="{$config.resources.mve_ultimate_license_url}" target="_blank">{__("upgrade_license")}</a>
                </div>
            </div>
            <div class="restriction-promo__wrapper-mv">
                <div class="center">
                    <a class="restriction-update-btn" href="{$config.resources.mve_plus_license_url}" target="_blank">{__("upgrade_license")}</a>
                </div>
            </div>
        </div>
    </div>
{/if}
