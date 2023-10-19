{foreach from=$order_info.shipping item="shipping_method"}
	{if $shipping_method.store_data}
        <p class="ty-strong">
            {$shipping_method.store_data.name}
        </p>
        <p class="ty-muted">
            {$shipping_method.store_data.city}{if $shipping_method.store_data.pickup_address}, {$shipping_method.store_data.pickup_address}{/if}</br>
            {if $shipping_method.store_data.pickup_phone}
                {__("phone")}: {$shipping_method.store_data.pickup_phone}</br>
            {/if}
            {if $shipping_method.store_data.pickup_time}
                {__("store_locator.work_time")}: {$shipping_method.store_data.pickup_time}</br>
            {/if}
            {$shipping_method.store_data.description nofilter}
        </p>

        {$store = $shipping_method.store_data}
        {$map_container_id = "sl_pickup_order_map"}

        {hook name="orders:store_locator_orders_map"}
        {/hook}

    {/if}

    {script src="js/addons/store_locator/pickup.js"}
{/foreach}