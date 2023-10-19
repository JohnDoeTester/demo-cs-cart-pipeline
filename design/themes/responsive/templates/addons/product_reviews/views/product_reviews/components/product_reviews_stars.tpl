{*
    $rating
    $integer_rating
    $is_half_rating
    $size
    $link
    $button
    $meta
    $title
    $product
    $scroll_to_elm
    $external_click_id
*}

{if $rating > 0}

    {$integer_rating = $rating|floor}
    {$accurate_rating = $rating|round:1}
    {$is_half_rating = (($rating - $integer_rating) >= 0.25 && ($rating - $integer_rating) < 0.75)}
    {$integer_rating_math = $rating|round:0}
    {$full_stars_count = ($is_half_rating) ? $integer_rating : $integer_rating_math}
    {$scroll_to_elm = $scroll_to_elm|default:"content_product_reviews"}
    {$external_click_id = $external_click_id|default:"product_reviews"}
    {$title = __("product_reviews.product_is_rated_n_out_of_five_stars", [$accurate_rating])}

    {if $link}
        {$title = "`$title`. {__("product_reviews.show_rating")}."}
    {elseif $button}
        {$title = "`$title`. {__("product_reviews.click_to_see_reviews")}."}
    {/if}
    {if $link === true}
        {$link = "products.view?product_id={$product.product_id}&selected_section=product_reviews#product_reviews"}
    {/if}

    {if $link}
        <a class="ty-product-review-reviews-stars__link {$meta}"
            href="{$link|fn_url}"
            title="{$title}"
        >
    {elseif $button}
        <button type="button"
            class="ty-product-review-reviews-stars__button ty-btn-reset cm-external-click {$meta}"
            data-ca-scroll="{$scroll_to_elm}"
            data-ca-external-click-id="{$external_click_id}"
            title="{$title}"
        >
    {/if}
        <div class="ty-product-review-reviews-stars
            {if $size === "large"}
                ty-product-review-reviews-stars--large
            {elseif $size === "xlarge"}
                ty-product-review-reviews-stars--xlarge
            {/if}
            "
            data-ca-product-review-reviews-stars-rating="{$rating|round}"
            data-ca-product-review-reviews-stars-full="{$full_stars_count}"
            data-ca-product-review-reviews-stars-is-half="{$is_half_rating}"
            {if !$link && !$button}
                title="{$title}"
            {/if}
        ></div>
    {if $link}
        </a>
    {elseif $button}
        </button>
    {/if}

{/if}
