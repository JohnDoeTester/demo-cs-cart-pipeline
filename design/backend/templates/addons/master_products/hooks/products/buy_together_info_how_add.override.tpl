{if !$chains}
    <span class="buy-together-info-message">
        {if !$runtime.company_id}
            {if !$product_data.company_id}
                {__("master_products.buy_together_info_message_for_admin")}
            {else}
                {__("buy_together_info_message_for_mve", ["[company_id]" => $product_data.company_id, "[product_id]" => $product_data.product_id]) nofilter}
            {/if}
        {else}
            {if !$product_data.company_id}
                {__("master_products.buy_together_info_message_for_vendor", ["[href]" => "products.sell_master_product?master_product_id=`$product_data.product_id`&selected_section=buy_together"|fn_url]) nofilter}
            {/if}
        {/if}
    </span>
{/if}