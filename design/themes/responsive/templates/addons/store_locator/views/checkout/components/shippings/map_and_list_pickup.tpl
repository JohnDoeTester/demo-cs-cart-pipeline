{$_max_desktop_items = $max_desktop_items|default:5}
{if $settings.geo_maps.general.provider === "yandex"}
    {$show_move_map_mobile_hint = true}
{/if}

<div class="litecheckout__item ty-checkout-select-store__map-full-div pickup pickup--map-list">

    {* Map *}
    <div class="ty-checkout-select-store__map pickup__map-wrapper">
        {if $display_pickup_map}

            {foreach from=$shipping.data.stores item=store}
                {capture name="marker_content"}
                    <div class="litecheckout-ya-baloon">
                        <strong class="litecheckout-ya-baloon__store-name">{$store.name}{if $store.pickup_rate > 0} — {include file="common/price.tpl" value=$store.pickup_rate}{/if}</strong>

                        {if $store.pickup_address}<p class="litecheckout-ya-baloon__store-address">{$store.pickup_address nofilter}</p>{/if}

                        <p class="litecheckout-ya-baloon__select-row">
                            <a data-ca-shipping-id="{$shipping.shipping_id}"
                               data-ca-group-key="{$group_key}"
                               data-ca-location-id="{$store.store_location_id}"
                               class="cm-sl-pickup-select-location ty-btn ty-btn__primary text-button ty-width-full"
                            >{__("select")}</a>
                        </p>

                        {if $store.pickup_phone}<p class="litecheckout-ya-baloon__store-phone"><a href="tel:{$store.pickup_phone nofilter}">{$store.pickup_phone nofilter}</a></p>{/if}
                        {if $store.pickup_time}<p class="litecheckout-ya-baloon__store-time">{$store.pickup_time nofilter}</p>{/if}
                        {if $store.description}<div class="litecheckout-ya-baloon__store-description">{$store.description nofilter}</div>{/if}
                    </div>
                {/capture}

                {$is_store_selected = $old_store_id == $store.store_location_id || $store_count == 1}

                {if $is_store_selected}
                    {$selected_store_lat = $store.latitude}
                    {$selected_store_lng = $store.longitude}
                {/if}

                <div class="cm-sl-pickup-map-marker-{$shipping.shipping_id}-{$group_key} hidden"
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
            <div class="pickup__map-container cm-geo-map-container"
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
                 data-ca-geo-map-marker-selector=".cm-sl-pickup-map-marker-{$shipping.shipping_id}-{$group_key}"
            ></div>
            {if $show_move_map_mobile_hint}
                <div class="pickup__map-container--mobile-hint">{__("lite_checkout.use_two_fingers_for_move_map")}</div>
            {/if}
        {/if}
    </div>

    {* For mobiles; List wrapper with selected pickup item *}
    {foreach from=$shipping.data.stores item=store}
        {if $old_store_id == $store.store_location_id}
        <div class="ty-checkout-select-store pickup__offices-wrapper visible-phone pickup__offices-wrapper--near-map">
            {* List *}
            <div class="litecheckout__fields-row litecheckout__fields-row--wrapped pickup__offices pickup__offices--list pickup__offices--list-no-height">
                {include file="addons/store_locator/views/checkout/components/shippings/items/pickup.tpl" store=$store}
            </div>
            {* End of List *}
        </div>
        {/if}
    {/foreach}
    {* For mobiles; List wrapper with selected pickup item *}

    {* For mobiles; button for popup with pickup points *}
    <button class="ty-btn ty-btn__secondary cm-open-pickups pickup__open-pickupups-btn visible-phone"
        data-ca-title="{__('lite_checkout.choose_from_list')}"
        data-ca-target=".pickup__offices-wrapper-open"
        type="button"
    >{__('lite_checkout.choose_from_list')}</button>
    <span class="visible-phone cm-open-pickups-msg"></span>
    {* For mobiles; button for popup with pickup points *}

    {* List wrapper *}
    <div class="ty-checkout-select-store pickup__offices-wrapper pickup__offices-wrapper-open hidden-phone">

        {* Search *}
        {if $shipping.data.stores|count >= $_max_desktop_items}
        <div class="pickup__search">
            <div class="pickup__search-field litecheckout__field">
                <input type="text" id="pickup-search" class="litecheckout__input js-pickup-search-input" data-ca-pickup-group-key="{$group_key}" placeholder=" "
                    value />
                <label class="litecheckout__label" for="pickup-search">{__("storefront_search_label")}</label>
            </div>
        </div>
        {/if}
        {* End of Search *}

        {* List *}
        <label for="pickup_office_list"
               class="cm-required cm-multiple-radios hidden"
               data-ca-validator-error-message="{__("pickup_point_not_selected")}"></label>
        <div class="litecheckout__fields-row litecheckout__fields-row--wrapped pickup__offices pickup__offices--list
            {if $shipping.data.stores|count < $_max_desktop_items}
                pickup__offices--list--no-sorting
            {/if}"
             id="pickup_office_list"
             data-ca-error-message-target-node-change-on-screen="xs,xs-large,sm"
             data-ca-error-message-target-node-after-mode="true"
             data-ca-error-message-target-node-on-screen=".cm-open-pickups-msg"
             data-ca-error-message-target-node=".pickup__offices--list"
        >
            {foreach from=$shipping.data.stores item=store}
                {include file="addons/store_locator/views/checkout/components/shippings/items/pickup.tpl" store=$store}
            {/foreach}
        </div>
        {* End of List *}

    </div>
    {* End of List wrapper *}

</div>
