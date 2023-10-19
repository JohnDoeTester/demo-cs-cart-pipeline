{*
    $company array Company data
*}

<a href="{"companies.products?company_id=`$company.logos.theme.company_id`"|fn_url}">
    {include file="common/image.tpl" images=$company.logos.theme.image image_width="60" image_height="60"}
</a>
