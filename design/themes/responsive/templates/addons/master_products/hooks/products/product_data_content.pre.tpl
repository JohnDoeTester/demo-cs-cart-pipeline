{if !$product.company_id}
    {if $show_add_to_cart}
        {$show_view_offers_btn=true scope=parent}
    {/if}

    {$is_allow_add_common_products_to_cart_list = $addons.master_products.allow_buy_default_common_product === "YesNo::YES"|enum scope=parent}

    {$show_master_product_discount_label = $show_discount_label scope=parent}
    {$show_discount_label=false scope=parent}
    {$show_shipping_label=false scope=parent}
    {$show_product_amount=true scope=parent}

    {if !$is_allow_add_common_products_to_cart_list}
        {$show_out_of_stock_block=false scope=parent}
        {$show_add_to_cart_block=false scope=parent}
    {/if}
{/if}