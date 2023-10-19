{script src="js/tygh/exceptions.js"}
{script src="js/tygh/checkout.js"}
{script src="js/tygh/cart_content.js"}

{if $carts}
    <h1 class="ty-mainbox-title {if $carts|count > 1}ty-mve-title{/if}">{__("cart_contents")}
        {if $carts|count > 1}
            <div class="ty-mve-total ty-float-right"
                 id="checkout_totals_header_general">
                {__("total_cost")}:&nbsp;{include file="common/price.tpl" value=$carts_total class="ty-price"}
            <!--checkout_totals_header_general--></div>
        {/if}
    </h1>

    {foreach $carts as $vendor_id => $cart}

        {include file="addons/direct_payments/views/separate_checkout/components/cart_content.tpl"
                 vendor_id=$vendor_id
                 vendor=$vendors.$vendor_id
                 cart=$cart
                 cart_products=$group_cart_products.$vendor_id
                 product_groups=$group_product_groups.$vendor_id
                 checkout_add_buttons=$group_checkout_add_buttons.$vendor_id
                 take_surcharge_from_vendor=$group_take_surcharge_from_vendor.$vendor_id
                 payment_methods=$group_payment_methods.$vendor_id
        }
    {/foreach}
{else}
    <p class="ty-no-items">{__("text_cart_empty")}</p>

    <div class="buttons-container wrap">
        {include file="buttons/continue_shopping.tpl" but_href=$continue_url|fn_url but_role="submit"}
    </div>
{/if}
