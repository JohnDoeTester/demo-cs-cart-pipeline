{if $shipping.service_code == 'pickup'}
    <div class="clearfix" id="rate_extra_{$shipping_estimation_block_id}_{$group_key}_{$shipping.shipping_id}{$id_suffix}">
    {if $cart.chosen_shipping.$group_key == $shipping.shipping_id}
        <div class="ty-checkout-select-store__estimation">
            {assign var="store_count" value=$shipping.data.stores|count}
            {assign var="shipping_id" value=$shipping.shipping_id}
            {assign var="old_store_id" value=$select_store.$group_key.$shipping_id}

            {assign var="store_locations" value=$shipping.data.stores}

            {foreach from=$shipping.data.stores item=store}
                <div class="ty-one-store">
                    <input type="radio"
                    name="select_store[{$group_key}][{$shipping.shipping_id}]"
                    value="{$store.store_location_id}"
                    {if $old_store_id == $store.store_location_id || $store_count == 1}checked="checked"{/if}
                    id="store_{$group_key}_{$shipping.shipping_id}_{$store.store_location_id}"
                    class="ty-one-store__radio ty-valign"
                    onclick="fn_calculate_total_shipping();"
                    data-ca-pickup-select-store="true"
                    data-ca-shipping-id="{$shipping.shipping_id}"
                    data-ca-group-key="{$group_key}"
                    data-ca-location-id="{$store.store_location_id}">
                    <div class="ty-one-store__label">
                        <label for="store_{$group_key}_{$shipping.shipping_id}_{$store.store_location_id}" class="ty-valign"  >
                            <span class="ty-one-store__name">
                                {$store.name}
                                {if $store.pickup_rate}({include file="common/price.tpl" value=$store.pickup_rate}){/if}
                            </span>
                            {if $store.pickup_address}
                                <span class="mutted">
                                    <br/>{$store.pickup_address}
                                </span>
                            {/if}
                            {if $store.delivery_time}
                                <span class="mutted">
                                    <br/>{__("delivery_time")}: {$store.delivery_time}
                                </span>
                            {/if}
                        </label>
                    </div>
                </div>
            {/foreach}
        </div>
    {/if}
    <!--rate_extra_{$shipping_estimation_block_id}_{$group_key}_{$shipping.shipping_id}{$id_suffix}--></div>
{/if}

