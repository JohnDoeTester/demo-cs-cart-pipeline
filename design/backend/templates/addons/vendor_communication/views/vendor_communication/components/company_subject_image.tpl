{*
    $company array Company data
*}

{include file="common/image.tpl" image=$company.logos.theme.image
    image_width=$settings.Thumbnails.product_admin_mini_icon_width 
    image_height=$settings.Thumbnails.product_admin_mini_icon_height
    href="companies.update?company_id=`$company.logos.theme.company_id`"|fn_url
}