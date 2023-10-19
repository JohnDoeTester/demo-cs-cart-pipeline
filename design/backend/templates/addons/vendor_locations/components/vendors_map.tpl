{$show_map = $show_map|default:true}
{$provider = $settings.vendor_locations.general.provider}

{if $show_map}

    {$class = ($items || ($lat && $lng) || $provider === "yandex") ? '' : 'hidden'}

    {if $lat && $lng}
        {$items = [
            [
                lat => $lat,
                lng => $lng,
                url => $url,
                company_id => $company_id,
                company => $company
            ]
        ]}
    {/if}

    {$id = $id|default:"vendors_map"}

    <div id="{$id}"
        class="vendor-locations-search-address__vendors-map cm-aom-map-container {$class}"
        data-ca-vendor-locations="vendorsMap"
        data-ca-geomap-marker-selector=".cm-vendor-map-marker-{$id}"
        data-ca-geomap-markers-container-selector=".cm-vendor-map-markers-container-{$id}"
        data-ca-geomap-max-zoom="15"
        data-ca-aom-country="{$settings.Company.company_country}"
        data-ca-aom-city="{$settings.Company.company_city}"
        data-ca-aom-address="{$settings.Company.company_address}"
    >
    </div>
    <div class="cm-vendor-map-markers-container-{$id} hidden">
        {foreach $items as $item}
            {if $item.company_id}
                {$url = {"companies.products?company_id={$item.company_id}"|fn_url|escape:javascript nofilter}}
            {/if}

            <div class="cm-vendor-map-marker-{$id}"
                data-ca-geomap-marker-lat="{$item.lat}"
                data-ca-geomap-marker-lng="{$item.lng}"
                {if $url}
                    data-ca-geomap-marker-url="{$url}"
                {/if}
                {if $item.company}
                    data-ca-geomap-marker-label="{$item.company}"
                {/if}
            >
            </div>
        {/foreach}
    </div>
{/if}
