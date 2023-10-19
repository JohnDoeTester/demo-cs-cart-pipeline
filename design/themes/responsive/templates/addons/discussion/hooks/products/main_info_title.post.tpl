{if $product.discussion_type && $product.discussion_type != 'D' && $addons.product_reviews.status !== "ObjectStatuses::ACTIVE"|enum}
    <div class="ty-discussion__rating-wrapper" id="average_rating_product">
        {assign var="rating" value="rating_`$obj_id`"}{$smarty.capture.$rating nofilter}

        {if $product.discussion.posts}
        <a class="ty-discussion__review-a cm-external-click" data-ca-scroll="content_discussion" data-ca-external-click-id="discussion">{$product.discussion.search.total_items} {__("reviews", [$product.discussion.search.total_items])}</a>
        {/if}
        {include
            file="addons/discussion/views/discussion/components/new_post_button.tpl"
            name=__("write_review")
            obj_id=$obj_id
            obj_prefix="main_info_title_"
            style="text"
            object_type="Addons\\Discussion\\DiscussionObjectTypes::PRODUCT"|enum
            locate_to_review_tab=true
        }
    <!--average_rating_product--></div>
{/if}
