{if $addons.vendor_communication.show_on_order === "YesNo::YES"|enum
    && !$vendor_communication_order_thread
}
    {include file="addons/vendor_communication/views/vendor_communication/components/new_thread_button.tpl"
        title=__("vendor_communication.start_communication")
        object_id=$order_info.order_id
        meta="ty-btn ty-btn__text"
        show_form=false
    }
{/if}
