{if $product.master_product_id}
    {if $runtime.company_id}
        {$product.list_price|fn_format_price:$primary_currency:null:false}
    {else}
        <input type="text"
               name="products_data[{$product.product_id}][list_price]"
               size="6" value="{$product.list_price|fn_format_price:$primary_currency:null:false}"
               class="input-small input-hidden"
        />
    {/if}
{/if}