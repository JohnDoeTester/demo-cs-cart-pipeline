{if $is_user_can_manage_customer_order_thread
    && !$order_vendor_to_customer_thread
    && $order_info.user_id
}
    {$communication_type="Addons\VendorCommunication\CommunicationTypes::VENDOR_TO_CUSTOMER"|enum}
    {$return_url=$config.current_url|fn_link_attach:"selected_section=vendor_communication_`$communication_type`"}
    {include
        file="addons/vendor_communication/views/vendor_communication/components/new_thread_button.tpl"
        title=__("vendor_communication.contact_customer")
        communication_type=$communication_type
        object_type=$smarty.const.VC_OBJECT_TYPE_ORDER
        object_id=$order_info.order_id
        menu_button=true
        divider=true
        return_url=$return_url
    }
{/if}
{if $is_user_can_manage_vendor_order_thread
    && !$order_vendor_to_admin_thread
}
    {$communication_type="Addons\VendorCommunication\CommunicationTypes::VENDOR_TO_ADMIN"|enum}
    {$return_url=$config.current_url|fn_link_attach:"selected_section=vendor_communication_`$communication_type`"}
    {include
        file="addons/vendor_communication/views/vendor_communication/components/new_thread_button.tpl"
        communication_type=$communication_type
        object_type=$smarty.const.VC_OBJECT_TYPE_ORDER
        object_id=$order_info.order_id
        menu_button=true
        divider=true
        return_url=$return_url
    }
{/if}