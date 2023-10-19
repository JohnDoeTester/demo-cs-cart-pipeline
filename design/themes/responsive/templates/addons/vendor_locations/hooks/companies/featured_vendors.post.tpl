{if isset($company.distance)}
    <div class="ty-grid-list__company-distance">
        <a href="{"companies.products?company_id=`$company.company_id`"|fn_url}" class="ty-company-distance">
        {if round($company.distance, 2) > 1}
            {include_ext file="common/icon.tpl" class="ty-icon-location-arrow"}&nbsp;{round($company.distance, 2)} {$addons.vendor_locations.distance_unit}</a>
        {else}
            {__("vendor_locations.nearby")}
        {/if}
    </div>
{/if}
