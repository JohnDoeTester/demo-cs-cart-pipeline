{if $customer.wishlist_products}
    <div class="muted">{__("wishlist_short")}: {$customer.wishlist_products|default:"0"}</div>
{/if}