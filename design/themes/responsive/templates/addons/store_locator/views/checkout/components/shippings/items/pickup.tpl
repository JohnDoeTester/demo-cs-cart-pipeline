<label for="store_{$group_key}_{$shipping.shipping_id}_{$store.store_location_id}" 
        class="ty-one-store js-pickup-search-block-{$group_key} {if ($old_store_id == $store.store_location_id) || ($store_count == 1)}ty-sdek-office__selected{/if}"
>
    <input type="radio" 
            class="ty-one-store__radio ty-one-store__radio--{$group_key} cm-sl-pickup-select-store"
            name="select_store[{$group_key}][{$shipping.shipping_id}]"
            value="{$store.store_location_id}"
            {if ($old_store_id == $store.store_location_id) || ($store_count == 1)}
            checked="checked"
            {/if}
            id="store_{$group_key}_{$shipping.shipping_id}_{$store.store_location_id}"
            data-ca-pickup-select-store="true"
            data-ca-shipping-id="{$shipping.shipping_id}"
            data-ca-group-key="{$group_key}"
            data-ca-location-id="{$store.store_location_id}"
    />

    <div class="ty-sdek-store__label ty-one-store__label">
        <p class="ty-one-store__name">
            <span class="ty-one-store__name-text">{$store.name}</span>
            {if !empty($shipping.free_shipping)} 
            <span class="ty-one-store__name-rate">
                ({__("free")})
            </span>
            {elseif $store.pickup_rate && $store.pickup_rate > 0}
            <span class="ty-one-store__name-rate">
                ({include file="common/price.tpl" value=$store.pickup_rate})
            </span>
            {/if}
        </p>

        <div class="ty-one-store__description">
            {if $store.pickup_address}
                <span class="ty-one-office__address">{$store.pickup_address}</span>
                <br />
            {/if}
            {if $store.pickup_time}
                <span class="ty-one-office__worktime">{$store.pickup_time}</span>
                <br />
            {/if}
            {if $store.pickup_phone}
                <span class="ty-one-office__worktime">{$store.pickup_phone}</span>
                <br />
            {/if}
            {if $store.delivery_time}
                <span class="ty-one-office__worktime">{__("delivery_time")}: {$store.delivery_time}</span>
                <br />
            {/if}
        </div>
    </div>
</label>
