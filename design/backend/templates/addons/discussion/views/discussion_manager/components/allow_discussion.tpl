<div class="control-group {if $no_hide_input}cm-no-hide-input{/if}">
    <label class="control-label" for="discussion_type">{$title}:</label>
    <div class="controls">

        {if !$discussion}
        {assign var="discussion" value=$object_id|fn_get_discussion:$object_type}
        {/if}

        {$discussion_types_list = fn_discussion_get_discussion_types()}
        {$discussion_type = $discussion.type|default:$discussion_default_type:("Addons\\Discussion\\DiscussionTypes::TYPE_DISABLED"|enum)}

        {if "discussion.add"|fn_check_view_permissions}
            <select name="{$prefix}[discussion_type]" id="discussion_type">
            {foreach $discussion_types_list as $type => $type_name}
                <option {if $discussion_type == $type}selected="selected"{/if} value="{$type}">{$type_name}</option>
            {/foreach}
            </select>
        {else}
            <span class="shift-input">{$discussion_types_list.$discussion_type}</span>
        {/if}

    </div>
</div>