{*
    $container_id
    $wrap
    $title
    $subheader
    $product_id
    $product_reviews
    $product_reviews_search
    $locate_to_product_review_tab
*}

<div class="ty-product-reviews-view" id="{if $container_id}{$container_id}{else}content_product_reviews{/if}">
    {if $wrap == true}
        {capture name="content"}
        {include file="common/subheader.tpl" title=$title}
    {/if}

    {if $subheader}
        <h4>{$subheader}</h4>
    {/if}

    <section class="ty-product-reviews-view__main">
        <div class="ty-product-reviews-view__main-content" id="product_reviews_list_{$product_id}">

            {include file="common/pagination.tpl" id="pagination_contents_comments_`$product_id`" extra_url="&selected_section=product_reviews" search=$product_reviews_search}

            {include file="addons/product_reviews/views/product_reviews/components/product_reviews_navigation.tpl"
                total_product_reviews=$product.product_reviews_count
            }

            {if $product_reviews}
                <div class="ty-product-review-view__posts">
                    {foreach $product_reviews as $product_review}
                        {include file="addons/product_reviews/views/product_reviews/components/post.tpl"
                        product_review=$product_review
                        }
                    {/foreach}
                </div>
            {else}
                <p class="ty-no-items">{__("product_reviews.no_reviews_found")}</p>
            {/if}

            {include file="common/pagination.tpl" id="pagination_contents_comments_`$product_id`" extra_url="&selected_section=product_reviews" search=$product_reviews_search}
        <!--product_reviews_list_{$product_id}--></div>

        {include file="addons/product_reviews/views/product_reviews/components/product_reviews_sidebar.tpl"
            product=$product
            product_id=$product_id
            locate_to_product_review_tab=$locate_to_product_review_tab
            product_reviews=$product_reviews
        }

    </section>

    {if $wrap == true}
        {/capture}
        {$smarty.capture.content nofilter}
    {else}
        {capture name="mainbox_title"}{$title}{/capture}
    {/if}
</div>

{script src="js/addons/product_reviews/fallback.js"}
{script src="js/addons/product_reviews/index.js"}
