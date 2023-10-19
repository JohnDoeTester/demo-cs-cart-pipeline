{if $is_wishlist}
<div class="wishlist-remove-item">
    <a href="{"wishlist.delete?cart_id=`$product.cart_id`"|fn_url}" class="ty-remove" title="{__("remove")}">{include_ext file="common/icon.tpl" class="ty-icon-cancel-circle ty-remove__icon"}<span class="ty-remove__txt">{__("remove")}</span></a>
</div>
{/if}
