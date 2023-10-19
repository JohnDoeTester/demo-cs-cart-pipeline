{*
    $product_review
*}

{if $product_review}
    {$date_machine_format = "%Y-%m-%dT%H:%M:%S"}

    <section class="ty-product-review-post-customer">

        <address class="ty-product-review-post-customer__address ty-address">
            {hook name="product_reviews:post_customer"}

                <div class="ty-product-review-post-customer__name">
                    {if $product_review.user_data.name}
                        {$product_review.user_data.name}
                    {else}
                        {__("anonymous")}
                    {/if}
                </div>

                {if $product_review.user_data.is_buyer === "YesNo::YES"|enum}
                    <div class="ty-product-review-post-customer__verified">
                        {__("product_reviews.verified_purchase")}
                    </div>
                {/if}

                {if $addons.product_reviews.review_ask_for_customer_location !== "none"}
                    {if $addons.product_reviews.review_ask_for_customer_location === "country"
                        && ($product_review.user_data.country_code || $product_review.user_data.country)
                    }
                        <div class="ty-product-review-post-customer__location">
                            <div class="ty-product-review-post-customer__location-flag">
                                {include_ext file="common/icon.tpl"
                                    class="ty-flag ty-flag-`$product_review.user_data.country_code|lower` ty-product-review-post-customer__location-flag-content"
                                }
                            </div>

                            <div class="ty-product-review-post-customer__location-text">
                                <div class="ty-product-review-post-customer__location-country ty-muted">{$product_review.user_data.country}</div>
                            </div>
                        </div>
                    {elseif $addons.product_reviews.review_ask_for_customer_location === "city"}
                        <div class="ty-product-review-post-customer__location">
                            <div class="ty-product-review-post-customer__location-text">
                                <div class="ty-product-review-post-customer__location-city ty-muted">{$product_review.user_data.city}</div>
                            </div>
                        </div>
                    {/if}

                {/if}

            {/hook}
        </address>

        {if $product_review.product_review_timestamp}
            <time class="ty-product-review-post-customer__date" datetime="{$product_review.product_review_timestamp|date_format:$date_machine_format}">
                {$product_review.product_review_timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
            </time>
        {/if}

    </section>
{/if}
