{if !$hide_wishlist_button}

    {include file="addons/wishlist/views/wishlist/components/add_to_wishlist.tpl"
        wishlist_but_id="button_wishlist_`$obj_prefix``$product.product_id`"
        wishlist_but_name="dispatch[wishlist.add..`$product.product_id`]"
        wishlist_but_role="text"
    }
{/if}