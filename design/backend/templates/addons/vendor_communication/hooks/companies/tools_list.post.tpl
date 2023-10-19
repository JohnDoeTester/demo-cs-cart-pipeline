{if $auth.user_type == "UserTypes::ADMIN"|enum}
    {$divider = true}
{else}
    {$divider = false}
{/if}

{include
    file="addons/vendor_communication/views/vendor_communication/components/new_thread_button.tpl"
    object_type=$smarty.const.VC_OBJECT_TYPE_COMPANY
    object_id=$id
    menu_button=true
    divider=$divider
}