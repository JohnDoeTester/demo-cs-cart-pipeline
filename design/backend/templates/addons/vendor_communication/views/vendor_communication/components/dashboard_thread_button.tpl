{$communication_type = "Addons\\VendorCommunication\\CommunicationTypes::VENDOR_TO_ADMIN"|enum}
{$allow_manage = fn_check_permissions("vendor_communication", "create_thread", "admin", "GET", ["communication_type" => $communication_type])}
{$allow_new_thread = fn_vendor_communication_is_communication_type_active($communication_type)}

{if "MULTIVENDOR"|fn_allowed_for && $runtime.company_id && $allow_manage && $allow_new_thread}
    <div class="dashboard-card dashboard-card--contact_admin">
        <div class="dashboard-card-title">{__("vendor_communication.communication")}</div>
        <div class="dashboard-card-content">
            <div class="control-group shift-top">
                <h3>
                    {include
                        file="addons/vendor_communication/views/vendor_communication/components/new_thread_button.tpl"
                        but_role="text"
                        but_meta="btn btn-primary cm-dialog-opener cm-dialog-auto-size"
                    }
                </h3>
            </div>
        </div>
    </div>
{/if}