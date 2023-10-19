{$available_registration_flow_types = fn_get_schema("setup_wizard", "registration_flow")}
{$current_registration_flow_type = fn_setup_wizard_get_current_registration_flow_type()}

<div id="sw_registration_flow" class="business_model">
    <div class="control-group">
        <h2 class="sw-block-title">{__("sw.vendor_registration_flow")}</h2>
    </div>
    {foreach $available_registration_flow_types as $registration_flow_type => $registration_flow_data}
        <div class="sw-columns-block-line">
            <div class="control-group">
                <label class="control-label control-label-radio" for="radio_{$registration_flow_type}">
                    <input type="radio"
                           name="registration_flow_type"
                           id="radio_{$registration_flow_type}"
                           class="cm-submit ladda-button"
                           data-ca-target-form="setup_wizard_vendors_form_elm"
                           data-ca-dispatch="dispatch[setup_wizard.change_registration_flow]"
                           value="{$registration_flow_type}"
                           {if $current_registration_flow_type === $registration_flow_type}checked{/if}
                    />
                    {$registration_flow_data.name}
                    <p>
                        {$registration_flow_data.description}
                    </p>
                </label>
            </div>
        </div>
    {/foreach}
</div>

<div id="container_sw_vendor_profile_fields" class="control-group sw_size_2 vendor_profile_fields">
    <label class="control-label">{__("sw.set_up_vendor_profile_fields")}</label>
    <div class="controls">
        {include file="buttons/button.tpl" but_href="profile_fields.manage?profile_type=S" but_text=__("sw.configure") but_role="action" but_target="_blank"}
    </div>
</div>
