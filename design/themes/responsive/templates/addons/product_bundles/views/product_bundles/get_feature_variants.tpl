{*
    Import
    ---
    $bundle
    $bundle_product_key
    $bundle_product

    Global
    ---
    $purpose_create_variations

    Local
    ---
    $feature
    $variant
*}

{if $bundle_product.variation_features_variants}
    {script src="js/addons/product_bundles/frontend/func.js"}

    <form class="ty-product-bundles-get-feature-variants"
        action="{""|fn_url}"
        method="post"
        name="product_bundles_get_feature_variants"
        enctype="multipart/form-data"
        id="product_bundles_get_feature_variants_{$bundle.bundle_id}_{$bundle_product_key}"
        data-ca-product-bundles-target-form="#bundle_form_{$bundle.bundle_id}"
    >
        <div>
            <div data-ca-product-bundles="fieldContainer"
                id="product_features_update_product_bundles_{$bundle.bundle_id}_{$bundle_product_key}">

                {$purpose_create_variations = "\Tygh\Addons\ProductVariations\Product\FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM"|constant}
                {$allow_negative_amount = $allow_negative_amount|default:$settings.General.allow_negative_amount === "YesNo::YES"|enum}

                {foreach $bundle_product.variation_features_variants as $feature}
                    {if $feature.purpose !== $purpose_create_variations}
                        {continue}
                    {/if}
                    <div class="ty-control-group ty-product-options__item clearfix">
                        <label class="ty-control-group__label ty-product-options__item-label">{$feature.description}:</label>
                        {if $feature.prefix}
                            <span>{$feature.prefix}</span>
                        {/if}
                        <select
                            name="product_data[{$bundle_product.product_id}_{$bundle_product_key}][product_features][{$feature.feature_id}]"
                        >
                            {foreach $feature.variants as $variant}
                                {if $variant.product && ($variant.product.amount || $allow_negative_amount)}
                                    <option {if $feature.variant_id === $variant.variant_id}selected="selected"{/if}
                                        value="{$variant.variant_id}"
                                    >
                                        {$variant.variant}
                                    </option>
                                {elseif $addons.product_variations.variations_show_all_possible_feature_variants === "YesNo::YES"|enum}
                                    <option value="{$variant.variant_id}" disabled>{$variant.variant}</option>
                                {/if}
                            {/foreach}
                        </select>
                    </div>
                {/foreach}
            <!--product_features_update_product_bundles_{$bundle.bundle_id}_{$bundle_product_key}--></div>

            <div class="buttons-container">

                {* Submit button *}
                {include file="buttons/button.tpl"
                    but_text=__("save")
                    but_id="bundle_button_`$bundle.bundle_id`"
                    but_meta="ty-btn__secondary"
                    but_name="dispatch[product_bundles.change_variation]"
                }
            </div>
        </div>
    </form>
{/if}
