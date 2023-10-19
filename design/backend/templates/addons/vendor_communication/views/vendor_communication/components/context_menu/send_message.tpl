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
        <input type="hidden" value="{$communication_type}" data-ca-bulkedit-message-communication-type/>
        <div class="add_message_form--wrapper">
            <textarea
                    class="cm-focus add_message_form--textarea"
                    rows="5"
                    autofocus
                    placeholder="{__("vendor_communication.type_message")}"
                    data-ca-bulkedit-message-text
            ></textarea>
        </div>
    </div>

    <div class="bulk-edit-inner__footer">
        <button class="btn btn-primary bulk-edit-inner__btn bulkedit-shippings-update"
                role="button"
                data-ca-bulkedit-message-send
                data-ca-bulkedit-message-value="[data-ca-bulkedit-message-text]"
                data-ca-bulkedit-message-type="[data-ca-bulkedit-message-communication-type]"
                data-ca-bulkedit-message-target-form="[name={$params.form}]"
                data-ca-bulkedit-message-target-form-active-objects="tr.selected:has(input[type=checkbox].cm-item:checked)"
                data-ca-bulkedit-message-dispatch="vendor_communication.m_post_message"
        >{__("send")}</button>
    </div>
{/capture}

{include file="components/context_menu/items/dropdown.tpl"
    content=$content
    data=$data
    id="send_message"
}
