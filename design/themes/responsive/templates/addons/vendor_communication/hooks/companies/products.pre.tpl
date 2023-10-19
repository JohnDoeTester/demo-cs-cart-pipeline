{if $addons.vendor_communication.show_on_vendor == "Y"}
    {include file="addons/vendor_communication/views/vendor_communication/components/new_thread_button.tpl" object_id=$company_id show_form=true}

    {include
        file="addons/vendor_communication/views/vendor_communication/components/new_thread_form.tpl"
        object_type=$smarty.const.VC_OBJECT_TYPE_COMPANY
        object_id=$company_id
        company_id=$company_id
        vendor_name=$company_id|fn_get_company_name:"":$smarty.const.CART_LANGUAGE:["use_i18n_fields" => true]
    }
{/if}

