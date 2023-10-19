{$schema = fn_get_schema("setup_wizard", "vendor_data_premoderation")}
{$addon_settings = $addons.vendor_data_premoderation}

{if $schema.header}
    <div class="control-group">
        <h2 class="sw-block-title">{__("{$schema.header}")}</h2>
    </div>
{/if}

{foreach $schema.settings as $setting_name => $setting}
    <div id="container_sw_{$setting_name}" class="control-group sw_size_2 {$setting_name}">
        <label for="sw_{$setting_name}" class="control-label ">
            {__("sw.`$setting_name`")}:
        </label>
        <div class="controls">
            <input type="hidden" name="vendor_data_premoderation[{$setting_name}]" value="{$setting.default_value}" {if $disable_input}disabled="disabled"{/if} />
            {include file="common/switcher.tpl"
                checked=($addon_settings.$setting_name === $setting.value)
                input_name="vendor_data_premoderation[`$setting_name`]"
                input_value=$setting.value
                input_id="sw_`$setting_name`"
                input_class="cm-submit"
                input_attrs=['data-ca-target-id' => 'setup_wizard_vendors_form', "data-ca-dispatch" => "dispatch[setup_wizard.change_vendor_data_premoderation]"]
            }
            {if $setting.configure && $addon_settings.$setting_name === $setting.value}
                {include file="buttons/button.tpl"
                    but_href=$setting.configure.href
                    but_text=__("sw.configure")
                    but_role="action"
                    but_target="_blank"
                    but_meta="shift-left"
                }
            {/if}
        </div>
    </div>
{/foreach}
