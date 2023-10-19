{** block-description:vendor_locations.closest_vendors **}
<div class="cm-reload-on-geolocation-change" id="closest_vendors_{$block.snapping_id}">
    {$items = $items.companies}
    {include file="blocks/vendor_list_templates/featured_vendors.tpl"}
<!--closest_vendors_{$block.snapping_id}--></div>
