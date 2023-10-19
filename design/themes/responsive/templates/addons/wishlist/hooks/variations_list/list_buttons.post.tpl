{if $show_add_to_wishlist}
    <div class="ty-valign-top">
        {include file="buttons/button.tpl" but_id="button_wishlist_`$obj_prefix``$product.product_id`" but_meta="ty-btn__tertiary ty-add-to-wish" but_name="dispatch[wishlist.add..`$product.product_id`]" but_role="text" but_icon="ty-icon-heart" but_onclick=$but_onclick but_href=$but_href}
    </div>
{/if}
