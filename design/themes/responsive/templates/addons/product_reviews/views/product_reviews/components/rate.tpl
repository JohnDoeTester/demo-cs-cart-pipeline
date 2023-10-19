{*
    $rate_id
    $rate_name
    $product_reviews_ratings
    $val
    $title
    $item_rate_id
    $size
*}

<div class="ty-product-review-rate cm-field-container">

    <div class="ty-product-review-rate__stars" id="{$rate_id}">
        {foreach $product_reviews_ratings as $val => $title}
            {$item_rate_id = "`$rate_id`_`$val`"}
            <input type="radio" id="{$item_rate_id}" class="ty-product-review-rate__stars-radio ty-visually-hidden" name="{$rate_name}" value="{$val}" />
            <label class="ty-product-review-rate__stars-label
                    {if $size === "large"}
                        ty-product-review-rate__stars-label--large
                    {elseif $size === "xlarge"}
                        ty-product-review-rate__stars-label--xlarge
                    {/if}
                    "
                for="{$item_rate_id}"
                title="{$title}"
            >
                {$title}
            </label>
        {/foreach}
    </div>
</div>