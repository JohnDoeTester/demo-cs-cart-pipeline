{if fn_check_view_permissions("rma.create_gift_certificate", "POST")}
    {if $return_info.status == "Addons\\Rma\\ReturnOperationStatuses::REQUESTED"|enum}
        <a data-ca-dispatch="dispatch[rma.create_gift_certificate]" class="btn cm-process-items cm-submit cm-confirm" data-ca-target-form="return_info_form">{__("create_gift_certificate")}</a>
    {else}
        {include file="buttons/button.tpl" but_text=__("create_gift_certificate") but_name="dispatch[rma.create_gift_certificate]" but_role="button_main" but_meta="cm-process-items cm-confirm"}
    {/if}
{/if}