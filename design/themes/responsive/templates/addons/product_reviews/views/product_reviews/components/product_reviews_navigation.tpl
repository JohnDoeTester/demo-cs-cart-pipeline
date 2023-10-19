{*
    $total_product_reviews
    $config
    $curl
    $product_reviews_with_images
    $product_reviews_sorting
    $product_reviews_sorting_orders
    $product_reviews_search
    $product_reviews_avail_sorting
    $product_id
*}

{if $total_product_reviews}
    {$curl=$config.current_url|fn_query_remove:"sort_by":"with_images"}

    {* TODO *}
    {$product_reviews_with_images = ($_REQUEST.with_images === "1")}
    {* /TODO *}

    <nav class="ty-product-review-reviews-navigation">

        {$curl=$config.current_url|fn_query_remove:"sort_by":"sort_order":"result_ids":"layout"}
        {include file="common/sorting.tpl"
            sorting=$product_reviews_sorting
            sorting_orders=$product_reviews_sorting_orders
            search=$product_reviews_search
            avail_sorting=$product_reviews_avail_sorting
            ajax_class="cm-ajax"
            pagination_id="pagination_contents_comments_`$product_id`"
        }

        <label class="ty-product-review-reviews-navigation__filter {if $product_reviews_with_images}ty-product-review-reviews-navigation__filter--active{/if}">
            <input id="product_review_with_images"
                type="checkbox"
                name="product_review_with_images"
                {if $product_reviews_with_images}checked="checked"{/if}
                class="cm-external-click ty-product-review-reviews-navigation__filter-checkbox
                {if $product_reviews_with_images}
                    ty-product-review-reviews-navigation__filter-checkbox--active
                {/if}
                "
                data-ca-external-click-id="product_review_with_images_link"
            >
            <a id="product_review_with_images_link"
                href="{$curl|fn_link_attach:"with_images={if $product_reviews_with_images}0{else}1{/if}&selected_section=product_reviews"|fn_url}"
                class="ty-product-review-reviews-navigation__filter-link cm-ajax"
                data-ca-target-id="pagination_contents_comments_{$product_id}"
                rel="nofollow"
            >
                {__("product_reviews.with_photo")}
            </a>
        </label>

    </nav>
{/if}
