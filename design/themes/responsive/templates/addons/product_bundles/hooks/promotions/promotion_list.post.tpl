{*
    Import
    ---
    $bundles

    Local
    ---
    $bundle
*}

{if $bundles}
    {script src="js/addons/product_bundles/frontend/func.js"}

    <div class="ty-product-bundles-promotion-list">
        <h2 class="ty-grid-promotions__subtitle">{__("product_bundles.active_bundles")}</h2>
        <div class="ty-product-bundles-promotion-list__content">
            {foreach $bundles as $bundle}
                {include file="addons/product_bundles/components/pages/bundles_promotion.tpl"
                    bundle=$bundle
                }
            {/foreach}
        </div>
    </div>
{/if}