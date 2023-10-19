{** block-description:vendor_locations.block_template_location_selector **}
{$location = $smarty.session.settings.customer_geolocation.value}
<div class="cm-reload-on-geolocation-change" id="location_selector_{$block.snapping_id}">
{capture name="geolocation_picker"}
    <div id="customer_geolocation_dialog">
        <form name="geolocation_form" id="form_geolocation" action="{""|fn_url}" method="post" class="cm-geolocation-form cm-ajax-full-render">
            <input name="return_url" type="hidden" value="{$smarty.request.return_url|default:$config.current_url}">
            <input name="location" id="elm_geolocation_data" type="hidden" data-ca-field-id="geolocation_data" value="">

            <div class="ty-filter-products-by-geolocation-popup__container">
                <p class="ty-filter-products-by-geolocation-popup__selected-city">{__("vendor_locations.selected_city")}:</p>
                <h3 class="ty-filter-products-by-geolocation-popup__title cm-filter-products-by-geolocation__location">
                    <bdi>{$location.locality_text|default:__("vendor_locations.customer_geolocation")}</bdi>
                </h3>
                <p class="ty-filter-products-by-geolocation-popup__not-your-city">{__("vendor_locations.not_your_city")}</p>
            </div>
            <div class="ty-control-group">
                <label class="ty-control-group__title" for="customer_geolocation">{__("vendor_locations.search_city")}</label>
                <input id="customer_current_geolocation"
                       size="50"
                       class="cm-geocomplete ty-input-text-full cm-geolocation-search-current-location"
                       type="text"
                       name="customer_geolocation"
                       value=""
                       data-ca-field-id="customer_geolocation"
                       data-ca-geocomplete-type="address"
                       data-ca-geocomplete-value-elem-id="elm_geolocation_data"
                       data-ca-geocomplete-place-id="{$location.place_id}"
                >
            </div>

            <div class="buttons-container">
                {include file="buttons/button.tpl" but_text=__("choose") but_role="text" but_meta="ty-btn__primary ty-btn__big cm-form-dialog-closer ty-btn cm-geolocation-select-current-location"}
            </div>
        </form>
    <!--customer_geolocation_dialog--></div>
{/capture}

{capture name="geolocation_label"}
    {$class = ""}
    {$locality = ""}

    {if $location}
        {$class = "location-selected"}
        {$locality = $location.locality_text}
    {/if}
    <span class="cm-geolocation-current-location ty-geolocation-current-location {$class}">{$locality|default:__("vendor_locations.customer_geolocation")}</span>
{/capture}

{include file="common/popupbox.tpl"
    href=""
    link_text=$smarty.capture.geolocation_label
    link_text_meta="ty-geo-maps__geolocation__opener-text"
    link_icon="ty-icon-location-arrow ty-filter-products-by-geolocation-popup__icon"
    link_icon_first=true
    link_meta="ty-filter-products-by-geolocation-popup__item"
    text=__("vendor_locations.select_city")
    id="customer_geolocation_dialog"
    but_name="dispatch[profiles.set_geolocation]"
    content=$smarty.capture.geolocation_picker
}
<!--location_selector_{$block.snapping_id}--></div>
