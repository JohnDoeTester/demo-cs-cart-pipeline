{*
    $product_review_id                      int                         Product review ID
    $is_allowed_to_delete_product_reviews   bool                        Is allowed to delete product reviews
    $current_redirect_url                   string                      Current redirect IRL
*}

{if $product_review_id}
    {capture name="tools_list"}
    <li>{btn type="list" text=__("edit") href="product_reviews.update?product_review_id=`$product_review_id`"}</li>
    {if $is_allowed_to_delete_product_reviews && $auth.user_type === "UserTypes::ADMIN"|enum}
        <li>{btn type="list"
                text=__("delete")
                class="cm-confirm"
                href="product_reviews.delete?product_review_id=`$product_review_id`&redirect_url=`$current_redirect_url`"
                method="POST"
            }
        </li>
    {/if}
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
{/if}
