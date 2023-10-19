{*
    $product_id
    $rate_id
    $product_reviews_ratings
*}

<div class="ty-control-group">
    {$rate_id = "rating_`$product_id`"}
    {include file="addons/product_reviews/views/product_reviews/components/update/rate.tpl"
    rate_id=$rate_id
    rate_name="product_review_data[rating_value]"
    product_reviews_ratings=$product_reviews_ratings
    size="xlarge"
    }
</div>