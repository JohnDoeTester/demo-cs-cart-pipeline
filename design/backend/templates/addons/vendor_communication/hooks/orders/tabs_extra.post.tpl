{if $order_vendor_to_customer_thread}
    {include file="addons/vendor_communication/views/vendor_communication/components/thread_view.tpl"
        thread=$order_vendor_to_customer_thread
        is_user_can_manage_order_thread=$is_user_can_manage_customer_order_thread
        refresh_href=$config.current_url
    }
{/if}
{if $order_vendor_to_admin_thread}
    {include file="addons/vendor_communication/views/vendor_communication/components/thread_view.tpl"
        thread=$order_vendor_to_admin_thread
        is_user_can_manage_order_thread=$is_user_can_manage_vendor_order_thread
        refresh_href=$config.current_url
    }
{/if}
