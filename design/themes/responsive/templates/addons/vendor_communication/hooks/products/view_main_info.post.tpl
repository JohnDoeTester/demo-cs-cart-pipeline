{if $addons.vendor_communication.show_on_product == "Y"}
    <div id="product_vendor_communication_thread_form">
    {if $auth.user_id}
        {include
            file="addons/vendor_communication/views/vendor_communication/components/new_thread_form.tpl"
            object_type=$smarty.const.VC_OBJECT_TYPE_PRODUCT
            object_id=$product.product_id
            company_id=$product.company_id
            vendor_name=$product.company_name
        }
    {else}
        {include file="addons/vendor_communication/views/vendor_communication/components/login_form.tpl"}
    {/if}
    <!--product_vendor_communication_thread_form--></div>
{/if}