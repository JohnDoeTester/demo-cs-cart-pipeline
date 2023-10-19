{$discussion=$object_id|fn_get_discussion:$object_type}
{$discussion_type_list=fn_discussion_get_discussion_types()}

<select name="{$prefix}[{$object_id}][discussion_type]">
{foreach $discussion_type_list as $type => $type_name}
    <option {if $discussion.type == $type}selected="selected"{/if} value="{$type}">{$type_name}</option>
{/foreach}
</select>