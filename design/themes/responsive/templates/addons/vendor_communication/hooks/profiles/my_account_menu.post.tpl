{$communication_type = "Addons\\VendorCommunication\\CommunicationTypes::VENDOR_TO_CUSTOMER"|enum}
{$communication_type_active = fn_vendor_communication_is_communication_type_active($communication_type)}

{if $auth.user_id && $communication_type_active}
    <li class="ty-account-info__item ty-dropdown-box__item"><a class="ty-account-info__a underlined" href="{"vendor_communication.threads"|fn_url}" rel="nofollow" >{__("vendor_communication.messages")}</a></li>
{/if}