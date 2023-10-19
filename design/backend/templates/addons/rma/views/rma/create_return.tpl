{capture name="mainbox"}

<form action="{""|fn_url}" method="post" name="return_registration_form">
<input name="order_id" type="hidden" value="{$smarty.request.order_id}" />
<input name="user_id" type="hidden" value="{$order_info.user_id}" />

{if $actions}
<div class="table-responsive-wrapper">
    <table width="100%" class="table table--relative table-responsive table-responsive-w-titles">
    <tr>
        <td class="nowrap" data-th="&nbsp;"><span>{__("what_you_would_like_to_do")}</span>:</td>
        <td data-th="&nbsp;">&nbsp;&nbsp;</td>
        <td width="100%" data-th="&nbsp;">
            <select name="action">
            {foreach $actions as $action_id => $action}
                <option value="{$action_id}">{$action.property}</option>
            {/foreach}
            </select></td>
    </tr>
    </table>
</div>
{/if}

<div class="table-responsive-wrapper">
    <table width="100%" class="table table--relative table-responsive">
    <thead>
    <tr>
        <th width="1%">
        {include file="common/check_items.tpl"}</th>
        <th>{__("sku")}</th>
        <th width="100%">{__("product")}</th>
        <th>{__("price")}</th>
        <th>{__("amount")}</th>
        <th>{__("reason")}</th>
    </tr>
    </thead>
    {foreach $order_info.products as $oi}
    <tr>
        <td width="1%" class="left" data-th="&nbsp;">
            <input type="checkbox" name="returns[{$oi.cart_id}][chosen]" value="Y" class="cm-item" />
            <input type="hidden" name="returns[{$oi.cart_id}][product_id]" value="{$oi.product_id}" /></td>
        <td class="product-code product-code__rma" data-th="{__("sku")}"><span class="product-code__label-normal">{$oi.product_code}</span></td>
        <td data-th="{__("product")}"><a href="{"products.update?product_id=`$oi.product_id`"|fn_url}">{$oi.product nofilter}</a>
        {if $oi.product_options}<div class="options-info">&nbsp;{include file="common/options_info.tpl" product_options=$oi.product_options}</div>{/if}</td>
        <td class="nowrap" data-th="{__("price")}">
            {if $oi.extra.exclude_from_calculate}{__("free")}{else}{include file="common/price.tpl" value=$oi.price}{/if}</td>
        <td data-th="{__("amount")}">
            <input type="hidden" name="returns[{$oi.cart_id}][available_amount]" value="{$oi.amount}" />
            <select name="returns[{$oi.cart_id}][amount]">
            {section name=amount loop=$oi.amount+1 start="1" step="1"}
                    <option value="{$smarty.section.amount.index}">{$smarty.section.amount.index}</option>
            {/section}
            </select></td>
        <td data-th="{__("reason")}">
            {if $reasons}
                <select name="returns[{$oi.cart_id}][reason]">
                {foreach $reasons as $reason_id => $reason}
                    <option value="{$reason_id}">{$reason.property}</option>
                {/foreach}
                </select>
            {/if}</td>
    </tr>
    {/foreach}
    </table>
</div>

{include file="common/subheader2.tpl" title=__("comments")}
<textarea name="comment" cols="55" rows="4" class="input-textarea-long"></textarea>

<div class="buttons-container buttons-bg">
    {include file="buttons/button.tpl" but_text=__("rma.return_action") but_name="dispatch[rma.add_return]" but_meta="cm-process-items" but_role="button_main"}
</div>

</form>
{/capture}
{include file="common/mainbox.tpl" title=__("return_registration") content=$smarty.capture.mainbox}