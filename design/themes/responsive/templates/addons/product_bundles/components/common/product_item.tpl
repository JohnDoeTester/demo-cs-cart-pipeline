{*
    Import
    ---
    $bundle
    $bundle_product_key
    $bundle_product
    $thumbnail_coefficient
    $has_required_options
    $product_bundle_options_after

    Global
    ---
    $thumbnail_width
    $thumbnail_height
*}

{if $bundle && $bundle_product}
    {$thumbnail_coefficient = $thumbnail_coefficient|default:"0.75"}
    {$thumbnail_width = $settings.Thumbnails.product_lists_thumbnail_width * $thumbnail_coefficient}
    {$thumbnail_height = $settings.Thumbnails.product_lists_thumbnail_height * $thumbnail_coefficient}

    <div class="ty-product-bundles-product-item ty-scroller__item"
        style="--ty-product-lists-thumbnail-width: {$thumbnail_width}px;
            --ty-product-lists-thumbnail-height: {$thumbnail_height}px;"
        id="product_bundles_product_item_{$bundle.bundle_id}_{$bundle_product_key}">

        <input type="hidden" name="product_data[{$bundle_product.product_id}_{$bundle_product_key}][product_id]" value="{$bundle_product.product_id}" />
        <input type="hidden" name="product_data[{$bundle_product.product_id}_{$bundle_product_key}][amount]" value="{$bundle_product.amount}" />

        <div class="ty-product-bundles-product-item__content">

            {* Product image *}
            <div class="ty-product-bundles-product-item__image"
                id="product_bundles_product_item_image_{$bundle.bundle_id}">

                <a href="{"products.view?product_id=`$bundle_product.product_id`"|fn_url}"
                    class="ty-product-bundles-product-item__image-link"
                >
                    {include file="common/image.tpl"
                        image_width=$thumbnail_width
                        image_height=$thumbnail_height
                        obj_id="`$bundle.bundle_id`_`$bundle_product.product_id`"
                        images=$bundle_product.main_pair
                        class="ty-product-bundles-product-item__image-content"
                    }
                </a>
            <!--product_bundles_product_item_image_{$bundle.bundle_id}--></div>

            {* Product information *}
            <div class="ty-product-bundles-product-item__info">

                {* Product name *}
                <div class="ty-product-bundles-product-item__name">
                    <bdi>
                        <a href="{"products.view?product_id=`$bundle_product.product_id`"|fn_url}"
                            class="ty-product-bundles-product-item__name-link"
                        >
                            {$bundle_product.product_name}
                        </a>
                    </bdi>
                </div>

                {* Selected product options *}
                {include file="addons/product_bundles/components/options/variation_options.tpl"
                    bundle=$bundle
                    bundle_product=$bundle_product
                    bundle_product_key=$bundle_product_key
                }

                {* Product price *}
                <div class="ty-product-bundles-product-item__price"
                    id="product_bundles_product_item_price_{$bundle.bundle_id}_{$bundle_product_key}">

                    {if $bundle_product.amount > 1}
                        <span>{$bundle_product.amount}</span><span dir="{$language_direction}">&nbsp;x&nbsp;</span>
                    {/if}

                    {* If auth user or show price for anonymous shopping *}
                    {if $auth.user_id || $settings.Checkout.allow_anonymous_shopping !== "hide_price_and_add_to_cart"}
                        {if $bundle_product.price !== $bundle_product.discounted_price}
                            {strip}
                                <span class="ty-list-price ty-nowrap ty-strike">
                                    {include file="common/price.tpl"
                                        value=$bundle_product.price
                                        class="ty-list-price ty-nowrap"
                                    }
                                </span>
                            {/strip}
                        {/if}
                        <span class="ty-price">
                            {include file="common/price.tpl"
                                value=$bundle_product.discounted_price
                                class="ty-price-num"
                            }
                        </span>
                        {if $settings.Appearance.show_prices_taxed_clean === "YesNo::YES"|enum && $auth.tax_exempt !== "YesNo::YES"|enum && $bundle_product.taxes}
                            {if $bundle_product.discounted_price != $bundle_product.taxed_price}
                                <span class="ty-list-price ty-nowrap" id="line_product_price_{$obj_prefix}{$obj_id}">({include file="common/price.tpl" value=$bundle_product.taxed_price span_id="product_price_`$obj_prefix``$obj_id`" class="ty-list-price ty-nowrap"} {__("inc_tax")})</span>
                            {else}
                                <span class="ty-list-price ty-nowrap ty-tax-include">({__("including_tax")})</span>
                            {/if}
                        {/if}
                    {/if}
                <!--product_bundles_product_item_price_{$bundle.bundle_id}_{$bundle_product_key}--></div>

                {* Product control *}
                {capture name="product_bundles_control"}
                    {* Hook for product variations *}
                    {hook name="product_bundles:product_control"}
                        {if $bundle_product.product_options && $bundle_product.aoc === "YesNo::YES"|enum}
                            {$selected_options = ["selected_options" => array_column($bundle_product.product_options, "value", "option_id")]}
                            <a id="opener_product_bundle_options_{$bundle.bundle_id}_{$bundle_product_key}"
                                class="cm-dialog-opener cm-dialog-auto-size ty-product-bundles-product-item__control-link
                                    {if $has_required_options}ty-product-bundles-product-item__control-link--required{/if}"
                                href="{"product_bundles.get_option_variants?bundle_id=`$bundle.bundle_id`&product_id=`$bundle_product.product_id`&bundle_product_key=`$bundle_product_key`&`$selected_options|http_build_query`&return_url=`$config.current_url|escape:url`"|fn_url}"
                                data-ca-target-id="content_product_bundle_options_{$bundle.bundle_id}_{$bundle_product_key}"
                                data-ca-dialog-title="{__("product_bundles.specify_options")}"
                                rel="nofollow"
                            >
                                {__("product_bundles.specify_options")}
                            </a>

                            {* Data after product options link *}
                            {$product_bundle_options_after nofilter}
                        {/if}
                    {/hook}
                {/capture}

                {if $smarty.capture.product_bundles_control|trim}
                    <div class="ty-product-bundles-product-item__control">
                        {$smarty.capture.product_bundles_control nofilter}
                    </div>
                {/if}

            </div>

        </div>

    <!--product_bundles_product_item_{$bundle.bundle_id}_{$bundle_product_key}--></div>
{/if}
