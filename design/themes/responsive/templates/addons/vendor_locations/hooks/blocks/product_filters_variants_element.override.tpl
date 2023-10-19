{if $filter.field_type == "\Tygh\Addons\VendorLocations\Enum\FilterTypes::REGION"|constant}
    {include file="addons/vendor_locations/blocks/product_filters/components/product_filter_location_region.tpl" filter_uid=$filter_uid filter=$filter collapse=$collapse}
    {script src="js/addons/vendor_locations/product_filters.js"}
{elseif $filter.field_type == "\Tygh\Addons\VendorLocations\Enum\FilterTypes::ZONE"|constant}
    {include file="addons/vendor_locations/blocks/product_filters/components/product_filter_location_zone.tpl" filter_uid=$filter_uid filter=$filter collapse=$collapse}
    {script src="js/addons/vendor_locations/product_filters.js"}
{/if}