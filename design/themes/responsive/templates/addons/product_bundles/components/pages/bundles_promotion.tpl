{*
    Import
    ---
    $bundle
    $obj_id
    $promotion_image_width
    $promotion_image_height
    $company_name
*}

{script src="js/tygh/exceptions.js"}

{if $bundle}
    <div class="ty-column3 ty-product-bundles-bundles-promotion">
        <div class="ty-grid-list__item ty-grid-promotions__item">
            {if $bundle.main_pair|is_array}
                {include file="common/image.tpl"
                    images=$bundle.main_pair
                    image_id="`$bundle.bundle_id`"
                    class="ty-grid-promotions__image"
                    image_width=$promotion_image_width|default:''
                    image_height=$promotion_image_height|default:''
                }
            {/if}

            <div class="ty-grid-promotions__content">
                <a id="opener_product_bundle_promotions_{$bundle.bundle_id}"
                    class="cm-dialog-opener"
                    href="{"product_bundles.get_product_bundles?bundle_id=`$bundle.bundle_id`&in_popup=1&return_url=`$config.current_url|escape:url`"|fn_url}"
                    data-ca-target-id="content_product_bundle_promotions_{$bundle.bundle_id}"
                    data-ca-dialog-title="{$bundle.storefront_name}"
                    rel="nofollow"
                >
                    <h2 class="ty-product-bundles-bundles-promotion__header ty-grid-promotions__header">{$bundle.storefront_name}</h2>
                </a>

                {if $bundle.date_to}
                    <div class="ty-grid-list__available">
                        {__("avail_till")}: {$bundle.date_to|date_format:$settings.Appearance.date_format}
                    </div>
                {/if}

                {if "MULTIVENDOR"|fn_allowed_for && ($company_name || $bundle.company_id)}
                    <div class="ty-grid-promotions__company">
                        <a href="{"companies.products?company_id=`$bundle.company_id`"|fn_url}" class="ty-grid-promotions__company-link">
                            {if $company_name}{$company_name}{else}{$bundle.company_id|fn_get_company_name}{/if}
                        </a>
                    </div>
                {/if}

                {if $bundle.description}
                    <div class="ty-wysiwyg-content ty-grid-promotions__description">
                        {$bundle.description nofilter}
                    </div>
                {/if}
            </div>
        </div>
    </div>
{/if}
