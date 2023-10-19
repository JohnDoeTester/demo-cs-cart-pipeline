{capture name="mainbox"}
    <form action="{""|fn_url}" method="post" name="edit_order_invoice_form" class="form-horizontal form-edit">
        {hook name="orders:modify_invoice"}
        <input type="hidden" value="{$order_info.order_id}" name="order_id" />
        <div class="control-group">
            <label for="elm_subject" class="cm-required control-label">{__("subject")}:</label>
            <div class="controls">
                <input id="elm_subject" type="text" name="invoice[subject]" value="{__("email_order_invoice_subject", ["[company_name]" => $company_data.company_name, "[order_id]" => $order_info.order_id])}" class="span9">
            </div>
        </div>

        <div class="control-group">
            <label for="elm_email" class="cm-required cm-email control-label">{__("email")}:</label>
            <div class="controls">
                <input id="elm_email" type="text" name="invoice[email]" value="{$order_info.email}" class="span9">
            </div>
        </div>

        <div class="control-group">
            <label for="elm_invoice" class="cm-required control-label">{__("invoice")}:</label>
            <div class="controls">
                <textarea id="elm_invoice" name="invoice[body]" cols="55" rows="14" class="cm-wysiwyg input-textarea-long">{$invoice}</textarea>
            </div>
        </div>
        {/hook}
    </form>

{/capture}

{capture name="buttons"}
    {include file="buttons/button.tpl" but_text=__("send") but_name="dispatch[orders.modify_invoice]" but_target_form="edit_order_invoice_form" but_role="submit-link"}
{/capture}

{include file="common/mainbox.tpl"
    title_start=__('editing_order_invoice_responsive')
    title_end="#`$order_info.order_id`"
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
}
