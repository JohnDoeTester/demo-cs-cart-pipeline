{if "ULTIMATE"|fn_allowed_for && $store_mode != "ultimate"}
    <div id="restriction_promo_dialog" class="restriction-promo restriction-promo--ult">
        {__("text_only_ultimate_license_required", [
            "[product]" => $smarty.const.PRODUCT_NAME,
            "[ultimate_license_url]" => $config.resources.ultimate_license_url
        ]) nofilter}
        <div class="center">
            <a class="restriction-update-btn restriction-update-btn--single" href="{$config.resources.ultimate_license_url}" target="_blank">{__("upgrade_license")}</a>
        </div>
    </div>
{/if}