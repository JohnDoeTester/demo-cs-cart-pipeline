{if $product.master_product_offers_count
    && $product.master_product_id === "0"
    && !($addons.master_products.allow_buy_default_common_product === "YesNo::YES"|enum)
}
    {$show_shipping_estimation = false scope=parent}
{/if}
