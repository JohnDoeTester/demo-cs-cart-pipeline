{if !$product.company_id && !$is_allow_add_common_products_to_cart_list}
    {$cart_button_exists = false scope = parent}
{/if}