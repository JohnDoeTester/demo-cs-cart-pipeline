{*
    $product_reviews_images_upload_allowed string One letter, means whether it is allower to upload images
*}

{if $product_reviews_images_upload_allowed === "YesNo::YES"|enum}

    {$max_images_upload = $config.tweaks.product_reviews.max_images_upload|default:10}

    <section class="ty-product-review-new-product-review__media" data-ca-product-review="newProductReviewMedia">
        <div class="ty-control-group">
            {__("product_reviews.add_images")}:
            <div>
                {include file="addons/product_reviews/views/product_reviews/components/new_product_review_fileuploader.tpl"
                    var_name="product_review_data[0]"
                    multiupload="Y"
                }

                <div class="ty-product-review-new-product-review__media-info hidden"
                    data-ca-product-review="newProductReviewMediaInfo"
                >
                    <small class="ty-product-review-new-product-review__media-info-text ty-muted">
                        {__("product_reviews.max_number_image_message", ['[max_image_number]' => $max_images_upload])}
                    </small>
                </div>
            </div>
        </div>
    </section>

{/if}