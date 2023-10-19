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
        <div class="bulk-edit-inner__input-group">
            <select class="input-medium input-hidden"
                    data-ca-bulkedit-pickup-changer
            >
                <option value="0"> -- </option>
                {foreach $destinations as $destination}
                    <option value="{$destination.destination_id}">{$destination.destination}</option>
                {/foreach}
            </select>
        </div>
    </div>

    <div class="bulk-edit-inner__footer">
        <button class="btn bulk-edit-inner__btn bulkedit-pickup-cancel"
                role="button"
                data-ca-bulkedit-pickup-cancel
                data-ca-bulkedit-pickup-reset-changer="[data-ca-bulkedit-pickup-changer]"
        >{__("reset")}</button>
        <button class="btn btn-primary bulk-edit-inner__btn bulkedit-pickup-update"
                role="button"
                data-ca-bulkedit-pickup-update
                data-ca-bulkedit-pickup-values="[data-ca-bulkedit-pickup-changer]"
                data-ca-bulkedit-pickup-target-form="[name={$params.form}]"
                data-ca-bulkedit-pickup-target-form-active-objects="tr.selected:has(input[type=checkbox].cm-item:checked)"
                data-ca-bulkedit-pickup-dispatch="store_locator.m_update_pickup"
        >{__("apply")}</button>
    </div>
{/capture}

{include file="components/context_menu/items/dropdown.tpl"
    content=$content
    data=$data
    id="pickup"
}
