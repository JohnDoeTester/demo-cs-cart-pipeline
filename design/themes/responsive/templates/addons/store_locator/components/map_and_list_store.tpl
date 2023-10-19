{$_max_desktop_items = $max_desktop_items|default:5}
{if $settings.geo_maps.general.provider === "yandex"}
    {$show_move_map_mobile_hint = true}
{/if}

<div class="pickup{if $display_pickup_map} pickup--map-list{else} pickup--list{/if}">

    {* Map *}
    {if $display_pickup_map}
        {$map_container_id = "store_locator_stores_map"}
        {hook name="checkout:store_locator_search_map"}
        {/hook}
    {/if}

    {* For mobiles; List wrapper with selected pickup item *}
    {foreach from=$store_locations item=store_location}

        {foreach from=$store_location item=store}
            {if $old_store_id == $store.store_location_id}
            <div class="pickup__offices-wrapper visible-phone pickup__offices-wrapper--near-map">
                {* List *}
                <div class="store-locator__fields-row store-locator__fields-row--wrapped pickup__offices pickup__offices--list pickup__offices--list-no-height">
                    {include file="addons/store_locator/components/items/store.tpl" store=$store}
                </div>
                {* End of List *}
            </div>
            {/if}
        {/foreach}
    {/foreach}
    <a href="#store_locator_search_form" class="store-locator__scroll-top-btn">{__("store_locator.scroll_to_top")}</a>

    {* List wrapper *}
    <div class="pickup__offices-wrapper pickup__offices-wrapper-open">

        {* Search *}
        <div class="pickup__search">
            {if $store_locations_count >= $_max_desktop_items}
                <div class="pickup__search-field store-locator__field">
                    <input type="text" id="pickup-search" class="store-locator__input js-store-locator-search-input" placeholder=" "
                        value />
                    <label class="store-locator__label" for="pickup-search">{__("storefront_search_label")}</label>
                </div>
            {/if}
            {* <div class="pickup__search-field store-locator__field store-locator__field--search-pickup sl-search-control">
                <input type="checkbox" name="sl_search[pickup_only]" value="Y" id="store_locator_search_pickup" {if $sl_search.pickup_only}checked{/if}/>
                <label for="store_locator_search_pickup">{__("store_locator.show_pickup_points_only")}</label>
            </div> *}
            {if $display_pickup_map}
                <div class="pickup__search-field store-locator__field store-locator__all-stores cm-store-locator__all-stores store-locator__all-stores--hidden sl-search-control">
                    <button class="cm-store-locator-view-locations store-locator__all-stores-btn" type="button" data-ca-stores-list-filter-id="pickup-search" data-ca-target-map-id="{$map_container_id}">{__("view_all")}</button>
                </div>
            {/if}
            </div>
        {* End of Search *}

        {* List *}
        <label for="pickup_office_list"
               class="cm-required cm-multiple-radios hidden"
               data-ca-validator-error-message="{__("pickup_point_not_selected")}"></label>
        <div class="store-locator__fields-row store-locator__fields-row--wrapped pickup__offices pickup__offices--list"
             id="pickup_office_list"
        >
            {foreach from=$store_locations key=store_city item=store_location}

                <div class="js-one-city ty-one-city">
                    {if $store_locations|@count > 1}
                        <div class="ty-one-city__name">{$store_city}</div>
                    {/if}

                    {foreach from=$store_location item=store}
                        {include file="addons/store_locator/components/items/store.tpl" store=$store}
                    {/foreach}
                </div>
            {/foreach}
        </div>
        {* End of List *}
        <div class="js-store-locator__not-found ty-store-locator__not-found ty-store-locator__not-found__hidden">{__("text_address_not_found")}</div>

    </div>
    {* End of List wrapper *}

</div>
