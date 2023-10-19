{script src="js/tygh/usergroup_privileges.js"}
<div class="usergroup-privileges-list" id="content_privileges_{$id}">
    {if $show_privileges_tab}
        <div class="control-group">
            <div class="control-label">{__("privilege.apply_to_all")}:</div>
            <div class="controls">
                {include file="views/usergroups/components/privileges_access_level_controls.tpl"
                    section_id='usergroup'
                    group_id='global'
                    usergroup_id=$id
                    show_custom_access_level_control=false
                }
            </div>
        </div>
        <hr/>
        <input type="hidden" name="usergroup_data[privileges]" value="" />
        {foreach $grouped_privileges as $section_id => $section}
            {include file="views/usergroups/components/privileges_section.tpl"
                usergroup_id=$id
                section_id=$section_id
                section=$section
            }
        {/foreach}
    {/if}
<!--content_privileges_{$id}--></div>