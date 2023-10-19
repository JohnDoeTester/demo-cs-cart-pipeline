{*
    $product_review                         array                       Product review
    $product_data                           array                       Product data
    $is_allowed_to_update_product_reviews   bool                        Is allowed to update product reviews
    $is_allowed_update_reply                bool                        Is allowed update reply
    $total_product_reviews                  int                         Total product reviews
    $product_review_id                      int                         Product review ID
    $available_message_types                array                       Available message types
*}

{if !$create_new_product_reviews}
    {$product_review_id = $product_review.product_review_id}
{/if}

{capture name="mainbox"}

    <form action="{""|fn_url}"
        method="POST"
        enctype="multipart/form-data"
        class="form-horizontal form-edit cs-product-reviews-update
            {if !$create_new_product_reviews && (!$is_allowed_to_update_product_reviews || $auth.user_type === "UserTypes::VENDOR"|enum)}
                cm-hide-inputs
            {/if}"
        name="update_product_reviews_form"
    >
        <input type="hidden"
            name="redirect_url"
            value="{if !$create_new_product_reviews}{$config.current_url}{else}{$post_redirect_url}{/if}"
            class="{if $is_allowed_update_reply}cm-no-hide-input{/if}"
        />
        <input type="hidden"
            name="selected_section"
            value=""
        />
        <input type="hidden"
            name="product_review_data[product_review_id]"
            value="{$product_review_id}"
            class="{if $is_allowed_update_reply}cm-no-hide-input{/if}"
        />
        {if $create_new_product_reviews}
            <input type="hidden" name="product_review_data[product_id]" value="{$product_id}"/>
        {/if}

        {include file="addons/product_reviews/views/product_reviews/components/update/review.tpl"
            product_review=$product_review
            available_message_types=$available_message_types
            is_allowed_update_reply=$is_allowed_update_reply
        }

    </form>

{/capture}

{capture name="sidebar"}
    {if !$create_new_product_reviews}
        {include file="addons/product_reviews/views/product_reviews/components/sidebar/sidebar_review_details.tpl"
            product_review=$product_review
            user_data=$product_review.user_data
            total_product_reviews=$total_product_reviews
            product_review_id=$product_review_id
        }
    {else}
        {include file="common/sidebar/sidebar_product.tpl"
            product_data=$product_data
            total_product_reviews=$total_product_reviews
        }
    {/if}
{/capture}

{capture name="buttons"}
    {if !$create_new_product_reviews && $is_allowed_update_reply}
        {include file="buttons/save.tpl"
            but_name="dispatch[product_reviews.update]"
            but_target_form="update_product_reviews_form"
            but_role="submit-link"
        }
    {elseif $create_new_product_reviews}
        {include file="buttons/save.tpl"
            but_name="dispatch[product_reviews.add]"
            but_target_form="update_product_reviews_form"
            but_role="submit-link"
        }
    {/if}
{/capture}

{include file="common/mainbox.tpl"
    title="{__("product_reviews.title")}{if !$create_new_product_reviews} #`$product_review_id`{/if}"
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
    sidebar=$smarty.capture.sidebar
}
