{if !$runtime.company_id && $product_data && !$product_data.company_id && $product_data.master_product_offers_count}
    <!-- Overridden by the Common Products for Vendors add-on -->
    <div class="control-group {$no_hide_input_if_shared_product}">
        <label for="elm_price_price" class="control-label cm-required">
            {__("price")} ({$currencies.$primary_currency.symbol nofilter}):
        </label>
        <div class="controls">
            <input type="hidden" name="product_data[price]" value="{$product_data.price|default:"0.00"|fn_format_price:$primary_currency:null:false}"/>
            <p>
                {include file="common/price.tpl" value=$product_data.price assign=price_from}
                <a href="{"products.manage?product_type[]=`$smarty.const.PRODUCT_TYPE_VENDOR_PRODUCT_OFFER`&product_type[]=`$smarty.const.PRODUCT_TYPE_PRODUCT_OFFER_VARIATION`&master_product_id=`$product_data.product_id`"|fn_url}">
                    {__("master_products.price_from", ["[formatted_price]" =>$price_from])}
                </a>
            </p>
        </div>
    </div>
{/if}