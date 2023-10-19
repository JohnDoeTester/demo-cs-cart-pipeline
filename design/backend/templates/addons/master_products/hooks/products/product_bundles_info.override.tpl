{if !$product_data.company_id}
    <span class="product-bundles-info-message">
        {if !$runtime.company_id}
            {__("master_products.product_bundles_info")}
        {else}
            {__("master_products.buy_together_info_message_for_vendor", ["[href]" => "products.sell_master_product?master_product_id=`$product_data.product_id`&selected_section=product_bundles"|fn_url]) nofilter}
        {/if}
    </span>
{/if}