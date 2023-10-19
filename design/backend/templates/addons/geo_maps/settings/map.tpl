{script src="js/addons/geo_maps/map_on_addon_settings.js"}

{$name="`$section`_api_key"}
{foreach $items as $item}
    {if $item.name === $name}
        {$api_key = $item.value}
        {break}
    {/if}
{/foreach}

{__("geo_maps.settings_is_configured_correctly_notice")}

<div id="geo-map-{$section}-container" class="control-group {if !$api_key}hidden{/if}">
    <iframe src="{"geo_maps.map?provider=`$section`&api_key=`$api_key`"|fn_url}" scrolling="no" id="geo-map-iframe-{$section}" class="geo-map-iframe"></iframe>
</div>
