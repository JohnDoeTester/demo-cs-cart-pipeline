{if $discussion && $discussion.type != "D"}
    <span class="ty-discussion__rating-wrapper" id="average_rating_{$object_type}_{$object_id}">
        {assign var="rating" value="rating_`$obj_id`"}{$smarty.capture.$rating nofilter}
        {if $company_data.discussion.search.total_items}
            <a class="ty-discussion__review-a cm-external-click" data-ca-scroll="content_discussion" data-ca-external-click-id="discussion">{$company_data.discussion.search.total_items} {__("reviews", [$company_data.discussion.search.total_items])}</a>
        {/if}
        {include
            file="addons/discussion/views/discussion/components/new_post_button.tpl"
            name=__("write_review")
            obj_id=$obj_id
            style="text"
            object_type="Addons\\Discussion\\DiscussionObjectTypes::COMPANY"|enum
        }
    <!--average_rating_{$object_type}_{$object_id}--></span>
{/if}