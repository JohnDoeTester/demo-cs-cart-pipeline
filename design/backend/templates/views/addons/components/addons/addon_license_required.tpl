{if "MULTIVENDOR"|fn_allowed_for && $key|fn_check_addon_snapshot:"plus"}
    {$license_required = [
        promo_popup_title => __("mve_ultimate_or_plus_license_required", ["[product]" => $smarty.const.PRODUCT_NAME]),
        href => "functionality_restrictions.mve_ultimate_or_plus_license_required"|fn_url,
        target_id => "content_mve_ultimate_or_plus_license_required"
    ] scope=parent}
{elseif "MULTIVENDOR"|fn_allowed_for}
    {$license_required = [
        promo_popup_title => __("mve_ultimate_license_required", ["[product]" => $smarty.const.PRODUCT_NAME]),
        href => "functionality_restrictions.mve_ultimate_license_required"|fn_url,
        target_id => "content_mve_ultimate_license_required"
    ] scope=parent}
{else}
    {$license_required = [
        promo_popup_title => __("ultimate_license_required", ["[product]" => $smarty.const.PRODUCT_NAME]),
        href => "functionality_restrictions.ultimate_license_required"|fn_url,
        target_id => "content_ultimate_license_required"
    ] scope=parent}
{/if}
