{$show_vendor_location = $show_vendor_location|default:true}
{$provider_google = $settings.vendor_locations.general.provider === 'google'}

{if $show_vendor_location && ($vendor_location || $show_vendor_location)}
    {$id = $id|default:"elm_company_location"}
    {$name = $name|default:"company_data[vendor_location]"}
    {$hide_map = $hide_map|default:false}
    {$geocomplete_type = $geocomplete_type|default:"address"}

    {if $description !== false && $provider_google}
        {$description = $description|default:__("tt_addons_vendor_locations_hooks_companies_shipping_address_post_vendor_locations.location")}
    {/if}

    <div class="control-group">
        <label for="{$id}" class="control-label {if $required}cm-required{/if}">{__("vendor_locations.location")}:</label>
        <div class="controls">
            {$place_id = null}
            {if $vendor_location}
                {$place_id=$vendor_location->getPlaceId()}
                {$lat=$vendor_location->getLat()}
                {$lng=$vendor_location->getLng()}
            {/if}

            {if $provider_google}
                <input type="text"
                    class="cm-geocomplete input-large {$class}"
                    data-ca-geocomplete-type="{$geocomplete_type}"
                    data-ca-geocomplete-place-id="{$place_id}"
                    data-ca-geocomplete-value-elem-id="{$id}_value"
                    data-ca-geocomplete-map-elem-id="{$id}_map"
                    id="{$id}"
                    {if $disabled}disabled="disabled"{/if}
                />
            {/if}

            <input type="hidden" name="{$name}" id="{$id}_value" {if $input_value_disabled}disabled="disabled"{/if} />

            {if $description}
                <p class="muted description">{$description}</p>
            {/if}

            {if !$hide_map}
                {include file = "addons/vendor_locations/components/vendors_map.tpl"
                    items=$items
                    id="`$id`_map"
                    lat=$lat
                    lng=$lng
                }
            {/if}
        </div>
    </div>
{/if}
