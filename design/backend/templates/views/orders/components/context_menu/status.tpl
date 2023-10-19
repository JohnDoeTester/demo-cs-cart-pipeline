{*
    $item_id string                                Item identifier
    $item    \Tygh\ContextMenu\Items\ComponentItem Data from context_menu schema
    $data    array                                 Data from context_menu schema
    $params  array                                 Сontext menu component parameters
*}

<li class="btn bulk-edit__btn bulk-edit__btn--status dropleft-mod">
    <span class="bulk-edit__btn-content dropdown-toggle" data-toggle="dropdown">{__("status")} <span class="caret mobile-hide"></span></span>

    <ul class="dropdown-menu">
        {foreach $order_status_descr as $status => $status_name}
            <li>
                <a class="cm-ajax cm-post cm-ajax-send-form"
                    href="{"orders.m_update?status={$status}"|fn_url}"
                    data-ca-target-id="pagination_contents"
                    data-ca-target-form="#orders_list_form"
                >
                    {__("change_to_status", ["[status]" => $status_name])}
                </a>
            </li>
        {/foreach}

        {include file="common/notify_checkboxes.tpl"
            prefix="multiple"
            id="select"
            notify_customer_status=true
            notify_department_status = true
            notify_vendor_status = true
            name_prefix="notify"
        }
    </ul>
</li>
