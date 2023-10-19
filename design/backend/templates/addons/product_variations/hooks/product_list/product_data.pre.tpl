{if $product.variation_name}
    {hook name="product_list:picker_product_alt_name"}
        <input type="hidden" id="product_{$product.product_id}_alt" value="{$product.variation_name}" />
    {/hook}
{/if}
