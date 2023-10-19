{if $id && $runtime.company_id && !$product_data.company_id}
    <!-- Overridden by the Common Products for Vendors add-on -->
    {btn type="text"
        class="btn btn-primary"
        text=__("master_products.sell_this")
        href="products.sell_master_product?master_product_id=`$product_data.product_id`"
        method="post"
    }
{/if}