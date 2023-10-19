{if !$product.company_id}
    {if $product.master_product_offers_count}
        {include file="common/price.tpl" value=$product.price assign=price_from}
        <a href="{"products.manage?product_type[]=`$smarty.const.PRODUCT_TYPE_VENDOR_PRODUCT_OFFER`&product_type[]=`$smarty.const.PRODUCT_TYPE_PRODUCT_OFFER_VARIATION`&master_product_id=`$product.product_id`"|fn_url}">
            {__("master_products.price_from", ["[formatted_price]" => $price_from])}
        </a>
    {else}
        <input type="text"
               name="products_data[{$product.product_id}][price]"
               size="6" value="{$product.price|fn_format_price:$primary_currency:null:false}"
               class="input-small input-hidden"
        />
    {/if}
{/if}