{if $custom_blocks_card}{strip}
    {*
        $custom_blocks_card
        $custom_blocks_section
    *}

    {$actions_button_icon = ($custom_blocks_card.is_dismissed) ? "icon-eye-close" : "icon-ellipsis-horizontal"}
    {$is_vendor_only = ($smarty.const.ACCOUNT_TYPE === "vendor" && !$auth.act_as_user)}
    {$is_admin_act_as_vendor = ($smarty.const.ACCOUNT_TYPE === "vendor" 
        && $auth.act_as_user
        && $auth.act_as_area === "UserTypes::VENDOR"|enum
    )}

    {$show_actions = ($custom_blocks_section.is_editable
            && ($is_vendor_only || ($is_admin_act_as_vendor && !$smarty.const.BLOCK_MANAGER_MODE))
        )
        || $custom_blocks_card.is_dismissed
    }

    <div class="custom-blocks-card" id="custom_blocks_card_{$custom_blocks_card.id}">
        {if $show_actions}
            <div class="custom-blocks-card__actions">
                <form action="{""|fn_url}"
                    method="post"
                    class="custom-blocks-card__actions-form"
                    name="custom_blocks_card_actions_form"
                    enctype="multipart/form-data"
                >
                    {capture name="tools_list"}
                        <li>
                            {if $custom_blocks_card.is_dismissed}
                                <span class="dropdown--text-wrap dropdown--text-wrap-long">
                                    {__("custom_blocks.dismissed_by_vendor")}
                                </span>
                            {else}
                                {btn type="list"
                                    class="cm-post cm-ajax"
                                    text=__("dismiss")
                                    href="index.dismiss_block?block_id=`$custom_blocks_card.id`&return_url=$return_url"
                                    form="custom_blocks_card_action_form_`$custom_blocks_card.id`"
                                    data=["data-ca-target-id" => $custom_blocks_section.id]
                                }
                            {/if}
                        </li>
                    {/capture}
                    {dropdown content=$smarty.capture.tools_list
                        icon=$actions_button_icon
                        no_caret=true
                        class="custom-blocks-card__actions-btn-group"
                    }
                </form>
            </div>
        {/if}
        <div class="custom-blocks-card__inner {""}
            {if $custom_blocks_section.is_fixed}custom-blocks-card__inner--fixed{/if} {""}
            {if $custom_blocks_section.is_editable}custom-blocks-card__inner--editable{/if}
            {if !$show_actions}custom-blocks-card__inner--hide-actions{/if}"
        >
            {safe_eval_string var=$custom_blocks_card.content}
        </div>
    <!--custom_blocks_card_{$custom_blocks_card.id}--></div>
{/strip}{/if}