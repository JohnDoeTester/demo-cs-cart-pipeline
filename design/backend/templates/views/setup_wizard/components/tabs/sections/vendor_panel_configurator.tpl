{if $sect.header}
    <div class="control-group">
        <h2 class="sw-block-title">{__("{$sect.header}")}</h2>
    </div>
{/if}

<div id="container_sw_easy_admin_panel_for_vendors" class="control-group sw_size_2">
    <label for="sw_vendor_panel_configurator_state" class="control-label ">
        {__("sw.easy_admin_panel_for_vendors")}:
    </label>
    <div class="controls">
        <input type="hidden" name="addon_name[]" value="vendor_panel_configurator" />
        <input type="hidden" name="vendor_panel_configurator_state" value="{"YesNo::NO"|enum}" />
        {include file="common/switcher.tpl"
            checked=($addons.vendor_panel_configurator.status === "ObjectStatuses::ACTIVE"|enum)
            input_name="vendor_panel_configurator_state"
            input_value="YesNo::YES"|enum
            input_id="sw_vendor_panel_configurator_state"
            input_class="cm-submit"
            input_attrs=["data-ca-dispatch" => "dispatch[setup_wizard.update_addon_status]"]
        }
    </div>
</div>

{if ($addons.vendor_panel_configurator.status === "ObjectStatuses::ACTIVE"|enum)}
    <div id="container_sw_vendor_panel_product_properties" class="control-group sw_size_2">
        <label class="control-label">{__("sw.product_tabs_and_properties")}</label>
        <div class="controls">
            {include file="buttons/button.tpl"
            but_href="addons.update?addon=vendor_panel_configurator&selected_section=settings"
            but_text=__("sw.configure")
            but_role="action"
            but_target="_blank"
            }
        </div>
    </div>

    <div id="container_sw_vendor_panel_branding" class="control-group sw_size_2">
        <label class="control-label">{__("sw.branding_and_colors")}</label>
        <div class="controls">
            {include file="buttons/button.tpl"
            but_href="addons.update?addon=vendor_panel_configurator&selected_sub_section=vendor_panel_configurator_vendor_panel_style&selected_section=settings"
            but_text=__("sw.configure")
            but_role="action"
            but_target="_blank"
            }
        </div>
    </div>
{/if}