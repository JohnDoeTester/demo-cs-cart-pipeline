{if $product.variation_features_variants && $is_product_bundles}
    {$purpose_create_variations = "\Tygh\Addons\ProductVariations\Product\FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM"|constant}

    <div class="cm-picker-product-options form-horizontal" id="features_{$product.product_id}">
        <div class="ty-product-bundles-product-list__variation-features"
            id="features_{$product.product_id}_AOC"
            data-ca-empty-product-description-prefix="true"
        >
            {foreach $product.variation_features_variants as $feature}
                {if $feature.purpose !== $purpose_create_variations}
                    {continue}
                {/if}

                <div class="control-group ty-product-variation-features__item clearfix">
                    <label class="control-label ty-product-variation-features__item-label">{$feature.description}:</label>
                    {if $feature.prefix}
                        <span>{$feature.prefix}</span>
                    {/if}

                    <div class="controls">
                        <select class="product_bundle_feature_variation" name="product_bundle_feature_variation[{$product.product_id}][product_features][{$feature.feature_id}]">
                            {foreach $feature.variants as $variant}
                                {if $variant.product}
                                    <option {if $feature.variant_id === $variant.variant_id}selected="selected"{/if}
                                        data-ca-product-id="{$variant.product_id}"
                                        data-ca-target-id="picker_product_row_{$row_index}"
                                        data-ca-change-url="{"product_bundles.change_variation"|fn_url}"
                                        data-ca-row-index="{$row_index}"
                                    >
                                        {$variant.variant}
                                    </option>
                                {elseif $addons.product_variations.variations_show_all_possible_feature_variants === "YesNo::YES"|enum}
                                    <option disabled>{$variant.variant}</option>
                                {/if}
                            {/foreach}
                        </select>
                    </div>
                </div>
            {/foreach}
        </div>
        <div>
            <div class="control-group cm-picker-product-options">
                <label for="sw_features_{$product.product_id}_AOC" class="checkbox">
                    <input class="cm-switch-availability cm-switch-inverse cm-option-aoc"
                        id="sw_features_{$product.product_id}_AOC"
                        type="checkbox"
                        name="item_data[products][{$product.product_id}][any_variation]"
                        value="{"YesNo::NO"|enum}"
                        data-ca-product-id="{$product.product_id}"
                        data-ca-aoc-text="{__("product_bundles.any_variation")}"
                        data-ca-product-bundles="anyVariation"
                    />{__("product_bundles.any_variation")}
                </label>
            </div>
        </div>
    </div>
{/if}
