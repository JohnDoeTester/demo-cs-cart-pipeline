{$products = $wishlist_products}
{$show_price = false}
{$wishlist_products_js_id = "wishlist_products_`$customer.user_id`"}
{if "ULTIMATE"|fn_allowed_for}
    {$wishlist_products_js_id = "`$wishlist_products_js_id`_`$customer.company_id`"}
{/if}
<div id="{$wishlist_products_js_id}">
{if $customer.user_id == $sl_user_id}
    {if $wishlist_products}
    <h4 class="mobile-visible">{__("wishlist_products")}</h4>
    <div class="table-responsive-wrapper">
        <table width="100%" class="table table-condensed table--relative table-responsive table-responsive-w-titles">
            <thead>
                <tr class="no-hover">
                    <th>{__("wishlist_products")}</th>
                </tr>
            </thead>
            <tbody>
                {foreach $wishlist_products as $product}
                    {hook name="cart:product_row"}
                        {if !$product.extra.extra.parent}
                            <tr>
                                <td data-th="&nbsp;">
                                {if $product.item_type == "P"}
                                    {if $product.product}
                                    <a href="{"products.update?product_id=`$product.product_id`"|fn_url}">{$product.product nofilter}</a>
                                    {else}
                                    {__("deleted_product")}
                                    {/if}
                                {/if}
                                {hook name="cart:products_list"}
                                {/hook}
                                </td>
                            </tr>
                        {/if}
                    {/hook}
                {/foreach}
            </tbody>
        </table>
    </div>
    {/if}
{/if}
<!--{$wishlist_products_js_id}--></div>