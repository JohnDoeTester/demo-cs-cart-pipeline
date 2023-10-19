{if $show_common_products_action_buttons}
    <li>{btn type="list" text=__("bulk_product_addition") href="products.m_add"}</li>
    {if $products}
        <li>{btn type="list" text=__("export_found_products") href="products.export_found.master"}</li>
    {/if}
{/if}