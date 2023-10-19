{if $show_container}
<div class="ty-discussion-post__buttons buttons-container">
{/if}

{if $locate_to_review_tab}
    {$return_current_url = ($config.current_url|fn_link_attach:"selected_section=discussion#discussion")|escape:url}
{else}
    {$return_current_url = $config.current_url|escape:url}
{/if}

{$is_product_and_post_after_purchase_enabled = $object_type == "Addons\\Discussion\\DiscussionObjectTypes::PRODUCT"|enum
    && $settings.discussion.products.product_review_after_purchase == "Y"}
{$is_company_and_post_after_purchase_enabled = $object_type == "Addons\\Discussion\\DiscussionObjectTypes::COMPANY"|enum
    && $settings.discussion.companies.company_only_buyers == "Y"}
{if !$auth.user_id
    && ($is_product_and_post_after_purchase_enabled
        || $is_company_and_post_after_purchase_enabled)
}
    {$but_id = "opener_discussion_login_form_new_post_`$obj_prefix``$obj_id`"}
    {$target_id = "new_discussion_post_login_form_popup"}

    {$but_href = fn_url("discussion.get_user_login_form?return_url=`$return_current_url`")}

    {if $style == "text"}
        <a id="{$but_id}" class="cm-dialog-opener cm-dialog-auto-size ty-discussion__review-write" data-ca-target-id="{$target_id}" rel="nofollow" title="{__("sign_in")}" href="{$but_href}">{$name}</a>
    {else}

        {include
            file="buttons/button.tpl"
            but_id=$but_id
            but_href=$but_href
            but_text=$name
            but_title=__("sign_in")
            but_role="submit"
            but_target_id=$target_id
            but_meta="cm-dialog-opener cm-dialog-auto-size ty-btn__primary"
            but_rel="nofollow"
        }
    {/if}
{else}
    {$but_id = "opener_new_post_`$obj_prefix``$obj_id`"}
    {$but_href = fn_url("discussion.get_new_post_form?object_type=`$object_type`&object_id=`$obj_id`&obj_prefix=`$obj_prefix`&post_redirect_url=`$return_current_url`")}
    {$target_id = "new_post_dialog_`$obj_prefix``$obj_id`"}

    {if $style == "text"}
        <a id="{$but_id}" class="ty-discussion__review-write cm-dialog-opener cm-dialog-auto-size" data-ca-target-id="{$target_id}" rel="nofollow" href="{$but_href}" title="{__("write_review")}">{$name}</a>
    {else}
        {include
            file="buttons/button.tpl"
            but_id=$but_id
            but_href=$but_href
            but_text=$name
            but_title=$name
            but_role="submit"
            but_target_id=$target_id
            but_meta="cm-dialog-opener cm-dialog-auto-size ty-btn__primary"
            but_rel="nofollow"
        }
    {/if}
{/if}

{if $show_container}
</div>
{/if}
