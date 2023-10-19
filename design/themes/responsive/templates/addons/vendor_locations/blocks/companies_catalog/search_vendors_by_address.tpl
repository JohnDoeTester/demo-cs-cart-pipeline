{** block-description:vendor_locations.search_vendors_by_address **}

{$form_name = "search_vendors_by_address_form"}

<form name="{$form_name}" action="{""|fn_url}" method="get" class="search-by-geodata">
    <input type="hidden" name="dispatch" value="{$runtime.controller}.{$runtime.mode}">
    <div class="ty-product-filters__wrapper">
        <div class="ty-product-filters__block">
            <div id="sw_content_{$filter_uid}" class="ty-product-filters__switch cm-combination-filter_{$filter_uid}{if !$collapse} open{/if} cm-save-state {if $filter.display == "Y"}cm-ss-reverse{/if}">
                <span class="ty-product-filters__title">{__("vendor_location.search_vendors_geolocation")}{if $reset_url}<a class="cm-ajax cm-ajax-full-render cm-history" href="{$reset_url|fn_url}" data-ca-event="ce.filtersinit" data-ca-target-id="{$ajax_div_ids}" data-ca-scroll=".ty-mainbox-title">{include_ext file="common/icon.tpl" class="ty-icon-cancel-circle"}</a>{/if}</span>
                {include_ext file="common/icon.tpl"
                    class="ty-icon-down-open ty-product-filters__switch-down"
                }
                {include_ext file="common/icon.tpl"
                    class="ty-icon-up-open ty-product-filters__switch-right"
                }
            </div>
            <div class="ty-product-filters {if $collapse}hidden{/if}" id="content_{$filter_uid}">
                <div class="ty-product-filters__search ty-filter-products-by-geolocation-filter-address">
                    <input type="text"
                        id="vendor_search_geolocation_{$block.snapping_id}"
                        class="cm-geocomplete ty-input-text-medium cm-filter-vendor-by-geolocation-input"
                        data-ca-field-id="{$input_field_data_id}"
                        data-ca-geocomplete-type="{$geocomplete_type|default:"(cities)"}"
                        data-ca-geocomplete-place-id="{$vendors_search_location_place_id}"
                        data-ca-geocomplete-value-elem-id="elm_vendor_search_geolocation_value_{$block.snapping_id}"
                        data-ca-filter-type="region"
                        value=""
                    >
                    <input id="elm_vendor_search_geolocation_value_{$block.snapping_id}"
                    type="hidden"
                    name="location_filter"
                    value="{$filter.location_hash}"
                    >
                    {include_ext file="common/icon.tpl"
                        class="ty-icon-cancel-circle ty-product-filters__search-icon hidden"
                        id="elm_search_clear_`$filter_uid`"
                        title=__("clear")
                    }
                    {include_ext file="common/icon.tpl"
                        class="ty-icon-target cm-filter-geolocation-use-my-location-button ty-vendors-locations-use-my-location"
                        data=[
                            "data-ca-filter-geocomplete-elem-id" => "vendor_search_geolocation_`$block.snapping_id`"
                        ]
                    }
                </div>

                <button class="hidden set-distance ty-btn">{__("choose")}</button>
            </div>
        </div>

        <div class="ty-product-filters__tools clearfix">
            <a href="{"companies.catalog"|fn_url}" rel="nofollow" class="ty-product-filters__reset-button">{include_ext file="common/icon.tpl" class="ty-icon-cw ty-product-filters__reset-icon"}{__("reset")}</a>
        </div>
    </div>
    {* Add hidden input of submit type to prevent js error *}
    <input type="submit" name="dispatch" value="companies.catalog" class="hidden">
</form>
