{*
    $user_data
    $product_id
    $auth
    $user_info
    $post_redirect_url
    $product_review_data
    $countries
*}

<section class="ty-product-review-new-product-review-customer">
    <div class="ty-product-review-new-product-review-customer__header">
        <label class="ty-product-review-new-product-review-customer__title ty-strong cm-required"
            data-ca-product-review="newProductReviewCustomerTitle"
        >
            {__("customer")}
        </label>

        {if !$auth.user_id}
            {include
                file="buttons/button.tpl"
                but_id="opener_product_review_login_form_new_post_`$product_id`"
                but_href=fn_url("product_reviews.get_user_login_form?return_url=`$post_redirect_url|escape:url`")
                but_text=__("sign_in")
                but_title=__("sign_in")
                but_role="submit"
                but_target_id="new_product_review_post_login_form_popup"
                but_meta="cm-dialog-opener cm-dialog-auto-size ty-product-review-write-product-review-button ty-btn__secondary"
                but_rel="nofollow"
            }
        {/if}
    </div>

    {include file="addons/product_reviews/views/product_reviews/components/new_product_review_customer_profile.tpl"
        product_id=$product_id
        product_review_data=$product_review_data
        countries=$countries
    }
</section>
