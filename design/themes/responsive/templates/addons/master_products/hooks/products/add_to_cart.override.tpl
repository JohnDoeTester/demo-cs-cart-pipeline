{if $product.master_product_id || !$product.company_id}
    {hook name="products:add_to_cart"}
        {$obj_id = $product.best_product_offer_id}
        {if $product.has_options && !$show_product_options && !$details_page}
            {if $but_role == "text"}
                {$opt_but_role="text"}
            {else}
                {$opt_but_role="action"}
            {/if}

            {include file="buttons/button.tpl" but_id="button_cart_`$obj_prefix``$obj_id`" but_text=__("select_options") but_href="products.view?product_id=`$product.product_id`" but_role=$opt_but_role but_name="" but_meta="ty-btn__primary ty-btn__big"}
        {else}
            {hook name="products:add_to_cart_but_id"}
                {$_but_id="button_cart_`$obj_prefix``$obj_id`"}
            {/hook}

            {if $extra_button}{$extra_button nofilter}&nbsp;{/if}
            {include file="buttons/add_to_cart.tpl" but_id=$_but_id but_name="dispatch[checkout.add..`$obj_id`]" but_role=$but_role block_width=$block_width obj_id=$obj_id product=$product but_meta=$add_to_cart_meta}

            {assign var="cart_button_exists" value=true}
        {/if}

        {if $product.best_product_offer_id}
            <input type="hidden" name="product_data[{$product.product_id}][product_id]" value="{$product.best_product_offer_id}" />
        {/if}
    {/hook}
{/if}