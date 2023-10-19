{if $product.is_vendor_products_list_item}
    <div class="ty-sellers-list__options hidden">
        {if $product.selected_options}
            {foreach $product.selected_options as $product_option_id => $product_option}
                <input type="hidden"
                       name="product_data[{$product.product_id}][product_options][{$product_option_id}]"
                       value="{$product_option}"
                />
            {/foreach}
        {/if}
    </div>
{/if}