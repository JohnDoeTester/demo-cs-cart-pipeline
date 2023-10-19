{if $product_groups}
    {foreach from=$product_groups key=group_key item=group}
        {if $group.shippings && !$group.shipping_no_required}

            {foreach from=$group.shippings item=shipping}
                {if $cart.chosen_shipping.$group_key == $shipping.shipping_id}
                
                    {if $shipping.data.stores}

                        {assign var="old_store_id" value=$old_ship_data.$group_key.store_location_id}
                        {assign var="shipping_id" value=$shipping.shipping_id}
                        {assign var="select_id" value=$select_store.$group_key.$shipping_id}
                        {assign var="store_count" value=$shipping.data.stores|count}

                        {if $store_count == 1}
                            {foreach from=$shipping.data.stores item=store}
                            <div class="sidebar-row">
                                <input type="hidden"
                                    name="select_store[{$group_key}][{$shipping_id}]"
                                    value="{$store.store_location_id}"
                                    id="store_{$group_key}_{$shipping_id}_{$store.store_location_id}"
                                    class="cm-submit cm-ajax cm-skip-validation"
                                    data-ca-dispatch="dispatch[order_management.update_shipping]"
                                    data-ca-pickup-select-store="true"
                                    data-ca-shipping-id="{$shipping_id}"
                                    data-ca-group-key="{$group_key}"
                                    data-ca-location-id="{$store.store_location_id}">
                                {$store.name} {if $store.pickup_rate}({include file="common/price.tpl" value=$store.pickup_rate}){/if}
                                <p class="muted">
                                {$store.city}, {$store.pickup_address},
                                {$store.pickup_phone}<br/>
                                {__("store_locator.work_time")}: {$store.pickup_time}
                                <br/>
                                {if $store.delivery_time}{__("delivery_time")}: {$store.delivery_time}{/if}
                                </p>
                            </div>
                            {/foreach}
                        {else}
                            {foreach from=$shipping.data.stores item=store}
                            <div class="sidebar-row">
                                <div class="control-group">
                                    <div id="pickup_stores" class="controls">
                                        <label for="store_{$group_key}_{$shipping_id}_{$store.store_location_id}" class="radio">
                                            <input type="radio"
                                                name="select_store[{$group_key}][{$shipping_id}]"
                                                value="{$store.store_location_id}"
                                                {if $select_id == $store.store_location_id || (!$select_id && $old_store_id == $store.store_location_id)}checked="checked"{/if}
                                                id="store_{$group_key}_{$shipping_id}_{$store.store_location_id}"
                                                class="cm-submit cm-ajax cm-skip-validation"
                                                data-ca-dispatch="dispatch[order_management.update_shipping]"
                                                data-ca-pickup-select-store="true"
                                                data-ca-shipping-id="{$shipping_id}"
                                                data-ca-group-key="{$group_key}"
                                                data-ca-location-id="{$store.store_location_id}">
                                                {$store.name} {if $store.pickup_rate}({include file="common/price.tpl" value=$store.pickup_rate}){/if}
                                        </label>
                                        <p class="muted">                                
                                            {$store.city}, {$store.pickup_address},
                                            {$store.pickup_phone}<br/>
                                            {__("store_locator.work_time")}: {$store.pickup_time}
                                            <br/>
                                            {if $store.delivery_time}{__("delivery_time")}: {$store.delivery_time}{/if}
                                        </p>
                                    </div>    
                                </div> 
                            </div>
                            {/foreach}
                        {/if}
                    {/if}
                {/if}
            {/foreach}
        {/if}
    {/foreach}
{/if}