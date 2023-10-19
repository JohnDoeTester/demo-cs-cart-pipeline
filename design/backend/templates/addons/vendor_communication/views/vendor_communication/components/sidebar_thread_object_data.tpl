{if $object.object_type == $smarty.const.VC_OBJECT_TYPE_PRODUCT}
    {include file="common/sidebar/sidebar_product.tpl"
        product_data=$object
    }
{elseif $object.object_type === $smarty.const.VC_OBJECT_TYPE_ORDER}
    {include file="addons/vendor_communication/views/vendor_communication/components/sidebar_thread_order_data.tpl"
        object=$object
        object_id=$object_id
    }
{/if}