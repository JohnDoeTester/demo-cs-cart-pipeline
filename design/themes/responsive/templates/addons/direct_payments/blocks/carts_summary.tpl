{** block-description:direct_payments.carts_summary **}

{if count($carts) > 1}
    <div id="checkout_carts_{$block.snapping_id}">
        <table class="ty-checkout-summary__block">
            <tbody>
            {foreach $carts as $vendor_id => $vendor_cart}
                <tr>
                    <td class="ty-checkout-summary__item">
                        {if $vendor_id == $cart.vendor_id}<strong>{/if}
                        {$vendors.$vendor_id.company}
                        {strip}
                            (
                            {if $vendor_id == $cart.vendor_id}
                                {__("direct_payments.paying_now")}
                            {else}
                                <a href="{"checkout.checkout?vendor_id=`$vendor_id`"|fn_url}">{__("checkout")}</a>
                            {/if}
                            )
                        {/strip}
                        {if $vendor_id == $cart.vendor_id}</strong>{/if}
                    </td>
                    <td class="ty-checkout-summary__item ty-right" data-ct-checkout-summary="items">
                        <span>{include file="common/price.tpl" value=$vendor_cart.total}</span>
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
        <!--checkout_carts_{$block.snapping_id}--></div>
{/if}
