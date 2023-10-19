{include file="common/saved_search.tpl" dispatch="vendor_communication.threads" view_type="vc_threads" view_additional_parameters="&communication_type=`$search.communication_type`"}

<div class="sidebar-row">
    <h6>{__("admin_search_title")}</h6>
    <form name="thread_search_form" action="{""|fn_url}" method="get" class="{$form_meta}">

        {if $smarty.request.redirect_url}
            <input type="hidden" name="redirect_url" value="{$smarty.request.redirect_url}" />
        {/if}

        {if $smarty.request.communication_type}
            <input type="hidden" name="communication_type" value="{$smarty.request.communication_type}" />
        {/if}

        {if $put_request_vars}
            {array_to_fields data=$smarty.request skip=["callback"] escape=["data_id"]}
        {/if}

        {capture name="simple_search"}
            {if $search.communication_type == "Addons\\VendorCommunication\\CommunicationTypes::VENDOR_TO_CUSTOMER"|enum}
                <div class="sidebar-field">
                    <label for="elm_customer">{__("vendor_communication.customer_name")}</label>
                    <div class="break">
                        <input type="text" name="customer_name" id="elm_customer" value="{$search.customer_name}" />
                    </div>
                </div>
            {/if}
            {if !$runtime.company_id}
                <div class="sidebar-field">
                    <label for="elm_company">{__("vendor")}</label>
                    <div class="break">
                        {include file="views/companies/components/picker/picker.tpl"
                            id="elm_company"
                            input_name="company_ids[]"
                            multiple=true
                            show_advanced=false
                            type="selection"
                            close_on_select=false
                            item_ids=$search.company_ids
                        }
                    </div>
                </div>
            {/if}
            {include file="common/period_selector.tpl" period=$period display="form"}
        {/capture}

        {include file="common/advanced_search.tpl"
            simple_search=$smarty.capture.simple_search
            dispatch=$dispatch
            view_type="vc_thread"
            in_popup=false
            but_permission_data="`$dispatch`?communication_type=`$search.communication_type`"
        }

    </form>
</div><hr>
