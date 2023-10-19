{*
    $company array Company data
*}

<a href="{"companies.update?company_id=`$company.logos.theme.company_id`"|fn_url}" title="{$company.company}">
    <small>
        {$company.company|truncate:50:"...":true}
    </small>
</a>
