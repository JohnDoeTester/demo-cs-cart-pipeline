{*
    $addons
    $product_id
    $is_advanced
*}

{$is_advanced = ($addons.product_reviews.review_fields === "advanced")}

<section class="ty-product-review-new-product-review-message">
    <div class="ty-control-group ty-product-review-new-product-review-message__title">
        <label class="ty-control-group__title ty-product-review-new-product-review-message__title-label
            {if !$is_advanced}
                cm-required cm-trim
            {/if}
        "
            {if $is_advanced}
                for="product_review_advantages_{$product_id}"
            {else}
                for="product_review_comment_{$product_id}"
            {/if}
        >
            {__("product_reviews.write_your_review")}
        </label>
    </div>


    {if $is_advanced}

        {include file="addons/product_reviews/views/product_reviews/components/new_product_review_message_field.tpl"
            message_title=__("product_reviews.advantages")
            id="product_review_advantages_{$product_id}"
            name="product_review_data[advantages]"
        }

        {include file="addons/product_reviews/views/product_reviews/components/new_product_review_message_field.tpl"
            message_title=__("product_reviews.disadvantages")
            id="product_review_disadvantages_{$product_id}"
            name="product_review_data[disadvantages]"
        }

    {/if}

    {include file="addons/product_reviews/views/product_reviews/components/new_product_review_message_field.tpl"
        message_title=(($is_advanced) ? "{__("product_reviews.comment")} *" : false)
        id="product_review_comment_{$product_id}"
        name="product_review_data[comment]"
        required=$is_advanced
    }

</section>
