<div class="hidden" id="map_picker" title="{__("select_coordinates")}">
    {$initial_latitude = $store_location.latitude|doubleval}
    {if !$initial_latitude}
        {$initial_latitude = $smarty.const.STORE_LOCATOR_DEFAULT_LATITUDE|doubleval}
    {/if}

    {$initial_longitude = $store_location.longitude|doubleval}
    {if !$initial_longitude}
        {$initial_longitude = $smarty.const.STORE_LOCATOR_DEFAULT_LONGITUDE|doubleval}
    {/if}

    <div class="cm-geo-map-container map-canvas" id="map_picker_container" style="height: 100%;"
         data-ca-geo-map-initial-lat="{$initial_latitude}"
         data-ca-geo-map-initial-lng="{$initial_longitude}"
         data-ca-geo-map-language="{$smarty.const.CART_LANGUAGE}"
         data-ca-geo-map-marker-selector=".cm-store-locator-map-marker"
         data-ca-geo-map-controls-enable-zoom="true"
         data-ca-geo-map-controls-enable-fullscreen="true"
         data-ca-geo-map-controls-enable-layers="true"
         data-ca-geo-map-controls-enable-ruler="true"
         data-ca-geo-map-behaviors-enable-drag="true"
         data-ca-geo-map-behaviors-enable-drag-on-mobile="true"
         data-ca-geo-map-behaviors-enable-dbl-click-zoom="true"
         data-ca-geo-map-behaviors-enable-multi-touch="true"
    ></div>

    <form name="map_picker" action="" method="">
        <div class="buttons-container">
            <a class="cm-dialog-closer cm-cancel tool-link btn">{__("cancel")}</a>
            {if $allow_save}
                {include file="buttons/button.tpl" but_text=__("set") but_role="action" but_meta="btn-primary cm-dialog-closer cm-map-save-location"}
            {/if}
        </div>
    </form>

    {if $store_location.latitude && $store_location.longitude}
        <div class="cm-store-locator-map-marker hidden"
             data-ca-geo-map-marker-lat="{$store_location.latitude}"
             data-ca-geo-map-marker-lng="{$store_location.longitude}"
        ></div>
    {/if}
</div>

{script src="js/addons/store_locator/map.js"}
