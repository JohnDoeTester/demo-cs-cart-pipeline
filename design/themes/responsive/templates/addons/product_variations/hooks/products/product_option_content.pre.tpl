{if $product.variation_features_variants && $product.detailed_params.info_type === "D"}
    {script src="js/addons/product_variations/picker_features.js"}
    <div id="features_{$obj_prefix}{$obj_id}_AOC">
        {$container = "product_detail_page"}
        {$product_url = "products.view"}
        {$show_all_possible_feature_variants = $addons.product_variations.variations_show_all_possible_feature_variants === "YesNo::YES"|enum}
        {$allow_negative_amount = $allow_negative_amount|default:$settings.General.allow_negative_amount === "YesNo::YES"|enum}
        {$is_buy_in_advance = $product.out_of_stock_actions === "OutOfStockActions::BUY_IN_ADVANCE"|enum}
        {$show_out_of_stock_products = $settings.General.show_out_of_stock_products === "YesNo::YES"|enum}

        {if $quick_view}
            {$container = "product_main_info_form_{$obj_prefix}{$quick_view_additional_container}"}
            {$product_url = "products.quick_view?product_id=`$product.product_id`&prev_url=`$current_url`"|trim}
        {/if}

        {if $is_microstore}
            {$product_url = $product_url|fn_link_attach:"is_microstore=Y"}

            {if $product.company_id}
                {$product_url = $product_url|fn_link_attach:"microstore_company_id=`$product.company_id`"}
            {/if}
        {/if}

        {if $product.detailed_params.is_preview}
            {$product_url = $product_url|fn_link_attach:"action=preview"}
        {/if}

        <div class="cm-picker-product-variation-features ty-product-options">
            {$feature_style_dropdown = "\Tygh\Enum\ProductFeatureStyles::DROP_DOWN"|constant}
            {$feature_style_images = "\Tygh\Enum\ProductFeatureStyles::DROP_DOWN_IMAGES"|constant}
            {$feature_style_labels = "\Tygh\Enum\ProductFeatureStyles::DROP_DOWN_LABELS"|constant}
            {$purpose_create_variations = "\Tygh\Addons\ProductVariations\Product\FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM"|constant}

            {foreach $product.variation_features_variants as $feature}
                {$is_feature_default_style = !in_array($feature.feature_style, [$feature_style_images, $feature_style_labels, $feature_style_dropdown])}

                <div class="ty-control-group ty-product-options__item clearfix">
                    <label class="ty-control-group__label ty-product-options__item-label">{$feature.description}:</label>
                    <bdi>
                        {if $feature.feature_style === $feature_style_images}
                            {foreach $feature.variants as $variant}
                                {if $feature.variant_id != $variant.variant_id}
                                    {continue}
                                {/if}
                                {if $variant.product_id || $show_all_possible_feature_variants}
                                    <div class="ty-product-option-container ty-product-option-container--feature-style-images">
                                        {if $feature.prefix}
                                            <div class="ty-product-option-child">{$feature.prefix}</div>
                                        {/if}
                                        <div class="ty-product-option-child">{$variant.variant}</div>
                                        {if $feature.suffix}
                                            <div class="ty-product-option-child">{$feature.suffix}</div>
                                        {/if}
                                    </div>
                                {/if}
                            {/foreach}
                        {elseif $feature.feature_style === $feature_style_dropdown || $is_feature_default_style}
                            <div class="ty-product-option-container">
                            {if $feature.prefix}
                                <div class="ty-product-option-child">{$feature.prefix}</div>
                            {/if}
                                <div class="ty-product-option-child">
                                    <select class="{if $feature.purpose === $purpose_create_variations || $quick_view}cm-ajax{/if} {if !$quick_view}cm-history{/if} cm-ajax-force" data-ca-target-id="{$container}">
                                        {foreach $feature.variants as $variant}
                                            {if $variant.product_id}
                                                <option
                                                    data-ca-variant-id="{$variant.variant_id}"
                                                    data-ca-product-url="{$product_url|fn_link_attach:"product_id={$variant.product.product_id}"|fn_url}"
                                                    {if $feature.variant_id == $variant.variant_id}selected="selected"{/if}
                                                >
                                                    {$variant.variant}
                                                </option>
                                            {elseif $show_all_possible_feature_variants}
                                                <option disabled>{$variant.variant}</option>
                                            {/if}
                                        {/foreach}
                                    </select>
                                </div>
                            {if $feature.suffix}
                                <div class="ty-product-option-child">{$feature.suffix}</div>
                            {/if}
                            </div>
                        {/if}
                    </bdi>

                    {if $feature.feature_style === $feature_style_images}
                        {capture name="variant_images"}
                            {foreach $feature.variants as $variant}
                                {if $variant.showed_product_id}
                                    {$variant_product_id = $variant.showed_product_id}
                                {else}
                                    {$variant_product_id = $variant.product.product_id}
                                {/if}
                                {if $variant_product_id}
                                    {if $variant.amount || $allow_negative_amount || $is_buy_in_advance || $show_out_of_stock_products && !$variant.product.disabled}
                                        <a href="{$product_url|fn_link_attach:"product_id={$variant_product_id}"|fn_url}"
                                        class="ty-product-options__image--wrapper {if $variant.variant_id == $feature.variant_id}ty-product-options__image--wrapper--active{/if} {if $feature.purpose === $purpose_create_variations || $quick_view}cm-ajax cm-ajax-cache{/if} {if !$quick_view}cm-history{/if}"
                                        {if $feature.purpose === $purpose_create_variations || $quick_view}data-ca-target-id="{$container}"{/if}
                                        >
                                    {/if}
                                        {if $variant.amount || $allow_negative_amount || $is_buy_in_advance || $show_out_of_stock_products && !$variant.product.disabled}
                                            {$image_class = "ty-product-options__image"}
                                        {else}
                                            {$image_class = "ty-product-variations-image-disabled"}
                                        {/if}

                                        {include file="common/image.tpl"
                                            obj_id="image_feature_variant_{$feature.feature_id}_{$variant.variant_id}_{$obj_prefix}{$obj_id}"
                                            class=$image_class
                                            images=$variant.product.main_pair
                                            image_width=$settings.Thumbnails.product_variant_mini_icon_width
                                            image_height=$settings.Thumbnails.product_variant_mini_icon_height
                                            image_additional_attrs = [
                                                "width" => $settings.Thumbnails.product_variant_mini_icon_width,
                                                "height" => $settings.Thumbnails.product_variant_mini_icon_height
                                            ]
                                        }
                                    {if $variant.amount || $allow_negative_amount || $is_buy_in_advance || $show_out_of_stock_products && !$variant.product.disabled}
                                        </a>
                                    {/if}
                                {/if}
                            {/foreach}
                        {/capture}

                        {if $smarty.capture.variant_images|trim}
                            <div class="ty-clear-both">
                                {$smarty.capture.variant_images nofilter}
                            </div>
                        {/if}
                    {elseif $feature.feature_style === $feature_style_labels}
                        <div class="ty-clear-both">
                            {foreach $feature.variants as $variant}
                                {if $variant.product_id && ($variant.product.amount || $allow_negative_amount || $is_buy_in_advance || $show_out_of_stock_products && !$variant.product.disabled)}
                                    <input type="radio"
                                           name="feature_{$feature.feature_id}"
                                           value="{$variant.variant_id}"
                                           {if $feature.variant_id == $variant.variant_id}
                                               checked
                                           {/if}
                                           id="feature_{$feature.feature_id}_variant_{$variant.variant_id}_{$obj_prefix}{$obj_id}"
                                           data-ca-variant-id="{$variant.variant_id}"
                                           data-ca-product-url="{$product_url|fn_link_attach:"product_id={$variant.product.product_id}"|fn_url}"
                                           class="hidden ty-product-options__radio {if $feature.purpose === $purpose_create_variations || $quick_view}cm-ajax{/if} {if !$quick_view}cm-history{/if} cm-ajax-force" data-ca-target-id="{$container}"
                                    />
                                    <label for="feature_{$feature.feature_id}_variant_{$variant.variant_id}_{$obj_prefix}{$obj_id}"
                                           class="ty-product-options__radio--label"
                                    >
                                        <span class="ty-product-option-checkbox">{$feature.prefix}</span>{$variant.variant}<span class="ty-product-option-checkbox">{$feature.suffix}</span>
                                    </label>
                                {elseif $show_all_possible_feature_variants}
                                    <label class="ty-product-options__radio--label ty-product-options__radio--label--disabled">
                                        <span class="ty-product-option-checkbox">{$feature.prefix}</span>{$variant.variant}<span class="ty-product-option-checkbox">{$feature.suffix}</span>
                                    </label>
                                {/if}
                            {/foreach}
                        </div>
                    {/if}
                </div>
            {/foreach}
        </div>
    </div>
{/if}
