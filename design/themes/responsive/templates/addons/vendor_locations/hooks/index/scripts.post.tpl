{$provider = $settings.vendor_locations.general.provider}
{$api_key = $settings.vendor_locations[$provider]["`$settings.vendor_locations.general.provider`_api_key"]}

<script>
    (function (_, $) {
        _.vendor_locations = {
            provider: '{$provider|escape:"javascript"}',
            api_key: '{$api_key|escape:"javascript"}',
            yandex_commercial: {if $settings.vendor_locations.yandex.yandex_commercial === "YesNo::YES"|enum}true{else}false{/if},
            language: '{$smarty.const.CART_LANGUAGE|escape:"javascript"}',
            storage_key_geolocation: '{$smarty.const.VENDOR_LOCATIONS_STORAGE_KEY_GEO_LOCATION|escape:"javascript"}',
            storage_key_locality: '{$smarty.const.VENDOR_LOCATIONS_STORAGE_KEY_LOCALITY|escape:"javascript"}',
            customer_geolocation: '{$vendor_locations_geolocation|to_json|escape:"javascript" nofilter}',
            customer_locality: '{$vendor_locations_locality|to_json|escape:"javascript" nofilter}',
        };
        _.tr({
            confirm: '{__("confirm")|escape:"javascript"}',
            remove: '{__("remove")|escape:"javascript"}',
            confirm_location: '{__("vendor_locations.confirm_location")|escape:"javascript"}',
            chosen_location: '{__("vendor_locations.chosen_location")|escape:"javascript"}',
            enter_location: '{__("vendor_locations.enter_location")|escape:"javascript"}',
            "vendor_locations.google_maps_cookie_title": '{__("vendor_locations.google_maps_cookie_title", ['skip_live_editor' => true])|escape:"javascript"}',
            "vendor_locations.google_maps_cookie_description": '{__("vendor_locations.google_maps_cookie_description", ['skip_live_editor' => true])|escape:"javascript"}',
        });
    })(Tygh, Tygh.$);
</script>

{if $provider === "yandex"}
    {script src="js/addons/vendor_locations/provider/yandex/index.js" cookie-name="yandex_maps"}
    {script src="js/addons/vendor_locations/provider/yandex/geocomplete.js" cookie-name="yandex_maps"}
    {script src="js/addons/vendor_locations/provider/yandex/geolocate.js" cookie-name="yandex_maps"}
    {script src="js/addons/vendor_locations/provider/yandex/geomap.js" cookie-name="yandex_maps"}
    {script src="js/addons/vendor_locations/provider/yandex/func.js" cookie-name="yandex_maps"}
{elseif $provider === "google"}
    {script src="js/addons/vendor_locations/geocomplete.js" cookie-name="google_maps"}
    {script src="js/addons/vendor_locations/geolocate.js" cookie-name="google_maps"}
    {script src="js/addons/vendor_locations/geomap.js" cookie-name="google_maps"}
    {script src="js/addons/vendor_locations/func.js" cookie-name="google_maps"}
{/if}
