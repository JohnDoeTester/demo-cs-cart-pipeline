{$group_name = __("privilege_groups.other")}
{if $group_id}
    {$group_name = __("privilege_groups.`$group_id`")}
{/if}

{$id = "`$section_id`_`$group_id`"}
<div class="control-group">
    <div class="control-label">{$group_name}:</div>
    <div class="controls">
        {if $group_id}
            {$manage_privileges_qty = $group.action_manage|count}
            {$view_privileges_qty = $group.action_view|count}
            {$show_custom_section = $manage_privileges_qty > 1 || $view_privileges_qty > 1}
            {include file="views/usergroups/components/privileges_access_level_controls.tpl"
                section_id=$section_id
                group_id=$group_id
                disable_full_access_level_control=$manage_privileges_qty < 1
                disable_view_access_level_control=$view_privileges_qty < 1
                show_custom_access_level_control=$show_custom_section
            }
            <div class="privileges-custom-access privileges-custom-access-disabled{if !$show_custom_section} hidden{/if}"
                 id="usergroup_{$usergroup_id}_privileges_list_{$id}"
                 data-ca-privilege-section-id="{$section_id}"
                 data-ca-privilege-group-id="{$group_id}"
                 data-ca-privilege-usergroup-id="{$usergroup_id}">
                {foreach $group.action_manage as $privilege}
                    {$privilege_id = "`$id`_`$privilege.privilege`"}
                    <div>
                        <label class="checkbox inline" for="privilege_{$privilege_id}">
                            <input type="checkbox"
                                   name="usergroup_data[privileges][{$privilege.privilege}]"
                                   value="Y"
                                   id="privilege_{$privilege_id}"
                                   {if $usergroup_privileges[$privilege.privilege]}checked="checked"{/if}
                                   data-ca-privilege-access-type="manage"
                            />{$privilege.description}</label>
                    </div>
                {/foreach}
                {foreach $group.action_view as $privilege}
                    {$privilege_id = "`$id`_`$privilege.privilege`"}
                    <div>
                        <label class="checkbox inline" for="privilege_{$privilege_id}">
                            <input type="checkbox"
                                   name="usergroup_data[privileges][{$privilege.privilege}]"
                                   value="Y"
                                   id="privilege_{$privilege_id}"
                                   {if $usergroup_privileges[$privilege.privilege]}checked="checked"{/if}
                                   data-ca-privilege-access-type="view"
                            />{$privilege.description}</label>
                    </div>
                {/foreach}
            </div>
        {else}
            <div class="table-responsive-wrapper">
                <table width="100%" class="table table-middle table--relative table-group table-responsive table-responsive-w-titles">
                    <thead>
                        <tr>
                            <th width="1%" class="table-group-checkbox">
                                {include file="common/check_items.tpl" check_target="privilege-check-{$section_id}-{$usergroup_id}"}</th>
                            <th width="100%" colspan="5">{__("select_all")}</th>
                        </tr>
                    </thead>

                    {foreach $group as $privileges}
                        {split data=$privileges size=3 assign="splitted_privilege"}
                        {math equation="floor(100/x)" x=3 assign="cell_width"}

                        {foreach $splitted_privilege as $sprivilege}

                            <tr class="object-group-elements">
                                {foreach $sprivilege as $p}
                                    {if $p && $p.description}
                                        {$pr_id = $p.privilege}
                                        <td width="1%" class="table-group-checkbox">
                                            <input type="checkbox" name="usergroup_data[privileges][{$pr_id}]" value="Y" {if $usergroup_privileges.$pr_id}checked="checked"{/if} class="cm-item-privilege-check-{$section_id}-{$usergroup_id}" id="set_privileges_{$id}_{$pr_id}"/></td>
                                        <td width="{$cell_width}%"><label for="set_privileges_{$id}_{$pr_id}">{$p.description}</label></td>
                                    {else}
                                        <td colspan="2">&nbsp;</td>
                                    {/if}
                                {/foreach}
                            </tr>

                        {/foreach}
                    {/foreach}
                </table>
            </div>
        {/if}
    </div>
</div>
