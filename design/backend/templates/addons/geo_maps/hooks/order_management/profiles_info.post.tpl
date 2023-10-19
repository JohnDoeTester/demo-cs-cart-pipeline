{if $settings.geo_maps.general.provider === "yandex"}
    {$show_move_map_mobile_hint = true}
{/if}
<div class="sidebar-row" id="om_ajax_location_map">
    <h6>{__("geo_maps.shipping_address_on_map")}</h6>
    {if $user_data.s_country_descr || $user_data.s_city || $user_data.s_address}
        <div class="geo-map__map-container">
            <div class="cm-geo-map-container cm-aom-map-container"
                data-ca-geo-map-language="{$smarty.const.CART_LANGUAGE}"
                data-ca-aom-country="{$user_data.s_country_descr}"
                data-ca-aom-city="{$user_data.s_city}"
                data-ca-aom-address="{$user_data.s_address}"
                data-ca-geo-map-controls-enable-search="false"
                data-ca-geo-map-behaviors-enable-drag="true"
                data-ca-geo-map-behaviors-enable-drag-on-mobile="false"
                data-ca-geo-map-behaviors-enable-smart-drag="true"
                data-ca-geo-map-behaviors-enable-dbl-click-zoom="true"
                data-ca-geo-map-behaviors-enable-multi-touch="true"
            ></div>
        </div>
        {if $show_move_map_mobile_hint}
            <div class="mobile-visible muted">{__("store_locator.use_two_fingers_for_move_map")}</div>
        {/if}
    {else}
        <p class="no-items">{__("no_data")}</p>
    {/if}
<!--om_ajax_location_map--></div>
