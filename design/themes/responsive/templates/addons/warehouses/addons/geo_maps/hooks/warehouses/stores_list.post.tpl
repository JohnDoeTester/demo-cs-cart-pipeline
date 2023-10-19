<div class="ty-warehouses-stores-map store-locator hidden"
     id="warehouses_map_{$block.block_id}"
     data-ca-warehouses-view-selector="#warehouses_view_selector_{$block.block_id}_map"
>
    <div class="pickup__map-wrapper">
        {foreach $items as $group_id => $stores_group}

            {foreach $stores_group.items as $store}
                {capture name="marker_content"}
                    <div class="store-locator-ya-baloon">
                        <strong class="store-locator-ya-baloon__store-name">{$store.name}</strong>

                        {if $store.pickup_address}<p class="store-locator-ya-baloon__store-address">{$store.pickup_address nofilter}</p>{/if}
                        {if $store.pickup_phone}<p class="store-locator-ya-baloon__store-phone"><a href="tel:{$store.pickup_phone nofilter}">{$store.pickup_phone nofilter}</a></p>{/if}
                        {if $store.pickup_time}<p class="store-locator-ya-baloon__store-time">{$store.pickup_time nofilter}</p>{/if}
                        {if $store.description}<div class="store-locator-ya-baloon__store-description">{$store.description nofilter}</div>{/if}
                    </div>
                {/capture}

                <div class="cm-sl-pickup-map-marker hidden"
                     data-ca-geo-map-marker-lat="{$store.latitude}"
                     data-ca-geo-map-marker-lng="{$store.longitude}"
                     id="warehouses_marker_{$block.block_id}_{$store.store_location_id}"
                >{$smarty.capture.marker_content nofilter}</div>

                {if $store.latitude && $store.longitude}
                    {$initial_lat = $store.latitude}
                    {$initial_lng = $store.longitude}
                {/if}
            {/foreach}
        {/foreach}

        <div class="pickup__map-container ty-warehouses-stores-map__map"
             id="warehouses_map_{$block.block_id}_map"
             data-ca-geo-map-initial-lat="{$initial_lat|default:$smarty.const.STORE_LOCATOR_DEFAULT_LATITUDE|doubleval}"
             data-ca-geo-map-initial-lng="{$initial_lng|default:$smarty.const.STORE_LOCATOR_DEFAULT_LONGITUDE|doubleval}"
             data-ca-geo-map-zoom="16"
             data-ca-geo-map-controls-enable-zoom="true"
             data-ca-geo-map-controls-enable-fullscreen="true"
             data-ca-geo-map-controls-enable-layers="true"
             data-ca-geo-map-controls-enable-ruler="true"
             data-ca-geo-map-behaviors-enable-drag="true"
             data-ca-geo-map-behaviors-enable-drag-on-mobile="false"
             data-ca-geo-map-behaviors-enable-dbl-click-zoom="true"
             data-ca-geo-map-behaviors-enable-multi-touch="true"
             data-ca-geo-map-language="{$smarty.const.CART_LANGUAGE}"
             data-ca-geo-map-marker-selector=".cm-sl-pickup-map-marker"
        ></div>
        <div class="pickup__map-container--mobile-hint">{__("store_locator.use_two_fingers_for_move_map")}</div>
    </div>
</div>
