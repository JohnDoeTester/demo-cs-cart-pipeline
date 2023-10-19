{*
    Import
    ---
    $bundle
    $bundle_product_key
    $bundle_product
    $variant_characters_threshold

    Global
    ---
    $variant_characters_threshold
    $variants

    Local
    ---
    $product_option
    $file

    Export
    ---
    $has_required_options
    $product_bundle_options_after
*}

{$variant_characters_threshold = $variant_characters_threshold|default:40}
{$variants = []}
{$has_required_options = false}

{hook name="product_bundles:variation_options"}
    {if $bundle_product.product_options || $variants}

        <div class="ty-product-bundles-variation-options"
            id="product_bundles_product_item_options_{$bundle.bundle_id}_{$bundle_product_key}">

            {* Create an array $variants with the selected variant options *}
            {foreach $bundle_product.product_options as $product_option}
                <input type="hidden"
                    value="{$product_option.value}"
                    name="product_data[{$bundle_product.product_id}_{$bundle_product_key}][product_options][{$product_option.option_id}]"
                />

                {* Files variants options *}
                {if $bundle_product.extra.custom_files[$product_option.option_id]}
                    {foreach $bundle_product.extra.custom_files[$product_option.option_id] as $file}
                        {$variants[] = $file.name|truncate:$variant_characters_threshold}
                    {/foreach}
                {/if}

                {if $product_option.variants[$product_option.value].variant_name}
                    {* Selected variant options for any options product *}
                    {$variants[] = $product_option.variants[$product_option.value].variant_name|truncate:$variant_characters_threshold}
                {elseif $product_option.variant_name}
                    {* Selected variant options for not any options product *}
                    {$variants[] = $product_option.variant_name|truncate:$variant_characters_threshold}
                {elseif $product_option.required === "YesNo::YES"|enum && !$product_option.value}
                    {$has_required_options = true}
                {/if}
            {/foreach}

            <div class="ty-product-bundles-variation-options__content">
                {foreach $variants as $variant}
                    <span class="ty-product-bundles-variation-options__item">{$variant}</span>
                {/foreach}
            </div>

        <!--product_bundles_product_item_options_{$bundle.bundle_id}_{$bundle_product_key}--></div>
    {/if}
{/hook}

{capture name="product_bundle_options_after"}
    {if $has_required_options}
        <label for="product_bundle_options_required_{$bundle.bundle_id}_{$bundle_product_key}"
                class="cm-required hidden"
                data-ca-validator-error-message="{__("product_bundles.specify_options_first")}"
                data-ca-product-bundles="optionsRequired"
        ></label>
        <input id="product_bundle_options_required_{$bundle.bundle_id}_{$bundle_product_key}"
                type="hidden"
                value=""
        />
    {/if}
{/capture}

{* Export *}
{$has_required_options = $has_required_options scope=parent}
{$product_bundle_options_after = $smarty.capture.product_bundle_options_after scope=parent}
