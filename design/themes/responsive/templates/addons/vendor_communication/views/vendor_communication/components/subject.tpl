{*
    $thread array Thread data
*}

{$object_type=$thread.object_type}
{$object=$thread.object}

{if !$object.object_id}
    {$thread.subject}
{elseif $object_type === $smarty.const.VC_OBJECT_TYPE_PRODUCT}
    {include file="addons/vendor_communication/views/vendor_communication/components/product_subject.tpl"
        product=$object
    }
{elseif $object_type === $smarty.const.VC_OBJECT_TYPE_ORDER}
    {include file="addons/vendor_communication/views/vendor_communication/components/order_subject.tpl"
        order=$object
    }
{elseif $object_type === $smarty.const.VC_OBJECT_TYPE_COMPANY}
    {include file="addons/vendor_communication/views/vendor_communication/components/company_subject.tpl"
        company=$object
    }
{/if}
