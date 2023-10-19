{if $addons.product_reviews.status !== "ObjectStatuses::ACTIVE"|enum}
{** block-description:discussion_title_product **}

{include file="addons/discussion/views/discussion/view.tpl" object_id=$product.product_id object_type="Addons\\Discussion\\DiscussionObjectTypes::PRODUCT"|enum title=__("discussion_title_product") quicklink="disussion_link" container_id="content_discussion_block" locate_to_review_tab=true}
{/if}