{*
    $product_review                 array                               Product review
    $current_redirect_url           string                              Current redirect url
    $product_review_id              int                                 Product review ID
    $show_product                   bool                                Show product
    $product_review_status_descr    array                               Product review status descr
    $rev                            string                              Rev
*}

{if $product_review}
    {$current_redirect_url=$config.current_url|escape:url}
    {$product_review_id = $product_review.product_review_id}
    
    <tr class="cm-row-status-{$product_review.status|lower} cs-product-reviews-manage-review-row cm-longtap-target"
        data-ca-longtap-action="setCheckBox"
        data-ca-longtap-target="input.cm-item"
        data-ca-id="{$product_review_id}"
    >
        <td class="left table__check-items-cell">
            <input name="reviews_ids[]"
                    type="checkbox"
                    value="{$product_review_id}"
                    class="cm-item hide cm-item-status-{$product_review.status|lower}"
            />
            <input name="reviews[{$product_review_id}][product_review_id]"
                type="hidden"
                value="{$product_review_id}"
            />
            <input name="reviews[{$product_review_id}][product_id]"
                type="hidden"
                value="{$product_review.product.product_id}"
            />
        </td>

        {* Product image *}
        {if $show_product}
            <td width="10%">
                {include file="common/image.tpl"
                    image=$product_review.product.main_pair.detailed
                    image_width=$settings.Thumbnails.product_admin_mini_icon_width
                    image_height=$settings.Thumbnails.product_admin_mini_icon_height
                    href="product_reviews.update?product_review_id=`$product_review_id`"|fn_url
                }
            </td>
        {/if}

        {* Review post *}
        <td width="43%" data-th="{__("id")}
            / {__("product_reviews.rating")}
            / {__("message")}
            / {__("product")}
            / {__("customer")}"
    >
            {include file="addons/product_reviews/views/product_reviews/components/manage/post.tpl"
                product_review_id=$product_review_id
                product_review_images=$product_review.images
                rating_value=$product_review.rating_value
                product=$product_review.product
                product_review_reply=$product_review.reply
                user_data=$product_review.user_data
                show_product=$show_product
            }
        </td>

        {* Helpfulness *}
        <td width="13%" data-th="{__("product_reviews.helpfulness")}">
            {include file="addons/product_reviews/views/product_reviews/components/reviews/helpfulness.tpl"
                helpfulness=$product_review.helpfulness
                size="small"
            }
        </td>

        {* Status *}
        <td width="10%" data-th="{__("status")}">
            <div class="pull-left">
                {include file="addons/product_reviews/views/product_reviews/components/reviews/review_status.tpl"
                    product_review_status=$product_review.status
                    product_review_id=$product_review_id
                    product_review_status_descr=$product_review_status_descr
                    rev=$rev
                }
            </div>
        </td>

        {* Tools *}
        <td width="9%" class="nowrap mobile-hide">
            <div class="hidden-tools">
                {include file="addons/product_reviews/views/product_reviews/components/reviews/review_tools_list.tpl"
                    product_review_id=$product_review_id
                    is_allowed_to_delete_product_reviews=$is_allowed_to_delete_product_reviews
                    current_redirect_url=$current_redirect_url
                }
            </div>
        </td>

        {* Review date *}
        <td width="15%" data-th="{__("date")}">
            {$product_review.product_review_timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
        </td>
    </tr>
{/if}
