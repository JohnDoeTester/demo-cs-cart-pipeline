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
                    data-ca-bulkedit-responsible-changer
            >
                <option value="0"> -- </option>
                {foreach $responsibles as $user_id => $name}
                    <option value="{$user_id}">{$name}</option>
                {/foreach}
            </select>
        </div>
    </div>

    <div class="bulk-edit-inner__footer">
        <button class="btn bulk-edit-inner__btn bulkedit-responsible-cancel"
                role="button"
                data-ca-bulkedit-responsible-cancel
                data-ca-bulkedit-responsible-reset-changer="[data-ca-bulkedit-responsible-changer]"
        >{__("reset")}</button>
        <button class="btn btn-primary bulk-edit-inner__btn bulkedit-responsible-update"
                role="button"
                data-ca-bulkedit-responsible-update
                data-ca-bulkedit-responsible-values="[data-ca-bulkedit-responsible-changer]"
                data-ca-bulkedit-responsible-target-form="[name={$params.form}]"
                data-ca-bulkedit-responsible-target-form-active-objects="tbody.selected:has(input[type=checkbox].cm-item:checked)"
                data-ca-bulkedit-responsible-dispatch="call_requests.m_update_responsible"
        >{__("apply")}</button>
    </div>
{/capture}

{include file="components/context_menu/items/dropdown.tpl"
    content=$content
    data=$data
    id="responsible"
}
