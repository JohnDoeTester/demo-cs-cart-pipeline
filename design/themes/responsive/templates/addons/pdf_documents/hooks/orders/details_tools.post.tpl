{$print_pdf_order = __("print_pdf_invoice")}
{if $status_settings.appearance_type == "C" && $order_info.doc_ids[$status_settings.appearance_type]}
    {$print_pdf_order = __("print_pdf_credit_memo")}
{elseif $status_settings.appearance_type == "O"}
    {$print_pdf_order = __("print_pdf_order_details")}
{/if}

{include file="buttons/button.tpl"
    but_role="text"
    but_meta="orders-print__pdf ty-btn__text cm-no-ajax"
    but_text=$print_pdf_order
    but_href="orders.print_invoice?order_id={$order_info.order_id}&pdf=1"
    but_icon="ty-icon-doc-text orders-print__icon"
}
