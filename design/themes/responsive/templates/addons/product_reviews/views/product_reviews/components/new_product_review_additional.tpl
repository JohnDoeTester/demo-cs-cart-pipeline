{*
    $product_id
*}

<section class="ty-product-review-new-product-review-additional">
    <div class="ty-control-group ty-product-review-new-product-review-additional__write-anonymously">
        <label class="ty-product-review-new-product-review-additional__write-anonymously-label">
            <input type="checkbox"
                name="product_review_data[is_anon]"
                value="{"YesNo::YES"|enum}"
                class="ty-product-review-new-product-review-additional__write-anonymously-checkbox"
                data-ca-product-review="newProductReviewAdditionalWriteAnonymouslyCheckbox"
            >
            <span class="ty-product-review-new-product-review-additional__write-anonymously-text">
                {__("product_reviews.hide_name")}
            </span>
        </label>
    </div>

    <div class="ty-control-group ty-product-review-new-product-review-additional__terms-and-conditions">
        <div class="cm-field-container ty-product-review-new-product-review-additional__terms-and-conditions-container">
            <label class="cm-required ty-product-review-new-product-review-additional__terms-and-conditions-label"
                for="product_reviews_terms_and_conditions"
            >
                <input type="checkbox"
                    id="product_reviews_terms_and_conditions"
                    name="product_review_data[terms]"
                    value="{"YesNo::YES"|enum}"
                    class="ty-product-review-new-product-review-additional__terms-and-conditions-checkbox"
                >
                <span class="ty-product-review-new-product-review-additional__terms-and-conditions-text">
                    {capture name="product_reviews_terms_link"}
                        <a id="sw_product_reviews_terms_and_conditions_{$product_id}" class="cm-combination ty-dashed-link">
                            {__("product_reviews.terms_n_conditions_name")}
                        </a>
                    {/capture}
                    {__("product_reviews.terms_n_conditions", ["[terms_href]" => $smarty.capture.product_reviews_terms_link]) nofilter}
                </span>
            </label>


            <div class="hidden" id="product_reviews_terms_and_conditions_{$product_id}">
                {__("product_reviews.terms_and_conditions_content") nofilter}
            </div>
        </div>
    </div>

    <div class="ty-control-group ty-product-review-new-product-review-additional__moderation-rules">
        <small class="ty-product-review-new-product-review-additional__moderation-rules-text ty-muted">
            {__("product_reviews.moderation_rules")}
        </small>
    </div>

</section>
