{if "MULTIVENDOR"|fn_allowed_for && !$runtime.company_id}
{if $addons.vendor_data_premoderation.products_prior_approval == 'custom' || $addons.vendor_data_premoderation.products_updates_approval == 'custom' || $addons.vendor_data_premoderation.vendor_profile_updates_approval == 'custom'}

{include file="common/subheader.tpl" title=__("vendor_data_premoderation") target="#collapsable_vendor_moderate"}

<div id="collapsable_vendor_moderate" class="in collapse">
    <fieldset>
    
        {if $addons.vendor_data_premoderation.products_prior_approval == 'custom'}
        <div class="control-group setting-wide">
            <label class="control-label" for="company_pre_moderation">{__("pre_moderation")}:</label>
            <div class="controls">
                <input type="hidden" name="company_data[pre_moderation]" value="N" />
                <input type="checkbox" id="company_pre_moderation" {if $company_data.pre_moderation == "Y"}checked="checked"{/if} name="company_data[pre_moderation]" value="Y">
            </div>
        </div>
        {/if}
    
        {if $addons.vendor_data_premoderation.products_updates_approval == 'custom'}
        <div class="control-group setting-wide">
            <label class="control-label" for="company_pre_moderation_edit">{__("pre_moderation_edit")}:</label>
            <div class="controls">
                <input type="hidden" name="company_data[pre_moderation_edit]" value="N" />
                <input type="checkbox" id="company_pre_moderation_edit" {if $company_data.pre_moderation_edit == "Y"}checked="checked"{/if} name="company_data[pre_moderation_edit]" value="Y">
            </div>
        </div>
        {/if}
        
        {if $addons.vendor_data_premoderation.vendor_profile_updates_approval == 'custom'}
        <div class="control-group setting-wide">
            <label class="control-label" for="company_pre_moderation_edit_vendors">{__("pre_moderation_edit_vendors")}:</label>
            <div class="controls">
                <input type="hidden" name="company_data[pre_moderation_edit_vendors]" value="N" />
                <input type="checkbox" id="company_pre_moderation_edit_vendors" {if $company_data.pre_moderation_edit_vendors == "Y"}checked="checked"{/if} name="company_data[pre_moderation_edit_vendors]" value="Y">
            </div>
        </div>
        {/if}
         
    </fieldset>
</div>
{/if}
{/if}