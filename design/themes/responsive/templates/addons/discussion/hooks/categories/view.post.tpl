{include
    file="addons/discussion/views/discussion/block_view.tpl"
    object_id=$category_data.category_id
    object_type="Addons\\Discussion\\DiscussionObjectTypes::CATEGORY"|enum
    title=__("discussion_title_category")
    wrap=true
}
