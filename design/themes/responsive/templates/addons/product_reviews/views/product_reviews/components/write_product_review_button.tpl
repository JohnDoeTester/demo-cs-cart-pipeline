{*
    $config
    $settings
    $locate_to_product_review_tab
    $return_current_url
    $is_product_and_post_after_purchase_enabled
    $but_meta
    $but_id
    $but_href
    $target_id
    $but_title
    $name
    $product_id
*}

{if $locate_to_product_review_tab}
    {$return_current_url = ($config.current_url|fn_link_attach:"selected_section=product_reviews")|escape:url}
{else}
    {$return_current_url = $config.current_url|escape:url}
{/if}

{if $settings.product_reviews.review_after_purchase === "YesNo::YES"|enum}
    {$is_product_and_post_after_purchase_enabled = true}
{else}
    {$is_product_and_post_after_purchase_enabled = false}
{/if}

{$but_meta = "cm-dialog-opener cm-dialog-auto-size cm-dialog-destroy-on-close ty-product-review-write-product-review-button `$but_meta`"}
{$but_id = "opener_new_post_`$product_id`"}
{$but_href = fn_url("product_reviews.get_new_post_form?product_id=`$product_id`&post_redirect_url=`$return_current_url`")}
{$target_id = "new_post_dialog_`$product_id`"}
{$but_title = $name}

{* Parameters for an unauthorized user *}
{if !$auth.user_id && $is_product_and_post_after_purchase_enabled}
    {$but_id = "opener_product_review_login_form_new_post_`$product_id`"}
    {$target_id = "new_product_review_post_login_form_popup"}
    {$but_href = fn_url("product_reviews.get_user_login_form?return_url=`$return_current_url`")}
    {$but_title = __("sign_in")}
{/if}
{* /Parameters for an unauthorized user *}

{include file="buttons/button.tpl"
    but_id=$but_id
    but_href=$but_href
    but_text=$name
    but_title=$but_title
    but_role="submit"
    but_target_id=$target_id
    but_meta=$but_meta
    but_rel="nofollow"
}
