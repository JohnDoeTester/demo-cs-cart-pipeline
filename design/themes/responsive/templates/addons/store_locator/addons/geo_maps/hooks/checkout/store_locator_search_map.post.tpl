<div class="pickup__map-wrapper">

    {foreach from=$store_locations item=store_location}

        {foreach from=$store_location item=store}
            {capture name="marker_content"}
                <div class="store-locator-ya-baloon">
                    <strong class="store-locator-ya-baloon__store-name">{$store.name}{if $store.pickup_rate > 0} — {include file="common/price.tpl" value=$store.pickup_rate}{/if}</strong>

                    {if $store.pickup_address}<p class="store-locator-ya-baloon__store-address">{$store.pickup_address nofilter}</p>{/if}
                    {if $store.pickup_phone}<p class="store-locator-ya-baloon__store-phone"><a href="tel:{$store.pickup_phone nofilter}">{$store.pickup_phone nofilter}</a></p>{/if}
                    {if $store.pickup_time}<p class="store-locator-ya-baloon__store-time">{$store.pickup_time nofilter}</p>{/if}
                    {if $store.description}<div class="store-locator-ya-baloon__store-description">{$store.description nofilter}</div>{/if}
                </div>
            {/capture}

            {$is_store_selected = $old_store_id == $store.store_location_id || $store_count == 1}

            {if $is_store_selected}
                {$selected_store_lat = $store.latitude}
                {$selected_store_lng = $store.longitude}
            {/if}

            <div class="cm-sl-pickup-map-marker hidden"
                 data-ca-geo-map-marker-lat="{$store.latitude}"
                 data-ca-geo-map-marker-lng="{$store.longitude}"
                    {if $is_store_selected}
                        data-ca-geo-map-marker-selected="true"
                    {/if}
            >{$smarty.capture.marker_content nofilter}</div>

            {if $store.latitude && $store.longitude}
                {$initial_lat = $selected_store_lat|default:$store.latitude}
                {$initial_lng = $selected_store_lng|default:$store.longitude}
            {/if}

        {/foreach}
    {/foreach}
    <div class="pickup__map-container cm-geo-map-container"
         id="{$map_container_id}"
         data-ca-geo-map-initial-lat="{$initial_lat|default:$smarty.const.STORE_LOCATOR_DEFAULT_LATITUDE|doubleval}"
         data-ca-geo-map-initial-lng="{$initial_lng|default:$smarty.const.STORE_LOCATOR_DEFAULT_LONGITUDE|doubleval}"
         data-ca-geo-map-zoom="16"
         data-ca-geo-map-controls-enable-zoom="true"
         data-ca-geo-map-controls-enable-fullscreen="true"
         data-ca-geo-map-controls-enable-layers="true"
         data-ca-geo-map-controls-enable-ruler="true"
         data-ca-geo-map-behaviors-enable-drag="true"
         data-ca-geo-map-behaviors-enable-drag-on-mobile="false"
         data-ca-geo-map-behaviors-enable-smart-drag="true"
         data-ca-geo-map-behaviors-enable-dbl-click-zoom="true"
         data-ca-geo-map-behaviors-enable-multi-touch="true"
         data-ca-geo-map-language="{$smarty.const.CART_LANGUAGE}"
         data-ca-geo-map-marker-selector=".cm-sl-pickup-map-marker"
    ></div>
    {if $show_move_map_mobile_hint}
        <div class="pickup__map-container--mobile-hint">{__("store_locator.use_two_fingers_for_move_map")}</div>
    {/if}
</div>
