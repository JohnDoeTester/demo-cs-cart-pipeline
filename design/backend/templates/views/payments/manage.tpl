{script src="js/tygh/tabs.js"}

{capture name="mainbox"}

<script>
var processor_descriptions = [];
{foreach $payment_processors as $payment_category}
    {foreach $payment_category as $p}
        processor_descriptions[{$p.processor_id}] = '{$p.description|escape:javascript nofilter}';
    {/foreach}
{/foreach}
function fn_switch_processor(payment_id, processor_id)
{
    Tygh.$('#tab_conf_' + payment_id).toggleBy(processor_id == 0);
    if (processor_id != 0) {
        var url = fn_url('payments.processor?payment_id=' + payment_id + '&processor_id=' + processor_id);
        Tygh.$('#tab_conf_' + payment_id + ' a').prop('href', url);
        Tygh.$('#elm_payment_tpl_' + payment_id).prop('disabled', true);
        Tygh.$('[name="payments_form_' + payment_id + '"] [data-ca-payment="templateControlGroup"]').addClass('hidden');
        Tygh.$('#elm_payment_instructions_' + payment_id).ceEditor('destroy');
        if (processor_descriptions[processor_id]) {
            Tygh.$('#elm_processor_description_' + payment_id).html(processor_descriptions[processor_id]).show();
        } else {
            Tygh.$('#elm_processor_description_' + payment_id).hide();
        }

        Tygh.$('#elm_payment_instructions_' + payment_id).ceEditor('recover');

        Tygh.$.ceAjax('request', url, {
            result_ids: 'content_tab_details_*,content_tab_conf_*'
        });
    } else {
        Tygh.$('#elm_payment_tpl_' + payment_id).prop('disabled', false);
        Tygh.$('[name="payments_form_' + payment_id + '"] [data-ca-payment="templateControlGroup"]').removeClass('hidden');
        Tygh.$('#content_tab_conf_' + payment_id).html('<!--content_tab_conf_' + payment_id + '-->');
        Tygh.$('#elm_processor_description_' + payment_id).hide();
    }
}
</script>

{$skip_delete=false}
{$draggable = $is_allow_update_payments|default:true}
{hook name="payments:list"}
<form action="{""|fn_url}" method="post" name="manage_payments_form" id="manage_payments_form">
{include file="common/pagination.tpl" object_type="payments"}
{if $payments}
    {capture name="payments_table"}
<div class="items-container payment-methods {if $draggable}cm-sortable{/if}"
     {if $draggable}data-ca-sortable-table="payments" data-ca-sortable-id-name="payment_id"{/if}
     id="payments_list">
<div class="table-responsive-wrapper longtap-selection">
    <table class="table table-middle table--relative table-objects table-striped table-responsive table-responsive-w-titles payment-methods__list">
        <thead
                data-ca-bulkedit-default-object="true"
                data-ca-bulkedit-component="defaultObject"
        >
        <tr>
            <th>
                {if $smarty.const.ACCOUNT_TYPE !== "vendor"}
                    {include file="common/check_items.tpl"}

                    <input type="checkbox"
                           class="bulkedit-toggler hide"
                           data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                           data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                    />
                {/if}
            </th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
        </tr>
        </thead>
        <tbody>
            {foreach $payments as $pf => $payment}
                {if "ULTIMATE"|fn_allowed_for}
                    {if $runtime.company_id && $runtime.company_id != $payment.company_id}
                        {$skip_delete=true}
                        {$hide_for_vendor=true}

                    {else}
                        {$skip_delete=false}
                        {$hide_for_vendor=false}
                    {/if}
                {/if}

                {if $payment.processor_status == "D"}
                    {$status = "D"}
                    {$can_change_status = false}
                    {$display= "text"}
                {else}
                    {$status = $payment.status}
                    {$can_change_status = true}
                    {$display= ""}
                {/if}

                {capture name="tool_items"}
                    {hook name="payments:list_extra_links"}{/hook}
                {/capture}

                {capture name="extra_data"}
                    {hook name="payments:extra_data"}{/hook}
                {/capture}

                {include file="common/object_group.tpl"
                    id=$payment.payment_id
                    text=$payment.payment
                    status=$status
                    href="payments.update?payment_id=`$payment.payment_id`"
                    object_id_name="payment_id"
                    table="payments"
                    href_delete="payments.delete?payment_id=`$payment.payment_id`"
                    delete_target_id="payments_list,pagination_contents"
                    skip_delete=$skip_delete
                    header_text=$payment.payment
                    additional_class="cm-sortable-row cm-sortable-id-`$payment.payment_id`"
                    no_table=true
                    draggable=$draggable
                    can_change_status=$can_change_status
                    display=$display
                    tool_items=$smarty.capture.tool_items
                    extra_data=$smarty.capture.extra_data
                    is_bulkedit_menu=$smarty.const.ACCOUNT_TYPE !== "vendor"
                    checkbox_col_width="6%"
                    checkbox_name="payment_ids[]"
                    show_checkboxes=true
                    hidden_checkbox=true
                }
            {/foreach}
        </tbody>
    </table>
</div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="manage_payments_form"
        object="payments"
        items=$smarty.capture.payments_table
    }
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}
    <!--payments_list--></div>
{include file="common/pagination.tpl" object_type="payments"}
</form>
{/hook}
{/capture}

{capture name="buttons"}
    {capture name="tools_list"}
        {hook name="payments:manage_tools_list"}
        {/hook}
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
{/capture}

{capture name="adv_buttons"}
    {if $is_allow_update_payments}
        {capture name="add_new_picker"}
            {include file="views/payments/update.tpl"
            payment=[]
            hide_for_vendor=false
            }
        {/capture}
        {include file="common/popupbox.tpl"
            id="add_new_payments"
            text=__("new_payments")
            content=$smarty.capture.add_new_picker
            title=__("add_payment")
            act="general"
            icon="icon-plus"
        }
    {/if}
{/capture}

{include file="common/mainbox.tpl"
        title=__("payment_methods")
        content=$smarty.capture.mainbox
        select_languages=true
        buttons=$smarty.capture.buttons
        adv_buttons=$smarty.capture.adv_buttons
        select_storefront=true
        storefront_switcher_param_name="storefront_id"
        selected_storefront_id=$selected_storefront_id
}
