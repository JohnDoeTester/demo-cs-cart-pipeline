{if !$product.company_id && $show_master_product_discount_label && ($product.discount_prc || $product.list_discount_prc) && $show_price_values}
    {if $product.discount}
        {$label_text = "{__("master_products.save_up_to")} {$product.discount_prc}%"}
    {else}
        {$label_text = "{__("master_products.save_up_to")} {$product.list_discount_prc}%"}
    {/if}

    {include
        file="views/products/components/product_label.tpl"
        label_meta="ty-product-labels__item--discount"
        label_text=$label_text
        label_mini=$product_labels_mini
        label_static=$product_labels_static
        label_rounded=$product_labels_rounded
    }
{/if}