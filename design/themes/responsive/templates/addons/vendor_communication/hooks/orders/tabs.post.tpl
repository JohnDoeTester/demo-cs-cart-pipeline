{if $vendor_communication_order_thread}
    <div id="content_vendor_communication" data-ca-accordion-is-active-scroll-to-elm="0">
        {include file="addons/vendor_communication/views/vendor_communication/view.tpl"
            thread_id=$vendor_communication_order_thread.thread_id
            messages=$vendor_communication_order_thread.messages
            redirect_url=$config.current_url|fn_link_attach:"selected_section=vendor_communication"
            refresh_href=$config.current_url
        }
    </div>
{/if}
