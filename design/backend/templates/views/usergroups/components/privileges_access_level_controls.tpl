{$id = "`$section_id`_`$group_id`"}

<div class="privileges-access-level-controls-inline{if $hide_controls} hidden{/if}">
<label class="radio inline{if $disable_full_access_level_control} privilege-access-level-control-disabled{/if}" for="usergroup_{$usergroup_id}_privilege_{$id}_access_level_full">
    <input type="radio" name="privilege_{$id}" value="Y" class="cm-privilege-set-access-level" id="usergroup_{$usergroup_id}_privilege_{$id}_access_level_full"
           data-ca-privilege-access-level="full"
           data-ca-privilege-section-id="{$section_id}"
           data-ca-privilege-group-id="{$group_id}"
           data-ca-privilege-usergroup-id="{$usergroup_id}"
           {if $disable_full_access_level_control}disabled{/if}
    />{__("privilege.full_access")}</label>

<label class="radio inline{if $disable_view_access_level_control} privilege-access-level-control-disabled{/if}" for="usergroup_{$usergroup_id}_privilege_{$id}_access_level_view">
    <input type="radio" name="privilege_{$id}" value="Y" class="cm-privilege-set-access-level" id="usergroup_{$usergroup_id}_privilege_{$id}_access_level_view"
           data-ca-privilege-access-level="view"
           data-ca-privilege-section-id="{$section_id}"
           data-ca-privilege-group-id="{$group_id}"
           data-ca-privilege-usergroup-id="{$usergroup_id}"
           {if $disable_view_access_level_control}disabled{/if}
    />{__("privilege.view_access")}</label>

<label class="radio inline" for="usergroup_{$usergroup_id}_privilege_{$id}_access_level_none">
    <input type="radio" name="privilege_{$id}" value="Y" class="cm-privilege-set-access-level" id="usergroup_{$usergroup_id}_privilege_{$id}_access_level_none"
           data-ca-privilege-access-level="none"
           data-ca-privilege-section-id="{$section_id}"
           data-ca-privilege-group-id="{$group_id}"
           data-ca-privilege-usergroup-id="{$usergroup_id}"
    />{__("privilege.no_access")}</label>
</div>
{if $show_custom_access_level_control}
    <label class="radio inline" for="usergroup_{$usergroup_id}_privilege_{$id}_access_level_custom">
        <input type="radio" name="privilege_{$id}" value="Y" class="cm-privilege-set-access-level" id="usergroup_{$usergroup_id}_privilege_{$id}_access_level_custom"
               data-ca-privilege-access-level="custom"
               data-ca-privilege-section-id="{$section_id}"
               data-ca-privilege-group-id="{$group_id}"
               data-ca-privilege-usergroup-id="{$usergroup_id}"
        />{__("privilege.custom_access")}</label>
{/if}
