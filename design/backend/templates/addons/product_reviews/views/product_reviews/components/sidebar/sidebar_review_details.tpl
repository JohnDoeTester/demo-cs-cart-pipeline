{*
    $product_review                 array                               Product review
    $product_review_id              int                                 Product review ID
    $product_review_status_descr    array                               Product review status descr
    $total_product_reviews          int                                 Total product reviews
    $user_data                      array                               User data
*}

<div class="sidebar-row">
    <h6>{__("product_reviews.review_status")}</h6>
    {include file="addons/product_reviews/views/product_reviews/components/reviews/review_status.tpl"
        product_review_status=$product_review.status
        product_review_id=$product_review_id
        product_review_status_descr=$product_review_status_descr
    }
</div>

{include file="common/sidebar/sidebar_product.tpl"
    product_data=$product_review.product
    total_product_reviews=$total_product_reviews
}

{include file="common/sidebar/sidebar_customer.tpl"
    user_data=$user_data
    user_default_name=__("anonymous")
}
