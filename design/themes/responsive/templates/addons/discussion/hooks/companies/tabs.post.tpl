<div id="content_discussion" class="{if $selected_section && $selected_section != "discussion"}hidden{/if}">
{include
    file="addons/discussion/views/discussion/view.tpl"
    object_id=$company_data.company_id
    object_type="Addons\\Discussion\\DiscussionObjectTypes::COMPANY"|enum
    wrap=true
    locate_to_review_tab=true
}
</div>