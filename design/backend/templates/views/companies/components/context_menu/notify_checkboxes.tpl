{*
    $item_id string                                Item identifier
    $item    \Tygh\ContextMenu\Items\ComponentItem Data from context_menu schema
    $data    array                                 Data from context_menu schema
    $params  array                                 Сontext menu component parameters
*}

{include file="common/notify_checkboxes.tpl"
    prefix="multiple"
    id="select"
    notify_vendor=true
    notify_vendor_status=true
    name_prefix="notify"
}
