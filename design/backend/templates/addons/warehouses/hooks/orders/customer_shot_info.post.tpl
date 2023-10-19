{if $order_info.shipping}
    {$shipping = $order_info.shipping|reset}
{else}
    {$shipping = []}
{/if}

{$unique_warehouse_ids = []}
{if $shipping.changed_warehouse_data}
    {foreach $shipping.changed_warehouse_data as $product_id => $warehouses}
        {foreach $warehouses as $warehouse_data}
            {if $warehouse_data.store_location_id|in_array:$unique_warehouse_ids}
                {continue}
            {else}
                {$unique_warehouse_ids[] = $warehouse_data.store_location_id}
            {/if}
            <div class="well orders-right-pane form-horizontal">
                <div class="control-group shift-top">
                    <div class="control-label">
                        {include file="common/subheader.tpl" title=__("warehouses.store_warehouse")}
                    </div>
                </div>
                {if ($warehouse_data.name)}
                    <p class="strong">
                        {$warehouse_data.name}
                    </p>
                {/if}
                <p class="muted">
                    {if $warehouse_data.city}{$warehouse_data.city}, {/if}
                    {if $warehouse_data.pickup_address}{$warehouse_data.pickup_address}<br />{/if}
                    {if $warehouse_data.pickup_phone}{$warehouse_data.pickup_phone}<br />{/if}
                    {if $warehouse_data.pickup_time}{__("store_locator.work_time")}: {$warehouse_data.pickup_time}<br />{/if}
                </p>
            </div>
        {/foreach}
    {/foreach}
{/if}