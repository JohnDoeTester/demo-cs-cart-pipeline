{*
    New vendors in Multi-Vendor
    ---
    $id                         string          required            Company Id
    $dispatch                   string          required            Company update status dispatch
    $disapprove_data            array           required            Disapprove submit data
    $approve_data               array           required            Approve submit data

    Another vendors in Multi-Vendor
    ---
    $id                         string          required            Company unique ID
    $status                     string          required            Company status
    $items_status               string          required            Company statuses
    $return_url                 string          required            Return URL

    CS-Cart
    ---
    $company                    array           required            Company information
*}

{if "MULTIVENDOR"|fn_allowed_for && $company.status === "VendorStatuses::NEW_ACCOUNT"|enum}
    {include file="buttons/approve_disapprove.tpl"
        id=$id
        dispatch="companies.update_status"
    }
{elseif "MULTIVENDOR"|fn_allowed_for && $company.status !== "VendorStatuses::NEW_ACCOUNT"|enum}
    {include file="common/select_popup.tpl"
        id=$id
        status=$status
        items_status=$items_status
        object_id_name="company_id"
        hide_for_vendor=$runtime.company_id
        update_controller="companies"
        notify=true
        notify_text=__("notify_vendor")
        status_target_id="pagination_contents"
        extra="&return_url=`$return_url`"
        popup_additional_class="dropleft"
    }
{else}
    {include file="views/companies/components/company_status_switcher.tpl" company=$company}
{/if}