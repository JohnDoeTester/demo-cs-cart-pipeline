{if $status_id === "Addons\VendorDataPremoderation\ProductStatuses::DISAPPROVED"|enum && !$runtime.company_id}
    <a data-ca-target-id="disapproval_reason_{$product.product_id}"
       href="#"
       class="cm-dialog-opener cm-dialog-auto-height status-link-{$status_id|lower} {if $product.status === $status_id}active{/if}"
       onclick="if (Tygh.$(this).parent().hasClass('disabled')) { Tygh.$(this).removeClass('cm-dialog-opener'); return false} else { Tygh.$(this).addClass('cm-dialog-opener'); }"
    >
        {$status_name}
    </a>
{/if}
