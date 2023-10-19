{if $show_list_quantity|default:false}
    {if $product.master_product_offers_count}
        <a href="{"products.manage?product_type[]=`$smarty.const.PRODUCT_TYPE_VENDOR_PRODUCT_OFFER`&product_type[]=`$smarty.const.PRODUCT_TYPE_PRODUCT_OFFER_VARIATION`&master_product_id=`$product.product_id`"|fn_url}">
            {$product.inventory_amount|default:$product.amount}
        </a>
    {else}
        <!-- Overridden by the Common Products for Vendors add-on -->
        {$product.inventory_amount|default:$product.amount}
    {/if}
{/if}