{** block-description:vendor_locations.vendors_map **}

<div id="vendors_map_container_{$block.snapping_id}">
    <div id="vendors_map_{$block.snapping_id}"
        class="ty-vendor-locations__vendors-map"
        data-ca-vendor-locations="vendorsMap"
        data-ca-geomap-marker-selector=".cm-vendor-map-marker-{$block.snapping_id}"
        data-ca-geomap-max-zoom="15"
    >
    </div>
    <div class="hidden">
        {foreach $items as $item}
            <div class="cm-vendor-map-marker-{$block.snapping_id}"
                data-ca-geomap-marker-lat="{$item.lat}"
                data-ca-geomap-marker-lng="{$item.lng}"
                data-ca-geomap-marker-url="{"companies.products?company_id={$item.company_id}"|fn_url|escape:javascript nofilter}"
                data-ca-geomap-marker-label="{$item.company}"
            >
            </div>
        {/foreach}
    </div>
 <!--vendors_map_container_{$block.snapping_id}--></div>