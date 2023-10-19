{include file="backend:addons/geo_maps/components/scripts.tpl"}
{script src="js/addons/geo_maps/locator.js"}

<script>
    (function (_, $) {
        _.tr({
            "geo_maps.google_maps_cookie_title": '{__("geo_maps.google_maps_cookie_title", ['skip_live_editor' => true])|escape:"javascript"}',
            "geo_maps.google_maps_cookie_description": '{__("geo_maps.google_maps_cookie_description", ['skip_live_editor' => true])|escape:"javascript"}',
            "geo_maps.yandex_maps_cookie_title": '{__("geo_maps.yandex_maps_cookie_title", ['skip_live_editor' => true])|escape:"javascript"}',
            "geo_maps.yandex_maps_cookie_description": '{__("geo_maps.yandex_maps_cookie_description", ['skip_live_editor' => true])|escape:"javascript"}',
        });
    })(Tygh, Tygh.$);
</script>
