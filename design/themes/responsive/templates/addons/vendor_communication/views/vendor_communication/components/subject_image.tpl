{*
    $thread array Thread data
*}

{$object_type=$thread.object_type}
{$object=$thread.object}

{if $object_type === $smarty.const.VC_OBJECT_TYPE_PRODUCT}
    {include file="addons/vendor_communication/views/vendor_communication/components/product_subject_image.tpl"
        product=$object
    }
{elseif $object_type === $smarty.const.VC_OBJECT_TYPE_COMPANY && fn_allowed_for("MULTIVENDOR")}
    {include file="addons/vendor_communication/views/vendor_communication/components/company_subject_image.tpl"
        company=$object
    }
{/if}
