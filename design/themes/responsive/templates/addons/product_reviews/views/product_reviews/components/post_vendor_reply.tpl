{*
    $product_review
*}

{if $product_review.reply}
    <blockquote class="ty-product-review-post-vendor-reply ty-blockquote">
        <dl class="ty-product-review-post-vendor-reply__content ty-dl">
            <dt class="ty-product-review-post-vendor-reply__title ty-dt ty-strong">
                {if $product_review.reply.reply_company}
                    {__("product_reviews.company_reply", ['[company_name]' => $product_review.reply.reply_company])}
                {else}
                    {__("product_reviews.admin_reply")}
                {/if}
            </dt>
            <dd class="ty-product-review-post-vendor-reply__body ty-dd ty-dialog-caret">
                {$product_review.reply.reply}
            </dd>
        </dl>
    </blockquote>
{/if}
