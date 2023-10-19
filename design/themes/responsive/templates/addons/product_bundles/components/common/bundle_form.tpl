{*
    Import
    ---
    $bundle
    $show_header
    $show_description
    $thumbnail_coefficient
    $show_add_all_to_cart

    Global
    ---
    $thumbnail_width
    $thumbnail_height
    $item_quantity
    $item_quantity_responsive
    $scroller_item_attrs
    $bundle_block

    Local
    ---
    $bundle_product_key
    $bundle_product
*}

{if $bundle.products}
    {$show_header = $show_header|default:true}
    {$show_description = $show_description|default:true}
    {$show_add_all_to_cart = $show_add_all_to_cart|default:true}
    {$thumbnail_coefficient = $thumbnail_coefficient|default:0.75}
    {$thumbnail_width = $settings.Thumbnails.product_lists_thumbnail_width * $thumbnail_coefficient}
    {$thumbnail_height = $settings.Thumbnails.product_lists_thumbnail_height * $thumbnail_coefficient}

    {* Bundle carousel init *}
    {include file="addons/product_bundles/components/common/bundle_scroller_init.tpl"
        bundle=$bundle
    }

    {* Bundle form wrapper *}
    <div class="ty-product-bundles-bundle-form"
        style="--ty-product-lists-thumbnail-width: {$thumbnail_width}px;
            --ty-product-lists-thumbnail-height: {$thumbnail_height}px;"
    >
        {* Bundle header *}
        {if $show_header && $bundle.storefront_name}
            <div class="ty-product-bundles-bundle-form__header ty-subheader">
                {$bundle.storefront_name}
            </div>
        {/if}

        {* Bundle description *}
        {if $show_description && $bundle.description}
            <div class="ty-product-bundles-bundle-form__description ty-wysiwyg-content">
                {$bundle.description nofilter}
            </div>
        {/if}

        {* Bundle form *}
        <form class="cm-ajax cm-ajax-full-render ty-product-bundles-bundle-form__form"
            action="{""|fn_url}"
            method="post"
            name="bundle_form_{$bundle.bundle_id}"
            enctype="multipart/form-data"
            id="bundle_form_{$bundle.bundle_id}"
        >
            <input type="hidden" name="result_ids" value="cart_status*,wish_list*,checkout*,account_info*,product_bundles_bundle_form_{$bundle.bundle_id}">
            <input type="hidden" name="redirect_url" value="{$config.current_url}" />
            <input type="hidden" name="bundle_id" value="{$bundle.bundle_id}"/>

            {* Bundle form inner reloaded block *}
            <div class="ty-product-bundles-bundle-form__form-inner"
                data-ca-product-bundles="formInner"
                id="product_bundles_bundle_form_{$bundle.bundle_id}">

                {* Bundle products scroller *}
                <div id="scroll_list_product_bundle_{$bundle.bundle_id}"
                    class="owl-carousel ty-scroller-list ty-scroller ty-product-bundles-bundle-form__products"
                    data-ca-product-bundles="scroller"
                    {$scroller_item_attrs|render_tag_attrs nofilter}
                >
                    {foreach $bundle.products as $bundle_product_key => $bundle_product}
                        {include file="addons/product_bundles/components/common/product_item.tpl"
                            bundle=$bundle
                            bundle_product=$bundle_product
                            bundle_product_key=$bundle_product_key
                        }
                    {/foreach}
                </div>

                {* Bundle total *}
                {include file="addons/product_bundles/components/common/bundle_total.tpl"
                    bundle=$bundle
                    show_add_all_to_cart=$show_add_all_to_cart
                }

            <!--product_bundles_bundle_form_{$bundle.bundle_id}--></div>
        </form>
    </div>

    {include file="common/scroller_init.tpl"
        block=$bundle_block
        item_quantity_responsive=$item_quantity_responsive
    }
{/if}
