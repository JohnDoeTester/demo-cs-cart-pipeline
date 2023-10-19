{*
    $product_id
    $rate_id
    $product_reviews_ratings
*}

<section class="ty-product-review-new-product-review-rating">
    <div class="ty-control-group">
        {$rate_id = "rating_`$product_id`"}
        <label for="{$rate_id}" class="ty-control-group__title cm-required cm-multiple-radios">
            {__("product_reviews.your_rating")}
        </label>
        {include file="addons/product_reviews/views/product_reviews/components/rate.tpl"
            rate_id=$rate_id
            rate_name="product_review_data[rating_value]"
            product_reviews_ratings=$product_reviews_ratings
            size="xlarge"
        }
    </div>
</section>
