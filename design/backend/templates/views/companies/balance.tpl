{capture name="mainbox"}

    {capture name="tabsbox"}
    {/capture}

    {include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section|default:"transactions" group_name="vendor_payouts"}

    {if $runtime.company_id}
        {assign var="hide_controls" value=true}
    {/if}

    {assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
    {include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
    {include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}
    <form action="{""|fn_url}" method="post" class="form-horizontal form-edit" name="manage_payouts_form" id="manage_payouts_form">

        {include file="common/pagination.tpl" save_current_page=true save_current_url=true}

        <input type="hidden" name="redirect_url" value="{$c_url}"/>
        {if $payouts}
            {capture name="payouts_table"}
                <div class="table-responsive-wrapper longtap-selection">
                    <table width="100%" class="table table-middle table--relative table-responsive" id="payouts_list">
                        <thead
                                data-ca-bulkedit-default-object="true"
                                data-ca-bulkedit-component="defaultObject"
                        >
                        <tr>
                            <th class="left">
                                {if !$hide_controls}
                                    {include file="common/check_items.tpl"}

                                    <input type="checkbox"
                                           class="bulkedit-toggler hide"
                                           data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                           data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                    />
                                {/if}
                            </th>
                            <th width="5%">
                                <div class="btn-expand-wrapper">
                                    <span id="on_st"
                                        alt="{__("expand_collapse_list")}"
                                        title="{__("expand_collapse_list")}"
                                        class=" hand cm-combinations-visitors btn-expand btn-expand--header">
                                        <span class="icon-caret-right"></span>
                                    </span>
                                    <span id="off_st"
                                        alt="{__("expand_collapse_list")}"
                                        title="{__("expand_collapse_list")}"
                                        class="hand hidden cm-combinations-visitors btn-expand btn-expand--header">
                                        <span class="icon-caret-down"></span>
                                    </span>
                                </div>
                            </th>
                            <th width="5%">{__("status")}</th>
                            <th>
                                <a class="cm-ajax"
                                   href="{"`$c_url`&sort_by=sort_date&sort_order=`$search.sort_order_rev`"|fn_url}"
                                   data-ca-target-id="pagination_contents">
                                    {__("date")}{if $search.sort_by === "sort_date"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}
                                </a>
                            </th>
                            <th>{__("vendor_payouts.type")}</th>
                            {if !$hide_controls}
                                <th>{__("vendor")}</th>
                            {/if}
                            {hook name="companies:balance_list_th"}{/hook}
                            <th class="center" width="5%">&nbsp;</th>
                            <th width="15%" class="right">{__("vendor_payouts.transaction_value")}</th>
                        </tr>
                        </thead>
                        {foreach name="payouts" from=$payouts item=payout}
                            <tr class="payout payout-{$payout.payout_type|lower} cm-row-status-{$payout.approval_status|lower} cm-longtap-target"
                                data-ca-longtap-action="setCheckBox"
                                data-ca-longtap-target="input.cm-item"
                                data-ca-id="{$payout.payout_id}"
                            >
                                <td class="left mobile-hide">
                                    <input type="checkbox" name="payout_ids[]" value="{$payout.payout_id}" class="cm-item cm-item-status-{$payout.approval_status|lower} hide"/>
                                </td>
                                <td class="left approval-status-{$payout.approval_status|lower}">
                                    <button type="button"
                                          name="plus_minus"
                                          id="on_payout_note_{$smarty.foreach.payouts.iteration}"
                                          alt="{__("expand_collapse_list")}"
                                          title="{__("expand_collapse_list")}"
                                          class="hand cm-combination-visitors btn-expand">
                                        <span class="icon-caret-right"></span>
                                    </button>
                                    <button type="button"
                                          name="minus_plus"
                                          id="off_payout_note_{$smarty.foreach.payouts.iteration}"
                                          alt="{__("expand_collapse_list")}"
                                          title="{__("expand_collapse_list")}"
                                          class="hand hidden cm-combination-visitors btn-expand">
                                        <span class="icon-caret-down"></span>
                                    </button>
                                </td>
                                <td class="nowrap" data-th="{__("status")}">
                                    {if $payout.payout_type == "VendorPayoutTypes::PAYOUT"|enum
                                    || $payout.payout_type == "VendorPayoutTypes::WITHDRAWAL"|enum
                                    }
                                        {include
                                            file="common/select_popup.tpl"
                                            id=$payout.payout_id
                                            status=$payout.approval_status
                                            items_status=$approval_statuses
                                            notify_vendor=true
                                            update_controller="companies.payouts"
                                            st_return_url=$config.current_url
                                            st_result_ids="balance_total,payouts_list"
                                            hide_for_vendor=$hide_controls
                                        }
                                    {/if}
                                </td>
                                <td data-th="{__("date")}">
                                    {$payout.payout_date|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                                </td>
                                <td data-th="{__("vendor_payouts.type")}">
                                    {hook name="companies:payout_type_description"}
                                    {$payout.payout_type_description|sanitize_html nofilter}
                                    {/hook}
                                </td>
                                {if !$runtime.company_id}
                                    <td data-th="{__("vendor")}">
                                        {if $payout.company_id}
                                            {$payout.company|default:__("deleted")}
                                        {else}
                                            {$settings.Company.company_name}
                                        {/if}
                                    </td>
                                {/if}
                                {hook name="companies:balance_list_tr"}{/hook}
                                <td class="center nowrap" data-th="{__("tools")}">
                                    {if !$hide_controls}
                                        <div class="hidden-tools">
                                            {capture name="tools_list"}
                                                <li>{btn type="list" class="cm-confirm" text=__("delete") href="companies.payout_delete?payout_id=`$payout.payout_id`&redirect_url={$c_url|rawurlencode}" method="POST"}</li>
                                            {/capture}
                                            {dropdown content=$smarty.capture.tools_list}
                                        </div>
                                    {/if}
                                </td>
                                <td class="right" data-th="{__("vendor_payouts.transaction_value")}">
                                    {* total balance change *}
                                    {hook name="companies:payout_amount"}
                                        {if $payout.payout_type == "VendorPayoutTypes::PAYOUT"|enum && $payout.payout_amount < 0}
                                            <small class="muted">
                                                {include file="common/price.tpl" value=$payout.display_amount}
                                            </small>
                                        {else}
                                            {include file="common/price.tpl" value=$payout.display_amount}
                                        {/if}
                                    {/hook}
                                </td>
                            </tr>
                            <tr id="payout_note_{$smarty.foreach.payouts.iteration}"
                                class="row-more {if $hide_extra_button != "Y"}hidden{/if}">
                                <td colspan="8" class="row-more-body row-more-body--not-title top row-gray">
                                    <div class="control-group">
                                        <label class="control-label"
                                               for="payout_comments_{$payout.payout_id}">
                                            {__("comment")}
                                        </label>
                                        <div class="controls">
                                            {if $runtime.company_id}
                                                <p>{if $payout.comments}{$payout.comments}{else}-{/if}</p>
                                            {else}
                                                <textarea class="span6"
                                                          rows="4"
                                                          cols="25"
                                                          name="payout_comments[{$payout.payout_id}]"
                                                          id="payout_comments_{$payout.payout_id}">{strip}
                                                    {$payout.comments}
                                                {/strip}</textarea>
                                            {/if}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        {/foreach}
                    <!--payouts_list--></table>
                </div>
            {/capture}

            {include file="common/context_menu_wrapper.tpl"
                form="manage_payouts_form"
                object="payouts"
                items=$smarty.capture.payouts_table
            }
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}

        <div class="clearfix">
            {include file="common/pagination.tpl"}
        </div>

        {if $payouts && $totals}
            {include file="views/companies/components/balance_info.tpl"}
        {/if}
    </form>
    {capture name="buttons"}
        {if !$hide_controls && $payouts}
            {include file="buttons/save.tpl" but_name="dispatch[companies.update_payout_comments]" but_role="action" but_target_form="manage_payouts_form" but_meta="cm-submit"}
        {/if}
    {/capture}

    {capture name="adv_buttons"}
        {hook name="companies:balance_adv_buttons"}
            {if $is_allow_add_payout}
                {capture name="add_new_picker"}
                    {include file="views/companies/components/balance_new_payment.tpl" c_url=$c_url}
                {/capture}
                {if $runtime.company_id}
                    {$popup_title = __("new_withdrawal")}
                    {$btn_title = __("add_withdrawal")}
                {else}
                    {$popup_title = __("new_payout")}
                    {$btn_title = __("add_payout")}
                {/if}
                {include file="common/popupbox.tpl"
                    id="add_payment"
                    text=$popup_title
                    content=$smarty.capture.add_new_picker
                    title=$btn_title
                    act="general"
                    icon="icon-plus"
                }
            {/if}
        {/hook}
    {/capture}

    {capture name="sidebar"}
        {include file="common/saved_search.tpl" dispatch="companies.balance" view_type="balance"}
        {include file="views/companies/components/balance_search_form.tpl" dispatch="companies.balance"}
    {/capture}

{/capture}
{capture name="mainbox_title"}
    {__("vendor_accounting")}
    {if $current_balance}
        {capture name="balance"}
            {include file="common/price.tpl" value=$current_balance}
        {/capture}
        <span class="f-middle">{__("vendor_payouts.current_balance", ["[balance]" => $smarty.capture.balance])}</span>
    {/if}
{/capture}
{include file="common/mainbox.tpl"
         title=$smarty.capture.mainbox_title
         content=$smarty.capture.mainbox
         buttons=$smarty.capture.buttons
         adv_buttons=$smarty.capture.adv_buttons
         sidebar=$smarty.capture.sidebar
}
