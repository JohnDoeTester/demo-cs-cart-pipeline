{if !$title}
    {if $auth.user_type == "UserTypes::VENDOR"|enum}
        {$title = __("vendor_communication.contact_admin")}
    {elseif $auth.user_type == "UserTypes::ADMIN"|enum}
        {$title = __("vendor_communication.contact_vendor")}
    {/if}
{/if}

{if !$communication_type}
    {$communication_type = "Addons\\VendorCommunication\\CommunicationTypes::VENDOR_TO_ADMIN"|enum}
{/if}
{$allow_manage = fn_check_permissions("vendor_communication", "create_thread", "admin", "GET", ["communication_type" => $communication_type])}
{$allow_new_thread = fn_vendor_communication_is_communication_type_active($communication_type)}

{$but_icon = $but_icon|default:""}
{$but_text = $but_text|default:$title}
{$menu_button = $menu_button|default:false}
{$divider = $divider|default:false}

{if $but_icon}
    {$but_text = ""}
{/if}

{if $object_type}
    {$href = "vendor_communication.create_thread?object_type={$object_type}&object_id={$object_id}&communication_type={$communication_type}"}
    {$target_id = "vendor_communication_create_thread_`$communication_type`_`$object_id`"}
{else}
    {$href = "vendor_communication.create_thread?communication_type={$communication_type}"}
    {$target_id = "vendor_communication_create_thread_`$communication_type`"}
{/if}

{if $return_url}
    {$href="`$href`&return_url={$return_url|urlencode}"}
{/if}

{capture name="thread_button"}
    {if $object_type}
        {btn
            type="list"
            text=$title
            class="cm-dialog-opener cm-dialog-auto-size cm-dialog-destroy-on-close"
            href=$href
            data=[
                "data-ca-dialog-title" => $title,
                "data-ca-target-id" => $target_id
            ]
        }
    {else}
        {include
            file="buttons/button.tpl"
            but_role=$but_role
            but_href=$href
            title=$title
            but_meta=$but_meta
            but_icon=$but_icon
            but_target_id=$target_id
        }
    {/if}
{/capture}

{if $allow_manage && $allow_new_thread}
    {if $object_type && $menu_button}
        {if $divider}
            <li class="divider"></li>
        {/if}
        <li>{$smarty.capture.thread_button nofilter}</li>
    {else}
        {$smarty.capture.thread_button nofilter}
    {/if}
{/if}
