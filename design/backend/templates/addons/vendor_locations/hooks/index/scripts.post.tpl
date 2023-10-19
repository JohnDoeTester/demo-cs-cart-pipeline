{$provider = $settings.vendor_locations.general.provider}
{$api_key = $settings.vendor_locations[$provider]["`$settings.vendor_locations.general.provider`_api_key"]}

<script>
    (function (_, $) {
        _.vendor_locations = {
            provider: '{$provider|escape:"javascript"}',
            api_key: '{$api_key|escape:"javascript"}',
            language: '{$smarty.const.CART_LANGUAGE|escape:"javascript"}',
            yandex_commercial: {if $settings.vendor_locations.yandex.yandex_commercial === "YesNo::YES"|enum}true{else}false{/if},
        };
        _.tr({
            confirm: '{__("confirm")|escape:"javascript"}',
            remove: '{__("remove")|escape:"javascript"}',
            confirm_location: '{__("vendor_locations.confirm_location")|escape:"javascript"}',
            chosen_location: '{__("vendor_locations.chosen_location")|escape:"javascript"}',
        });
    })(Tygh, Tygh.$);
</script>

{if $provider === "yandex"}
    {script src="js/addons/vendor_locations/provider/yandex/index.js"}
    {script src="js/addons/vendor_locations/provider/yandex/geocode.js"}
    {script src="js/addons/vendor_locations/provider/yandex/geomap.js"}
    {script src="js/addons/vendor_locations/provider/yandex/geolocate.js"}
    {script src="js/addons/vendor_locations/provider/yandex/func.js"}
{elseif $provider === "google"}
    {script src="js/addons/vendor_locations/geocomplete.js"}
    {script src="js/addons/vendor_locations/geolocate.js"}
    {script src="js/addons/vendor_locations/geomap.js"}
    {script src="js/addons/vendor_locations/func.js"}
{/if}
