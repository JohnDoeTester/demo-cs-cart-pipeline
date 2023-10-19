{*
    $quantity
    $percentage
    $count
*}

<div class="ty-product-review-product-star-line">

    <div class="ty-product-review-product-star-line__quantity">
        {__("product_reviews.n_stars", [$quantity])}
    </div>

    <progress
        class="ty-product-review-product-star-line__line"
        max="100"
        value="{$percentage}"
        title="{__("product_reviews.reviews", [$count])}"
    ></progress>


    <div class="ty-product-review-product-star-line__percentage">
        {$percentage|round}%
    </div>

</div>
