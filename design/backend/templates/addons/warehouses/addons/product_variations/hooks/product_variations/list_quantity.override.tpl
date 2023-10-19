{if $product.warehouse_amount !== null}
    {if $is_form_readonly}
        <div class="product-variations__table-quantity">{$product.warehouse_amount}</div>
    {else}
        <input type="text" name="products_data[{$product.product_id}][amount]" size="6" value="{$product.warehouse_amount}" disabled class="input-full input-hidden product-variations__table-quantity" />
    {/if}
{/if}
