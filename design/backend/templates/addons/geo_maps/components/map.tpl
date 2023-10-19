{include file="common/scripts.tpl"}
{style src="addons/geo_maps/styles.css"}

{script src="js/addons/geo_maps/maps.js"}
{script src="js/addons/geo_maps/code.js"}
{script src="js/addons/geo_maps/locate.js"}

{if $provider === "yandex"}
    {script src="js/addons/geo_maps/provider/yandex/index.js" cookie-name="yandex_maps"}
    {script src="js/addons/geo_maps/provider/yandex/maps.js"}
    {script src="js/addons/geo_maps/provider/yandex/code.js"}
    {script src="js/addons/geo_maps/provider/yandex/locate.js"}
{elseif $provider === "google"}
    {script src="js/addons/geo_maps/provider/google/index.js" cookie-name="google_maps"}
    {script src="js/addons/geo_maps/provider/google/maps.js"}
    {script src="js/addons/geo_maps/provider/google/code.js"}
    {script src="js/addons/geo_maps/provider/google/locate.js" cookie-name="google_maps"}
{/if}

{script src="js/addons/geo_maps/func.js"}

<script>
    (function (_, $) {
        _.geo_maps = {
            provider: '{$provider|escape:"javascript"}',
            api_key: '{$api_key|escape:"javascript"}',
            yandex_commercial: {if $yandex_commercial === "YesNo::YES"|enum}true{else}false{/if},
            language: "{$smarty.const.CART_LANGUAGE}",
        };
    })(Tygh, Tygh.$);
</script>

<div class="geo-map__map-container">
    <div class="cm-geo-map-container cm-aom-map-container geo-map-iframe-container"
         data-ca-geo-map-language="{$smarty.const.CART_LANGUAGE}"
         data-ca-aom-country="{$settings.Company.company_country}"
         data-ca-aom-city="{$settings.Company.company_city}"
         data-ca-aom-address="{$settings.Company.company_address}"
         data-ca-geo-map-controls-enable-search="false"
         data-ca-geo-map-controls-enable-zoom="true"
         data-ca-geo-map-controls-enable-layers="true"
         data-ca-geo-map-behaviors-enable-drag="true"
         data-ca-geo-map-behaviors-enable-drag-on-mobile="false"
         data-ca-geo-map-behaviors-enable-smart-drag="true"
         data-ca-geo-map-behaviors-enable-dbl-click-zoom="true"
         data-ca-geo-map-behaviors-enable-multi-touch="true"
    ></div>
</div>
