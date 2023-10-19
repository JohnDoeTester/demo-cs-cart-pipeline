{if $carts}
<div class="ty-checkout-complete__iv-pending-carts-notice">
    {__("direct_payments.pending_carts_notice", ["[cart_url]" => fn_url("checkout.cart")]) nofilter}
</div>
{/if}