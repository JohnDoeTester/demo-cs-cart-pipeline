{*
    $item_id string                                Item identifier
    $item    \Tygh\ContextMenu\Items\ComponentItem Data from context_menu schema
    $data    array                                 Data from context_menu schema
    $params  array                                 Сontext menu component parameters
*}

<li class="btn bulk-edit__btn bulk-edit__btn--edit-products hidden-phone">
    <span class="bulk-edit__btn-content">
        {btn type="dialog"
            class="cm-process-items"
            text=__("edit_selected")
            target_id="content_select_fields_to_edit"
            form=$params.form
        }
    </span>
</li>
