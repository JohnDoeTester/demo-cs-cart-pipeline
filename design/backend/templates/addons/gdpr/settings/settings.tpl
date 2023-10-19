<div id="settings_block_gdpr">
    <input type="hidden" name="setting_id" value="{$setting_id}" />

    {$gdpr_agreement_variables_hint nofilter}
    <hr>

    <div id="content_gdpr_gdpr_general" class="settings">
        {foreach $gdpr_settings as $agreement_name => $agreement}
            <h4 class="subheader hand" data-toggle="collapse" data-target="#collapsable_addon_option_gdpr_{$agreement_name}">
                {__($agreement.description_langvar)}
            <span class="exicon-collapse"></span></h4>
            <div id="collapsable_addon_option_gdpr_{$agreement_name}" class="in collapse">
                <fieldset>
                    <div id="container_addon_option_gdpr_{$agreement_name}_enable" class="control-group setting-wide">
                        <label for="addon_option_gdpr_{$agreement_name}_enable" class="control-label">{__("enable")}:</label>

                        <div class="controls">
                            <input type="hidden" name="gdpr_settings[{$agreement_name}][enable]" value="N">
                            <input id="addon_option_gdpr_{$agreement_name}_enable" type="checkbox" name="gdpr_settings[{$agreement_name}][enable]" value="Y" {if $saved_settings.$agreement_name.enable == "Y"}checked{/if}>
                        </div>
                    </div>

                    {** Short agreement text **}
                    <div id="container_addon_option_gdpr_{$agreement_name}_short_langvar" class="control-group setting-wide">
                        <label for="addon_option_gdpr_{$agreement_name}_short_langvar" class="control-label">{__("gdpr.short_agreement")}:</label>

                        <div class="controls shift-top">
                            <a href="{"languages.translations&q=`$agreement.short_agreement_langvar`"|fn_url}" target="_blank">{__("gdpr.view_and_edit")}</a>
                        </div>
                    </div>

                    {** Full agreement text **}
                    <div id="container_addon_option_gdpr_{$agreement_name}_full_langvar" class="control-group setting-wide">
                        <label for="addon_option_gdpr_{$agreement_name}_full_langvar" class="control-label">{__("gdpr.full_agreement")}:</label>

                        <div class="controls shift-top">
                            <a href="{"languages.translations&q=`$agreement.full_agreement_langvar`"|fn_url}" target="_blank">{__("gdpr.view_and_edit")}</a>
                        </div>
                    </div>

                </fieldset>
            </div>
        {/foreach}
    </div>
</div>