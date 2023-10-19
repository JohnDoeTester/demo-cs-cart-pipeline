{include file="common/subheader.tpl" title=__("privilege_sections.`$section_id`") target="#{$section_id}_contents"}
<div id="{$section_id}_contents" class="collapse in collapse-visible">
    {$named_groups_exists = $section|count && key($section) !== ''}
    {if $named_groups_exists}
        <div class="control-group">
            <div class="controls">
                {include file="views/usergroups/components/privileges_access_level_controls.tpl"
                    section_id=$section_id
                    group_id='section_global'
                    usergroup_id=$usergroup_id
                    disable_custom_access_level_control=true
                    hide_controls=true
                }
            </div>
        </div>
    {/if}
    {foreach $section as $group_id => $group}
        {include file="views/usergroups/components/privileges_group.tpl"
            usergroup_id=$usergroup_id
            section_id=$section_id
            group_id=$group_id
            group=$group
        }
    {/foreach}
</div>
