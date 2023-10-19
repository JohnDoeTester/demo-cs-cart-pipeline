{** block-description:block_vendor_logo **}

<div class="ty-logo-container-vendor">
    {include file="common/image.tpl"
        obj_id=$vendor_info.company_id
        images=$vendor_info.logos.theme.image
        class="ty-logo-container-vendor__image"
        image_additional_attrs=["width" => $vendor_info.logos.theme.image.image_x, "height" => $vendor_info.logos.theme.image.image_y]
        show_no_image=false
        show_detailed_link=false
        capture_image=false
    }
</div>