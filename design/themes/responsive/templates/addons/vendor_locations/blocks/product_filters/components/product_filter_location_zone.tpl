{script src="js/lib/jqueryuitouch/jquery.ui.touch-punch.min.js"}

{$min = 0}
{$max = $addons.vendor_locations.max_search_radius}
{$start_value = $addons.vendor_locations.start_search_radius}
{$range_suffix = $addons.vendor_locations.distance_unit}
{$range_prefix = ""}

{if $filter.location}
    {$location = $filter.location}
    {$value = $location->getRadius()}
{/if}

<div class="ty-product-filters__block">
    <div class="ty-product-filters {if $collapse}hidden{/if}" id="content_{$filter_uid}">
        <div class="ty-product-filters__search ty-filter-products-by-geolocation-filter-address">
            <input type="text"
                   id="elm_filter_geolocation_{$filter_uid}"
                   name="vendor_search_geolocation"
                   class="cm-geocomplete ty-input-text-medium cm-filter-products-by-geolocation-geolocation-input ty-filter-products-by-geolocation-filter-address__input"
                   data-ca-geocomplete-place-id="{$filter.location_place_id}"
                   data-ca-geocomplete-type="address"
                   data-ca-geocomplete-value-elem-id="elm_checkbox_address_{$filter_uid}"
                   {if $location}data-ca-filter-value="{$location->toArray()|to_json}"{/if}
                   data-ca-filter-slider-elem-id="slider_{$filter_uid}"
                   data-ca-filter-type="zone">
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

            {* Slider *}
            <p class="ty-price-slider__inputs ty-filter-products-by-geolocation-filter-address__input-text">
                <bdi class="ty-price-slider__bidi-container">
                    <span class="ty-price-slider__filter-prefix">{$range_prefix nofilter}</span>
                    <span>{__("vendor_locations.search_nearby")}</span>
                    <span id="slider_{$filter_uid}_right">{$value|default:$start_value}</span>
                    <span>{$range_suffix}</span>
                </bdi>
            </p>
            <div id="slider_{$filter_uid}"
                 class="cm-zone-radius-slider ty-range-slider"
                 data-ca-filter-geocomplete-elem-id="elm_filter_geolocation_{$filter_uid}"
                 data-ca-geocomplete-value-elem-id="elm_checkbox_address_{$filter_uid}"
                 data-ca-slider-disabled="{$filter.disable}"
                 data-ca-slider-value="{$value|default:$start_value}"
                 data-ca-slider-min="{$min}"
                 data-ca-slider-max="{$max}"
            >
                <ul class="ty-range-slider__wrapper">
                    <li class="ty-range-slider__item" style="left: 0%;">
                        <span class="ty-range-slider__num">
                            {if $language_direction != "rtl"}
                            <span>&lrm;{$range_prefix nofilter}<bdi><span>{$min} </span></bdi>{$range_suffix nofilter}</span>
                            {else}
                            <span><bdi><span>{$min} </span></bdi>&lrm;{$range_prefix nofilter}{$range_suffix nofilter}</span>
                            {/if}
                        </span>
                    </li>
                    <li class="ty-range-slider__item" style="left: 100%;">
                        <span class="ty-range-slider__num">
                            {if $language_direction != "rtl"}
                                <span>&lrm;{$range_prefix nofilter}<bdi><span>{$max} </span></bdi>{$range_suffix nofilter}</span>
                            {else}
                                <span><bdi><span>{$max} </span></bdi>&lrm;{$range_prefix nofilter}{$range_suffix nofilter}</span>
                            {/if}
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        {* Filter checkbox *}
        <input id="elm_checkbox_address_{$filter_uid}"
               data-ca-filter-id="{$filter.filter_id}"
               data-ca-filter-uid="{$filter_uid}"
               class="cm-product-filters-checkbox hidden"
               type="checkbox"
               name="product_filters[{$filter.filter_id}]"
               value="{$filter.location_hash}" {if $filter.location_hash}checked="checked"{/if}>

        <button class="hidden set-distance ty-btn">{__("choose")}</button>
    </div>
</div>
