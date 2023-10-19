{*
    Import
    ---
    $bundle
    $bundle_product_key
    $bundle_product
*}

{if $bundle_product.product_options}
    {script src="js/addons/product_bundles/frontend/func.js"}

    <form class="ty-product-bundles-get-option-variants cm-reload-product_bundles_{$bundle.bundle_id}_{$bundle_product_key}{$bundle_product.product_id}"
        action="{""|fn_url}"
        method="post"
        name="product_bundles_get_option_variants"
        enctype="multipart/form-data"
        id="product_bundles_get_option_variants_{$bundle.bundle_id}_{$bundle_product_key}"
        data-ca-product-bundles-target-form="#bundle_form_{$bundle.bundle_id}"
        data-ca-product-bundles-bundle-product-key="{$bundle_product_key}"
        data-ca-product-bundles-product-id="{$bundle_product.product_id}"
    >
        <input type="hidden" name="appearance[show_product_options]" value="1" />
        <input type="hidden" name="bundle_id" value="{$bundle.bundle_id}"/>

        <div>
            <div class="cm-reload-product_bundles_{$bundle.bundle_id}_{$bundle_product_key}{$bundle_product.product_id}"
                data-ca-product-bundles="fieldContainer"
                id="product_options_update_product_bundles_{$bundle.bundle_id}_{$bundle_product_key}{$bundle_product.product_id}">

                {include file="views/products/components/product_options.tpl" 
                    product=$bundle_product 
                    id=$bundle_product.product_id
                    obj_prefix="product_bundles_`$bundle.bundle_id`_`$bundle_product_key`"
                    product_options=$bundle_product.product_options
                    name="product_data" 
                    no_script=true 
                }

            <!--product_options_update_product_bundles_{$bundle.bundle_id}_{$bundle_product_key}{$bundle_product.product_id}--></div>

            <div class="buttons-container">

                {* Submit button *}
                {include file="buttons/button.tpl"
                    but_text=__("save")
                    but_id="bundle_button_`$bundle.bundle_id`"
                    but_meta="ty-btn__secondary"
                    but_name="dispatch[product_bundles.change_options]"
                }
            </div>
        </div>
    </form>
{/if}
