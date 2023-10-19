{if "MULTIVENDOR"|fn_allowed_for && ($company_name || $company_id) && $settings.Vendors.display_vendor == "Y"}
    <div class="ty-control-group{if !$capture_options_vs_qty} product-list-field{/if} paypal-adaptive-vendor-name {if !empty($product.paypal_verification.verified) && $product.paypal_verification.verified == "verified"}paypal-adaptive-vendor-name-text{/if}">
        <label class="ty-control-group__label">{__("vendor")}:</label>
        <span class="ty-control-group__item"><a href="{"companies.products?company_id=`$company_id`"|fn_url}">{if $company_name}{$company_name}{else}{$company_id|fn_get_company_name}{/if}</a></span>
        {hook name="companies:product_company_data"}
            {if !empty($product.paypal_verification.main_pair)}
                {include file="common/image.tpl" image_width=$product.paypal_verification.width image_height=$product.paypal_verification.height obj_id=$object_id images=$product.paypal_verification.main_pair}
            {elseif !empty($product.paypal_verification.verified) && $product.paypal_verification.verified == "verified"}
                <span class="ty-control-group__item">{__("verified_by_paypal")}</span>
            {/if}
        {/hook}
    </div>
{/if}
