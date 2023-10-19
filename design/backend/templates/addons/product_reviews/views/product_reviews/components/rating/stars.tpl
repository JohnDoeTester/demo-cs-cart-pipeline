{*
    $rating                         number                              Rating
    $integer_rating                 int                                 Integer rating
    $is_half_rating                 bool                                Is half rating
    $size                           string                              Size
    $link                           bool | string                       Link
    $button                         bool                                Button
    $meta                           string                              Meta
    $title                          string                              Title
    $product_reviews                array                               Product reviews
    $total_product_reviews          int                                 Total product reviews
    $product_data                   array                               Product data
    $scroll_to_elm                  string                              Scroll to elm
    $external_click_id              string                              External click ID
    $without_empty_stars            bool                                Without empty stars
    $flip                           bool                                Flip
*}

{if $rating > 0}
    {$integer_rating = $rating|floor}
    {$accurate_rating = $rating|round:1}
    {$is_half_rating = (($rating - $integer_rating) >= 0.25 && ($rating - $integer_rating) < 0.75)}
    {$integer_rating_math = $rating|round:0}
    {$full_stars_count = ($is_half_rating) ? $integer_rating : $integer_rating_math}
    {$scroll_to_elm = $scroll_to_elm|default:"content_product_reviews"}
    {$external_click_id = $external_click_id|default:"reviews"}
    {$title = __("product_reviews.product_is_rated_n_out_of_five_stars", [$accurate_rating])}

    {if $total_product_reviews}
        {$show_reviews_text = __("product_reviews.show_n_reviews", [$total_product_reviews])}
    {else}
        {$show_reviews_text = __("product_reviews.show_review")}
    {/if}

    {if $link === true}
        {$link = "products.update?product_id=`$product_data.product_id`&selected_section=product_reviews"}
    {/if}

    {capture name="stars"}
        <span class="cs-product-reviews-rating-stars
            {if $size === "small"}
                cs-product-reviews-rating-stars--small
            {elseif $size === "large"}
                cs-product-reviews-rating-stars--large
            {elseif $size === "xlarge"}
                cs-product-reviews-rating-stars--xlarge
            {/if}
            {if $type === "secondary"}
                cs-product-reviews-rating-stars--secondary
            {/if}
            {if $without_empty_stars}
                cs-product-reviews-rating-stars--without-empty-stars
            {/if}
            {if $flip}
                cs-product-reviews-rating-stars--flip
            {/if}
            "
            data-ca-product-review-reviews-stars-rating="{$accurate_rating}"
            data-ca-product-review-reviews-stars-full="{$full_stars_count}"
            data-ca-product-review-reviews-stars-is-half="{$is_half_rating}"
            {if !$link && !$button}
                title="{$title}"
            {/if}
        ></span>        
    {/capture}

    {if $link}
        <a class="cs-product-reviews-rating-stars__link {$meta}"
            href="{$link|fn_url}"
            title="{$title}. {$show_reviews_text nofilter}"
        >
            {$smarty.capture.stars nofilter}
        </a>
    {elseif $button}
        <button type="button"
            class="cs-product-reviews-rating-stars__button cs-btn-reset cm-external-click {$meta}"
            data-ca-scroll="{$scroll_to_elm}"
            data-ca-external-click-id="{$external_click_id}"
            title="{$title}. {__("product_reviews.scroll_to_reviews")}"
        >
            {$smarty.capture.stars nofilter}
        </button>
    {else}
        {$smarty.capture.stars nofilter}
    {/if}

{/if}
