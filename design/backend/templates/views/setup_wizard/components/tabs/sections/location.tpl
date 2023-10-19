{if $sect.header}
    <div class="control-group">
        <h2 class="sw-block-title">{__("{$sect.header}")}</h2>
    </div>
{/if}

<div class="control-group">
    <label for="sw_vendor_location_state" class="control-label ">{__("sw.enable_vendor_location_using_google_map")}:</label>

    <div class="controls">
        <input type="hidden" name="addon_name[]" value="vendor_locations" />
        <input type="hidden" name="vendor_locations_state" value="{"YesNo::NO"|enum}" />
        {include file="common/switcher.tpl"
            checked=($addons.vendor_locations.status === "ObjectStatuses::ACTIVE"|enum)
            input_name="vendor_locations_state"
            input_value="YesNo::YES"|enum
            input_id="sw_vendor_location_state"
            input_class="cm-submit"
            input_attrs=["data-ca-dispatch" => "dispatch[setup_wizard.update_addon_status]"]
        }
        {if ($addons.vendor_locations.status === "ObjectStatuses::ACTIVE"|enum)}
            {include file="buttons/button.tpl" but_href="addons.update&addon=vendor_locations&selected_section=settings" but_text=__("sw.configure") but_role="action" but_target="_blank" but_meta="shift-left"}
        {/if}
    </div>
</div>
