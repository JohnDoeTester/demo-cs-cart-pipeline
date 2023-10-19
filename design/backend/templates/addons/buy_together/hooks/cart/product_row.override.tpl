{if $product.item_type == "P" && $product.extra.extra.buy_together}
    <tr>
        <td>
            {$product.product}

            {hook name="cart:product_info"}
            {/hook}
        </td>
        {if $show_price}
            <td class="center">{$product.amount}</td>
            <td class="right">{include file="common/price.tpl" value=$product.price span_id="c_`$customer.user_id`_`$product.item_id`"}</td>
        {/if}
    </tr>
    <tr><td {if $show_price}colspan="3"{/if}>
        <table cellpadding="0" cellspacing="0" border="0" width="90%" class="table table--relative margin-bottom" align="center">
        <tr>
            <th width="100%">{__("product")}</th>
            {if $show_price}
                <th class="center">{__("quantity")}</th>
                <th class="right">{__("price")}</th>
            {/if}
        </tr>
        {$base_product = $product}
        {if $cart_products}
            {$products = $cart_products}
        {/if}
        {if $wishlist_products}
            {$products = $wishlist_products}
        {/if}
        {foreach $products as $product}
            {if $product.extra.extra.parent.buy_together && ($product.extra.extra.parent.buy_together == $base_product.item_id || $product.extra.extra.parent.buy_together == $base_product.extra.extra.buy_id)}
            <tr>
                <td>
                    {$product.product}

                    {hook name="cart:product_info"}
                    {/hook}
                </td>
                {if $show_price}
                    <td class="center">{$product.amount}</td>
                    <td class="right">{include file="common/price.tpl" value=$product.price span_id="c_`$customer.user_id`_`$product.item_id`"}</td>
                {/if}
            </tr>
            {/if}
        {/foreach}
        </table>
    </td></tr>
{/if}