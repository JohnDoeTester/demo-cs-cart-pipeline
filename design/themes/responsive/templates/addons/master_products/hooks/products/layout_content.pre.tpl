{if $product.master_product_id || !$product.company_id}
    {$show_old_price=false scope=parent}
    {$show_list_discount=false scope=parent}
    {$show_product_labels=false scope=parent}
    {$show_discount_label=false scope=parent}
    {$show_shipping_label=false scope=parent}
    {$show_product_tabs=true scope=parent}
    {$dont_show_points=!$product.company_id scope=parent}

    {if !$product.company_id}
        {$show_product_amount=false scope=parent}
    {/if}
{/if}