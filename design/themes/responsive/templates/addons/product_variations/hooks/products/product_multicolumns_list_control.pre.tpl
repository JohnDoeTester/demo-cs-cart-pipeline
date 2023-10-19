{if $show_features && $product.variation_features_variants}
    {capture name="variation_features_variants"}
    {foreach $product.variation_features_variants as $variation_feature}
        {if $variation_feature.display_on_catalog === "YesNo::YES"|enum}
            <div class="ty-grid-list__item-features-item">
                <span class="ty-grid-list__item-features-description">
                    {$variation_feature.description}:
                </span>
                {$variant_names = []}
                {foreach $variation_feature.variants as $variant}
                    {if $variant.product_id || $addons.product_variations.variations_show_all_possible_feature_variants === "YesNo::YES"|enum}
                        {$variant_names[] = $variant.variant}
                    {/if}
                {/foreach}
                {foreach $variant_names as $variant_name}
                    <span class="ty-grid-list__item-features-variant">
                        {$variant_name}{if !$variant_name@last},{/if}
                    </span>
                {/foreach}
            </div>
        {/if}
    {/foreach}
    {/capture}
    {if $smarty.capture.variation_features_variants|trim}
        <div class="ty-grid-list__item-features">
            {$smarty.capture.variation_features_variants nofilter}
        </div>
    {/if}
{/if}
