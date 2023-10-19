{assign var="discussion" value=$object_id|fn_get_discussion:$object_type:true:$smarty.request}
{if $object_type == "Addons\\Discussion\\DiscussionObjectTypes::ORDER"|enum}
    {$new_post_title = __("new_post")}
{else}
    {$new_post_title = __("write_review")}
{/if}
{if $discussion && $discussion.type != "Addons\\Discussion\\DiscussionTypes::TYPE_DISABLED"|enum}
    <div class="discussion-block" id="{if $container_id}{$container_id}{else}content_discussion{/if}">
        {if $wrap == true}
            {capture name="content"}
            {include file="common/subheader.tpl" title=$title}
        {/if}

        {if $subheader}
            <h4>{$subheader}</h4>
        {/if}

        <div id="posts_list_{$object_id}">
            {if $discussion.posts}
                {include file="common/pagination.tpl" id="pagination_contents_comments_`$object_id`" extra_url="&selected_section=discussion" search=$discussion.search}
                {foreach from=$discussion.posts item=post}
                    <div class="ty-discussion-post__content ty-mb-l">
                        {hook name="discussion:items_list_row"}
                        <div class="ty-discussion-post {cycle values=", ty-discussion-post_even"}" id="post_{$post.post_id}">

                            {if $discussion.type == "Addons\\Discussion\\DiscussionTypes::TYPE_COMMUNICATION"|enum
                                || $discussion.type == "Addons\\Discussion\\DiscussionTypes::TYPE_COMMUNICATION_AND_RATING"|enum
                            }
                                <div class="ty-discussion-post__message">{$post.message|escape|nl2br nofilter}</div>
                            {/if}

                            <span class="ty-caret-bottom"><span class="ty-caret-outer"></span><span class="ty-caret-inner"></span></span>

                        </div>
                        <span class="ty-discussion-post__author">{$post.name}</span>
                        <span class="ty-discussion-post__date">{$post.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</span>
                        {if $discussion.type == "Addons\\Discussion\\DiscussionTypes::TYPE_RATING"|enum
                            || $discussion.type == "Addons\\Discussion\\DiscussionTypes::TYPE_COMMUNICATION_AND_RATING"|enum
                            && $post.rating_value > 0
                        }
                            <div class="clearfix ty-discussion-post__rating">
                                {include file="addons/discussion/views/discussion/components/stars.tpl" stars=$post.rating_value|fn_get_discussion_rating}
                            </div>
                        {/if}
                        {/hook}
                    </div>
                {/foreach}

                {include file="common/pagination.tpl" id="pagination_contents_comments_`$object_id`" extra_url="&selected_section=discussion" search=$discussion.search}
            {else}
                <p class="ty-no-items">{__("no_posts_found")}</p>
            {/if}
        <!--posts_list_{$object_id}--></div>

        {if $discussion.type !== "Addons\\Discussion\\DiscussionTypes::TYPE_DISABLED"|enum}
            {include
                file="addons/discussion/views/discussion/components/new_post_button.tpl"
                name=$new_post_title
                obj_id=$object_id
                object_type=$discussion.object_type
                locate_to_review_tab=$locate_to_review_tab
            }
        {/if}

        {if $wrap == true}
            {/capture}
            {$smarty.capture.content nofilter}
        {else}
            {capture name="mainbox_title"}{$title}{/capture}
        {/if}
    </div>
{/if}