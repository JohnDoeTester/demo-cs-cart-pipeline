{$print_pdf_order = __("print_pdf_invoice")}
{if $status_settings.appearance_type == "C" && $order_info.doc_ids[$status_settings.appearance_type]}
    {$print_pdf_order = __("print_pdf_credit_memo")}
{elseif $status_settings.appearance_type == "O"}
    {$print_pdf_order  = __("print_pdf_order_details")}
{/if}

<li>{btn type="list" text=$print_pdf_order href="orders.print_invoice?order_id=`$order_info.order_id`&pdf=1" class="cm-new-window"}</li>
<li>{btn type="list" text=__("print_pdf_packing_slip") href="orders.print_packing_slip?order_id=`$order_info.order_id`&pdf=1" class="cm-new-window"}</li>
