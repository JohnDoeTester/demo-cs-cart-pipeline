{*
    $total_product_reviews          int                                 Total product reviews
    $link                           bool | string                       Link
    $button                         bool                                Button
    $meta                           string                              Meta
    $scroll_to_elm                  string                              Scroll to elm
    $external_click_id              string                              External click ID
    $product_id                     int                                 Product ID
    $total_product_reviews_text     string                              Total product reviews text
*}

{if $total_product_reviews > 0}

    {$scroll_to_elm = $scroll_to_elm|default:"content_product_reviews"}
    {$external_click_id = $external_click_id|default:"reviews"}
    {$total_product_reviews_text = "$total_product_reviews {__("product_reviews.n_reviews", [$total_product_reviews])}"}
    {if $link === true}
        {$link = "products.update?product_id=`$product_id`&selected_section=product_reviews"}
    {/if}

    {if $link}
        <a href="{$link|fn_url}"
            class="{$meta}"
            title="{__("product_reviews.show_reviews")}"
        >
            {$total_product_reviews_text nofilter}
        </a>

    {elseif $button}
        <button type="button"
            class="cm-external-click {$meta}"
            data-ca-scroll="{$scroll_to_elm}"
            data-ca-external-click-id="{$external_click_id}"
            title="{__("product_reviews.scroll_to_reviews")}"
        >
            {$total_product_reviews_text nofilter}
        </button>
    {else}
        <span class="{$meta}">
            {$total_product_reviews_text nofilter}
        </span>
    {/if}

{/if}
