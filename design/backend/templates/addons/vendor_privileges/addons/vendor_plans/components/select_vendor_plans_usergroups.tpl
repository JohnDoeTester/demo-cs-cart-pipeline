{if $usergroup_ids !== ""}
    {$ug_ids=","|explode:$usergroup_ids}
{/if}

{$default_id = $settings.vendor_privileges.general.default_vendor_usesrgroup}

{hook name="usergroups:select_vendor_plans_usergroups"}
<input type="hidden" name="{$name}" value="0" {$input_extra nofilter}/>
{foreach $usergroups as $usergroup}
    <label class="checkbox {if !$list_mode}inline{/if}" for="{$id}_{$usergroup.usergroup_id}">

    <input type="checkbox" name="{$name}[]" id="{$id}_{$usergroup.usergroup_id}"{if $ug_ids && $usergroup.usergroup_id|in_array:$ug_ids || $default_id === $usergroup.usergroup_id} checked="checked"{/if} value="{$usergroup.usergroup_id}" {if $default_id === $usergroup.usergroup_id}disabled{/if} {$input_extra nofilter} />
    {$usergroup.usergroup}

    </label>
    {if $default_id === $usergroup.usergroup_id}
        {$settings_link = "addons.update&addon=vendor_privileges&selected_section=settings"|fn_url}
        <p class="description">{__("vendor_privileges.default_usergroup_is_assigned_to_new_vendors", ["[settings_link]" => $settings_link]) nofilter}</p>
    {/if}
{/foreach}
{/hook}
