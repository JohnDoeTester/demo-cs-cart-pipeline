{*
    $product_review_id              int                                 Product review ID
    $product_review_status          string                              Product review status
    $product_review_status_descr    array                               Product review status descr
    $return_url                     string                              Return URL
    $rev                            string                              Rev
*}

{if $product_review_status}
    {$return_url = $config.current_url|escape:"url"}

    {include file="common/select_popup.tpl"
        prefix="product_review"
        id=$product_review_id
        status=$product_review_status
        items_status=$product_review_status_descr
        object_id_name="product_review_id"
        table="product_reviews"
        st_result_ids=$rev
        btn_meta="nowrap cs-product-reviews-reviews-review-status__btn"
        extra="&return_url={$return_url}"
        ajax_full_render=true
    }

{/if}
