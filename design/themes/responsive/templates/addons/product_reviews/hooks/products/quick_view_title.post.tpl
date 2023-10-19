{*
    $product
*}

{include file="addons/product_reviews/views/product_reviews/components/product_rating_overview_short.tpl"
    average_rating=$product.average_rating
    total_product_reviews=$product.product_reviews_rating_stats.total
    link=true
}
