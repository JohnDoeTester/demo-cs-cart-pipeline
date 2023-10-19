{if $order_info.returned_products}
<div class="table-responsive-wrapper">
    <table width="100%" class="table table--relative table-responsive">
        <tr>
            <th width="5%">{__("sku")}</th>
            <th>{__("returned_product")}</th>
            <th width="5%">{__("amount")}</th>
            <th width="7%" class="rigth">{__("subtotal")}</th>
        </tr>
        {foreach from=$order_info.returned_products item="oi"}
        <tr class="top">
            <td data-th="{__("sku")}">{$oi.product_code}</td>
            <td data-th="{__("returned_product")}">
                <a href="{"products.update?product_id=`$oi.product_id`"|fn_url}">{$oi.product}</a>
                {hook name="orders:returned_product_info"}
                {/hook}
                {if $oi.product_options}<div class="options-info">&nbsp;{include file="common/options_info.tpl" product_options=$oi.product_options}</div>{/if}
                </td>
            <td data-th="{__("amount")}">{$oi.amount}</td>
            <td class="right" data-th="{__("subtotal")}"><span>{if $oi.extra.exclude_from_calculate}{__("free")}{else}{include file="common/price.tpl" value=$oi.subtotal}{/if}</span></td>
        </tr>
        {/foreach}
    </table>
</div>
{/if}