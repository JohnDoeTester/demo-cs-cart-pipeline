{*
    $rating                         number                              Rating
    $integer_rating                 int                                 Integer rating
    $is_half_rating                 bool                                Is half rating
    $size                           string                              Size
    $link                           bool | string                       Link
    $meta                           string                              Meta
    $title                          string                              Title
    $total_reviews                  int                                 Total reviews
    $without_empty_stars            bool                                Without empty stars
    $flip                           bool                                Flip
*}

{if $rating || $rating === "0"}
    {$integer_rating = $rating|floor}
    {$accurate_rating = $rating|round:1}
    {$is_half_rating = (($rating - $integer_rating) >= 0.5)}
    
    {if $is_half_rating}
        {$title = "`$integer_rating` {__("addons.and_half_stars")}"}
    {else}
        {$title = "`$integer_rating` {__("addons.stars")}"}
    {/if}
    {if $total_reviews}
        {$show_reviews_text = __("addons.show_n_reviews", [$total_reviews])}
    {else}
        {$show_reviews_text = __("addons.show_review")}
    {/if}

    {if $link === true}
        {$link = $addon_reviews_url}
    {/if}

    {capture name="stars"}
        <div class="cs-addons-rating-stars
            {if $size === "small"}
                cs-addons-rating-stars--small
            {elseif $size === "large"}
                cs-addons-rating-stars--large
            {elseif $size === "xlarge"}
                cs-addons-rating-stars--xlarge
            {/if}
            {if $type === "secondary"}
                cs-addons-rating-stars--secondary
            {/if}
            {if $without_empty_stars}
                cs-addons-rating-stars--without-empty-stars
            {/if}
            {if $flip}
                cs-addons-rating-stars--flip
            {/if}
            "
            data-ca-addons-addons-stars-rating="{$accurate_rating}"
            data-ca-addons-addons-stars-full="{$integer_rating}"
            data-ca-addons-addons-stars-is-half="{$is_half_rating}"
            {if !$link}
                title="{$title}"
            {/if}
        ></div>        
    {/capture}

    {if $link}
        <a class="cs-addons-rating-stars__link {$meta}"
            href="{$link|fn_url}"
            title="{$title}. {$show_reviews_text nofilter}"
            target="_blank"
        >
            {$smarty.capture.stars nofilter}
        </a>
    {else}
        {$smarty.capture.stars nofilter}
    {/if}

{/if}
