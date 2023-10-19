{if $settings.geo_maps.general.provider === "yandex"}
    {$show_move_map_mobile_hint = true}
{/if}
<button
        class="cm-dialog-opener cm-dialog-auto-size ty-btn ty-btn__tertiary"
        data-ca-target-id="map_{$map_container_id}"
        data-ca-dialog-title="{__("view_on_map")}"
        data-ca-scroll="true"
        action="button"
>{__("view_on_map")}</button>

<div class="hidden object-container">
    <div id="map_{$map_container_id}" class="hidden clearfix ty-checkout-select-store__map-full-div ty-checkout-select-store__map-full-div-in-popup store-locator">
        <div class="ty-checkout-select-store__map-details pickup__map-container cm-geo-map-container" id="{$map_container_id}"
             data-ca-geo-map-initial-lat="{$store.latitude}"
             data-ca-geo-map-initial-lng="{$store.longitude}"
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
             data-ca-geo-map-marker-selector=".cm-sl-pickup-maps-marker-{$shipping_method.shipping_id}"
        ></div>
        {if $show_move_map_mobile_hint}
            <div class="pickup__map-container--mobile-hint">{__("store_locator.use_two_fingers_for_move_map")}</div>
        {/if}
    </div>
    {capture name="marker_content"}
        <div style="padding-right: 10px">
            <strong>{$store.name}</strong>
            <p>
                {if $store.city}{$store.city nofilter}, {/if}
                {if $store.pickup_address}{$store.pickup_address nofilter}{/if}
                {if $store.pickup_phone}<br/>{$store.pickup_phone nofilter}{/if}
                {if $store.pickup_time}<br/>{$store.pickup_time nofilter}{/if}
                {if $store.description}<br/>{$store.description nofilter}{/if}
            </p>
        </div>
    {/capture}

    <div class="cm-sl-pickup-maps-marker-{$shipping_method.shipping_id} hidden"
         data-ca-geo-map-marker-lat="{$store.latitude}"
         data-ca-geo-map-marker-lng="{$store.longitude}"
         data-ca-geo-map-marker-selected="true"
         data-ca-geo-map-marker-static="true"
    >{$smarty.capture.marker_content nofilter}</div>
</div>