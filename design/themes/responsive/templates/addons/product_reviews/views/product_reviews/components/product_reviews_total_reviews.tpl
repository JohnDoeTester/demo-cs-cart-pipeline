{*
    $total_product_reviews
    $link
    $button
    $meta
    $scroll_to_elm
    $external_click_id
    $secondary
*}

{if $total_product_reviews > 0}

    {$scroll_to_elm = $scroll_to_elm|default:"content_product_reviews"}
    {$external_click_id = $external_click_id|default:"product_reviews"}
    
    {if $secondary}
        {$meta = "ty-muted `$meta`"}
    {/if}
    {if $link === true}
        {$link = "products.view?product_id={$product.product_id}&selected_section=product_reviews#product_reviews"}
    {/if}
    
    {if $link}
        <a href="{$link|fn_url}"
            class="ty-product-review-reviews-total-reviews ty-product-review-reviews-total-reviews--link {$meta}"
        >
    {elseif $button}
        <button type="button"
            class="ty-product-review-reviews-total-reviews ty-product-review-reviews-total-reviews--button
                ty-btn-reset
                cm-external-click {$meta}
            "
            data-ca-scroll="{$scroll_to_elm}"
            data-ca-external-click-id="{$external_click_id}"
        >
    {else}
        <span class="ty-product-review-reviews-total-reviews ty-product-review-reviews-total-reviews--text {$meta}">
    {/if}

        {__("product_reviews.reviews", [$total_product_reviews])}

    {if $link}
        </a>
    {elseif $button}
        </button>
    {else}
        </span>
    {/if}

{/if}
