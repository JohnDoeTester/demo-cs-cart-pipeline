{capture name="mainbox"}

<p>{__("rma.please_confirm_decision")}</p>

{if $change_return_status}
<form action="{""|fn_url}" method="post" name="change_return_status">
<input type="hidden" name="confirmed" value="Y" />
{foreach from=$change_return_status item="value" key="field"}
<input type="hidden" name="change_return_status[{$field}]" value="{$value}" />
{/foreach}

<div>
    {assign var="status_to" value=$change_return_status.status_to}
    {assign var="status_from" value=$change_return_status.status_from}
    {__("rma.text_return_change_warning", ["[old_status]" => $status_descr.$status_from, "[new_status]" => $status_descr.$status_to])}
</div>
{if $change_return_status.recalculate_order == "Addons\\Rma\\RecalculateOperations::MANUALLY"|enum}
<div class="control-group">
    <label for="total" class="cm-required control-label">{__("rma.order_total_will_changed")}:</label>
    <div class="controls">
        <input id="total" type="text" name="change_return_status[total]" value="{$change_return_status.total}" size="5" class="input-text cm-numeric" />
    </div>
</div>
{elseif $change_return_status.recalculate_order == "Addons\\Rma\\RecalculateOperations::AUTO"|enum}

{if $shipping_info}
<div>
    {__("rma.shipping_costs_will_changed")}:
</div>
{foreach from=$shipping_info item="shipping"}
<div class="control-group">
    <label for="sh_{$shipping.shipping_id}" class="control-label cm-required">{$shipping.shipping}:</label>
    <div class="controls">
        <input id="sh_{$shipping.shipping_id}" type="text" name="change_return_status[shipping_costs][{$shipping.shipping_id}]" value="{$shipping.rate|default:0}" size="5" class="input-text cm-numeric" />
    </div>
</div>
{/foreach}
{/if}

{/if}
<p>{__("text_are_you_sure_to_proceed")}</p>

<div class="buttons-container">    
    {include file="buttons/button.tpl" but_text=__("yes") but_name="dispatch[rma.update_details]"}
    {include file="buttons/button.tpl" but_text=__("no") but_meta="cm-back-link" but_role="action"}
</div>

</form>
{/if}
{/capture}

{include file="common/mainbox.tpl" title=__("confirmation_dialog") content=$smarty.capture.mainbox}
