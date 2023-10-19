{if !$product_type->isFieldAvailable("categories")}
    {hook name="products:categories_section"}
    <!-- Overridden by the Product Variations add-on -->
    {/hook}
{/if}
