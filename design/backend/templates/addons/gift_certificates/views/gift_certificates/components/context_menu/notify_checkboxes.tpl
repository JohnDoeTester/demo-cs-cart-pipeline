{*
    $item_id string                                Item identifier
    $item    \Tygh\ContextMenu\Items\ComponentItem Data from context_menu schema
    $data    array                                 Data from context_menu schema
    $params  array                                 Сontext menu component parameters
*}

{include file="common/notify_checkboxes.tpl"
    prefix="multiple"
    id="select"
    notify=true
    notify_customer_status=true
    notify_text=__("notify_customer")
    name_prefix="notify"
}