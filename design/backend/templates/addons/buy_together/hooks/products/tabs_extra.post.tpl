{if "ULTIMATE"|fn_allowed_for}
    {if $runtime.company_id && !$no_hide_input_if_shared_product}
        {assign var="hide_controls" value=false}
    {else}
        {assign var="hide_controls" value=true}
    {/if}
{else}
    {assign var="hide_controls" value=false}
{/if}


{if "MULTIVENDOR"|fn_allowed_for}
    {$hide_controls=($product_data.company_id == 0 || !$runtime.company_id)}
{/if}

<div id="content_buy_together" class="cm-hide-save-button {if $selected_section !== "buy_together"}hidden{/if} {if $hide_controls}cm-hide-inputs{/if}">
    {if !$hide_controls}
        <div class="clearfix">
            <div class="pull-right">
                    {capture name="add_new_picker"}
                        <div id="add_new_chain">
                            {include file="addons/buy_together/views/buy_together/update.tpl" product_id=$product_data.product_id item=[]}
                        </div>
                    {/capture}
                    {include file="common/popupbox.tpl" id="add_new_chain" text=__("new_combination") content=$smarty.capture.add_new_picker link_text=__("add_combination") act="general"}
            </div>
        </div><br>
    {/if}

    <form action="{""|fn_url}" method="post" name="manage_buy_together_form" class="form-horizontal form-edit cm-ajax" id="manage_buy_together_form">
        <input type="hidden" name="redirect_url" value="{$config.current_url|fn_link_attach:"selected_section=buy_together"}" />
        {if $chains}
            {$context_menu_id = "context_menu_{uniqid()}"}
            {capture name="buy_together_table"}
                <div class="items-container" id="update_chains_list">
                    <div class="table-responsive-wrapper longtap-selection">
                        <table class="table table-middle table--relative table-objects table-responsive">
                            <thead
                                    data-ca-bulkedit-default-object="true"
                                    data-ca-bulkedit-component="defaultObject"
                            >
                                <tr>
                                    <th class="left" width="6%">
                                        {include file="common/check_items.tpl"
                                            elms_container="#`$context_menu_id`"
                                        }

                                        <input type="checkbox"
                                               class="bulkedit-toggler hide"
                                               data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                               data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                        />
                                    </th>
                                    <th width="28%"></th>
                                    <th width="50%"></th>
                                    <th width="10%"></th>
                                    <th width="12%"></th>
                                </tr>
                            </thead>
                            {foreach $chains as $chain}
                                {if $hide_controls}
                                    {$link_text=__("view")}
                                {else}
                                    {$link_text=__("edit")}
                                {/if}

                                {include file="common/object_group.tpl"
                                    id=$chain.chain_id
                                    id_prefix="_bt_"
                                    text=$chain.name
                                    status=$chain.status
                                    hidden=false
                                    href="buy_together.update?chain_id=`$chain.chain_id`&product_id=`$chain.product_id`"
                                    link_text=$link_text
                                    object_id_name="chain_id"
                                    table="buy_together"
                                    href_delete="buy_together.delete?chain_id=`$chain.chain_id`"
                                    delete_target_id="update_chains_list"
                                    header_text=$chain.name
                                    skip_delete=$hide_controls
                                    no_table=true
                                    hide_for_vendor=$hide_controls
                                    is_bulkedit_menu=true
                                    checkbox_col_width="6%"
                                    checkbox_name="chain_ids[]"
                                    show_checkboxes=true
                                    hidden_checkbox=true
                                    no_padding=true
                                }
                            {/foreach}
                        </table>
                    </div>
                <!--update_chains_list--></div>
            {/capture}

            {include file="common/context_menu_wrapper.tpl"
                id=$context_menu_id
                form="manage_buy_together_form"
                object="buy_together"
                items=$smarty.capture.buy_together_table
            }
        {else}
            {hook name="products:buy_together_info_how_add"}
                {if !$chains && (!$runtime.company_id || $no_hide_input_if_shared_product)}
                    <span class="buy-together-info-message">
                        {if "MULTIVENDOR"|fn_allowed_for}
                            {__("buy_together_info_message_for_mve", ["[company_id]" => $product_data.company_id, "[product_id]" => $product_data.product_id]) nofilter}
                        {elseif "ULTIMATE"|fn_allowed_for}
                            {__("buy_together_info_message", ["[storefront_id]" => $product_data.company_id, "[product_id]" => $product_data.product_id]) nofilter}
                        {/if}
                    </span>
                {/if}
            {/hook}

            <p class="no-items">{__("no_data")}</p>
        {/if}
    </form>
<!--content_buy_together--></div>