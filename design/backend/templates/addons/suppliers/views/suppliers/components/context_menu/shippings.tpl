{*
    $item_id string                                Item identifier
    $item    \Tygh\ContextMenu\Items\ComponentItem Data from context_menu schema
    $data    array                                 Data from context_menu schema
    $params  array                                 Сontext menu component parameters
*}

{capture assign="content"}
    <div class="bulk-edit-inner__header">
        <span>{__($data.name.template, $data.name.params)}</span>
    </div>

    <div class="bulk-edit-inner__body">
        {if $shippings}
            <div class="table-responsive-wrapper">
                <table width="100%" class="table table-middle table--relative table-responsive">
                    <thead>
                    <tr>
                        <th width="50%">{__("shipping_methods")}</th>
                        <th class="center">{__("available_for_supplier")}</th>
                    </tr>
                    </thead>
                    {foreach $shippings as $shipping_id => $shipping}
                        <tr>
                            <td data-th="{__("shipping_methods")}"><a href="{"shippings.update?shipping_id=`$shipping_id`"|fn_url}">{$shipping.shipping}</a></td>
                            <td class="center" data-th="{__("available_for_supplier")}">
                                <input type="checkbox" value="{$shipping_id}" data-ca-bulkedit-shippings-changer>
                            </td>
                        </tr>
                    {/foreach}
                </table>
            </div>
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}
    </div>


    <div class="bulk-edit-inner__footer">
        <button class="btn bulk-edit-inner__btn bulkedit-mod-cancel"
                role="button"
                data-ca-bulkedit-shippings-cancel
                data-ca-bulkedit-shippings-reset-changer="[data-ca-bulkedit-shippings-changer]"
        >{__("reset")}</button>
        <button class="btn btn-primary bulk-edit-inner__btn bulkedit-shippings-update"
                role="button"
                data-ca-bulkedit-shippings-update
                data-ca-bulkedit-shippings-values="[data-ca-bulkedit-shippings-changer]:checked"
                data-ca-bulkedit-shippings-target-form="[name={$params.form}]"
                data-ca-bulkedit-shippings-target-form-active-objects="tr.selected:has(input[type=checkbox].cm-item:checked)"
                data-ca-bulkedit-shippings-dispatch="suppliers.m_update_shippings"
        >{__("apply")}</button>
    </div>
{/capture}

{include file="components/context_menu/items/dropdown.tpl"
    content=$content
    data=$data
    id=$item_id
}
