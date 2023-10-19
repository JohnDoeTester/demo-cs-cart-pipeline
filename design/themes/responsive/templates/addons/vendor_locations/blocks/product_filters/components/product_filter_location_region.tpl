<div class="ty-product-filters__block">
    <div class="ty-product-filters {if $collapse}hidden{/if}" id="content_{$filter_uid}">
        <div class="ty-product-filters__search ty-filter-products-by-geolocation-filter-address">
            <input type="text"
                   id="elm_filter_geolocation_{$filter_uid}"
                   name="vendor_search_geolocation"
                   class="cm-geocomplete ty-input-text-medium cm-filter-products-by-geolocation-geolocation-input"
                   data-ca-geocomplete-place-id="{$filter.location_place_id}"
                   data-ca-geocomplete-type="(regions)"
                   data-ca-geocomplete-value-elem-id="elm_checkbox_address_{$filter_uid}"
                   data-ca-filter-type="region">
            {include_ext file="common/icon.tpl"
                class="ty-icon-cancel-circle ty-product-filters__search-icon hidden"
                id="elm_search_clear_`$filter_uid`"
                title=__("clear")
            }
            {include_ext file="common/icon.tpl"
                class="ty-icon-target cm-filter-geolocation-use-my-location-button ty-vendors-locations-use-my-location"
                data=[
                    "data-ca-filter-geocomplete-elem-id" => "elm_filter_geolocation_`$filter_uid`"
                ]
            }
        </div>

        <input id="elm_checkbox_address_{$filter_uid}" data-ca-filter-id="{$filter.filter_id}" class="cm-product-filters-checkbox hidden" type="checkbox" name="product_filters[{$filter.filter_id}]" value="{$filter.location_hash}" {if $filter.location_hash}checked="checked{/if}">

        <button class="hidden set-distance ty-btn">{__("choose")}</button>
    </div>
</div>
