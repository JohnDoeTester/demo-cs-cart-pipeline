{if $id != 0 && ($preset.company_id != 0 || $preset.company_id == 0 && $runtime.company_id)}
    {include
        file="addons/vendor_communication/views/vendor_communication/components/new_thread_button.tpl"
        object_type=$smarty.const.VC_OBJECT_TYPE_IMPORT_PRESET
        object_id=$id
        menu_button=true
    }
{/if}