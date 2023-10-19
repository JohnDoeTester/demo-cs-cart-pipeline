{*
    Import
    ---
    $bundles
    $obj_id
    $show_block_header

    Global
    ---
    $show_block_header
    $show_add_all_to_cart

    Local
    ---
    $bundle
*}

{if $bundles}
    {hook name="product_bundles:product_bundles"}
    {$show_block_header = $show_block_header|default:false}
    {$enable_padding = $enable_padding|default:true}
    {$show_add_all_to_cart = $show_add_all_to_cart|default:true}

    <div class="ty-product-bundles-product-bundles
        {if $enable_padding}ty-product-bundles-product-bundles--padding{/if}
        {if $show_on_tab}ty-product-bundles-product-bundles--tab{/if}
        {if $show_in_popup}ty-product-bundles-product-bundles--popup{/if}
    ">
        {if $show_block_header}
            <div class="ty-product-bundles-product-bundles__header">
                {__("product_bundles.product_bundles")}
            </div>
        {/if}
        <div class="ty-product-bundles-product-bundles__body">
            {foreach $bundles as $bundle}
                {include file="addons/product_bundles/components/common/bundle_form.tpl"
                    bundle=$bundle
                    obj_id=$obj_id
                }
            {/foreach}
        </div>
    </div>
    {/hook}
{/if}
