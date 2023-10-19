{script src="js/addons/geo_maps/maps.js"}
{script src="js/addons/geo_maps/code.js"}
{script src="js/addons/geo_maps/locate.js"}

{$provider = $settings.geo_maps.general.provider}

{if $provider == "yandex"}
    {script src="js/addons/geo_maps/provider/yandex/index.js" cookie-name="yandex_maps"}
    {script src="js/addons/geo_maps/provider/yandex/maps.js" cookie-name="yandex_maps"}
    {script src="js/addons/geo_maps/provider/yandex/code.js" cookie-name="yandex_maps"}
    {script src="js/addons/geo_maps/provider/yandex/locate.js" cookie-name="yandex_maps"}
{elseif $provider == "google"}
    {script src="js/addons/geo_maps/provider/google/index.js" cookie-name="google_maps"}
    {script src="js/addons/geo_maps/provider/google/maps.js" cookie-name="google_maps"}
    {script src="js/addons/geo_maps/provider/google/code.js" cookie-name="google_maps"}
    {script src="js/addons/geo_maps/provider/google/locate.js" cookie-name="google_maps"}
{/if}

{script src="js/addons/geo_maps/func.js"}

{$api_key = $settings.geo_maps[$provider]["`$settings.geo_maps.general.provider`_api_key"]}

<script>
    (function (_, $) {
        _.geo_maps = {
            provider: '{$settings.geo_maps.general.provider|escape:"javascript"}',
            api_key: '{$api_key|escape:"javascript"}',
            yandex_commercial: {if $settings.geo_maps.yandex.yandex_commercial == "Y"}true{else}false{/if},
            language: "{$smarty.const.CART_LANGUAGE}",
        };

        _.tr({
            geo_maps_google_search_bar_placeholder: '{__("admin_search_field")|escape:"javascript"}',
            geo_maps_cannot_select_location: '{__("geo_maps.cannot_select_location")|escape:"javascript"}',
        });
    })(Tygh, Tygh.$);
</script>
