{*
    $product_reviews                array                               Product reviews
    $product_reviews_search         array                               Product reviews search
    $available_message_types        array                               Available message types
*}

{capture name="mainbox"}
    {include file="addons/product_reviews/views/product_reviews/components/manage/reviews_table.tpl"
        product_reviews=$product_reviews
        product_reviews_search=$product_reviews_search
    }
{/capture}

{capture name="sidebar"}
    {include file="common/saved_search.tpl"
        dispatch="product_reviews.manage"
        view_type="product_reviews"
    }
    {include file="addons/product_reviews/views/product_reviews/components/sidebar/sidebar_review_search.tpl"
        product_reviews=$product_reviews
        available_message_types=$available_message_types
        product_reviews_search=$product_reviews_search
    }
{/capture}

{include file="common/mainbox.tpl"
    title=__("product_reviews.title")
    content=$smarty.capture.mainbox
    sidebar=$smarty.capture.sidebar
    select_storefront=$select_storefront
    show_all_storefront="MULTIVENDOR"|fn_allowed_for
    storefront_switcher_param_name="storefront_id"
}
