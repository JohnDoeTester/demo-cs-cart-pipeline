{if !$product_type->isFieldAvailable("product")}
    {hook name="products:update_product_name"}
    <!-- Overridden by the Product Variations add-on -->
    <input type="hidden" value="{$product_data.product}" name="product_data[product]" class="cm-no-hide-input">
    {/hook}
{/if}
