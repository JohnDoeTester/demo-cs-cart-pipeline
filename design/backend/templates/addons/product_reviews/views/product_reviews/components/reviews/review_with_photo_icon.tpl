{*
    $product_review_images          array                               Product review images
    $link                           string | bool                       Link
    $button                         bool                                Button
    $meta                           string                              Meta
    $scroll_to_elm                  string                              Scroll to elm
    $external_click_id              string                              External click ID
*}

{if $product_review_images}

    {$scroll_to_elm = $scroll_to_elm|default:"content_product_reviews"}
    {$external_click_id = $external_click_id|default:"reviews"}
    {if $link === true}
        {$link = "product_reviews.update?product_review_id=`$product_review_id`"}
    {/if}

    {capture name="with_photo_icon"}
        {include_ext file="common/icon.tpl"
            class="icon-picture muted"
            title=__("product_reviews.with_photo")
        }
    {/capture}

    {if $link}
        <a href="{$link|fn_url}"
            class="{$meta}"
            title="{__("product_reviews.show_review_images")}"
        >
            {$smarty.capture.with_photo_icon nofilter}
        </a>

    {elseif $button}
        <button type="button"
            class="cm-external-click {$meta}"
            data-ca-scroll="{$scroll_to_elm}"
            data-ca-external-click-id="{$external_click_id}"
            title="{__("product_reviews.scroll_to_review_images")}"
        >
            {$smarty.capture.with_photo_icon nofilter}
        </button>
    {else}
        <span class="{$meta}">
            {$smarty.capture.with_photo_icon nofilter}
        </span>
    {/if}

{/if}
