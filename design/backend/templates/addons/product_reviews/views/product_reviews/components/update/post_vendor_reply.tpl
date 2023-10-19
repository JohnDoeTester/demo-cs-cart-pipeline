{*
    $product_review_reply           array                               Product review reply
    $is_allowed_update_reply        bool                                Is allowed update reply
*}

<section class="cs-product-reviews-update-post-vendor-reply">
    
    <div class="control-group">
        <label class="control-label">
            {__("product_reviews.date")}:
        </label>
        <div class="controls">
            <p>
                {$product_review_reply.reply_timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
            </p>
        </div>
    </div>

    {if $product_review_reply.reply_company_id && !$runtime.company_id}
        <div class="control-group">
            <label class="control-label">
                {__("product_reviews.vendor")}:
            </label>
            <div class="controls">
                <p>
                    {include file="addons/product_reviews/views/product_reviews/components/reviews/vendor_name.tpl"
                        product_review_reply=$product_review_reply
                    }
                </p>
            </div>
        </div>
    {/if}

    <div class="control-group">
        <label class="control-label">
            {__("product_reviews.reply")}:
        </label>
        <div class="controls">
            <textarea
                id="product_review_data_reply"
                name="product_review_data[reply]"
                placeholder="{__("product_reviews.type_message")}"
                class="cs-textarea-adaptive cs-textarea-adaptive--with-sidebar input-full
                    {if $is_allowed_update_reply}
                        cm-no-hide-input
                    {/if}"
                style="--text-length: {$product_review_reply.reply|count_characters:true};"
                {if !$product_review_reply.reply_company_id && !$product_review_reply && $runtime.company_id}
                    autofocus
                {/if}
            >{$product_review_reply.reply}</textarea>
        </div>
    </div>

    {if $is_allowed_update_reply}
        <div class="control-group">
            <div class="controls">
                <p>
                    {$but_text=($product_review_reply.reply)?__("product_reviews.update_reply"):__("product_reviews.add_reply")}
                    {include file="buttons/button.tpl"
                        but_text=$but_text
                        but_role="submit-link"
                        but_name="dispatch[product_reviews.update]"
                        but_target_form="update_product_reviews_form"
                        allow_href=true
                    }
                </p>
            </div>
        </div>
    {/if}

</section>
