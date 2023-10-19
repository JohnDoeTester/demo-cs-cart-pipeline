{if isset($product.warehouse_amount)}
    <input type="text" name="products_data[{$product.product_id}][amount]" size="6" value="{$product.warehouse_amount}" disabled class="input-full input-hidden" />
{/if}
