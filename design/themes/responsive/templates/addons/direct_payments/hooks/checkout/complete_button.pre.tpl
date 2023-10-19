{if $carts}
    <div class="ty-checkout-complete__buttons-left">
        {include file="buttons/button.tpl"
                 but_meta="ty-btn__primary ty-btn__iv-pending-carts"
                 but_text=__("view_cart")
                 but_href="checkout.cart"
        }
        &nbsp;
    </div>
{/if}