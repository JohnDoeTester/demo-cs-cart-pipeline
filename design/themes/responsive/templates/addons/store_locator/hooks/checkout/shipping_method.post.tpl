{if $cart.chosen_shipping.$group_key == $shipping.shipping_id && $shipping.module == "store_locator"}

    {script src="js/addons/store_locator/pickup.js"}

    {$store_count = $shipping.data.stores|count}
    {$shipping_id = $shipping.shipping_id}
    {$old_store_id = $select_store.$group_key.$shipping_id}

    {if $shipping.service_params.display}
        {$display_type = $shipping.service_params.display}
    {else}
        {$display_type = "ML"}
    {/if}

    {$store_locations = $shipping.data.stores}

    {if $display_type != "L"}
        {$display_pickup_map = true}
    {/if}

    <div class="litecheckout__item">
        <h2 class="litecheckout__step-title">{__("lite_checkout.select_pickup_item")}</h2>
    </div>

    {hook name="checkout:store_locator_pickup_content"}
        {include file="addons/store_locator/views/checkout/components/shippings/list_pickup.tpl"}
    {/hook}
{/if}
