{if $addons.vendor_communication.show_on_product == "Y" && $details_page}
    {include file="addons/vendor_communication/views/vendor_communication/components/new_thread_button.tpl" object_id=$product.product_id show_form=false}
{/if}