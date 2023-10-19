{*
    $company array Company data
*}

{if fn_allowed_for("MULTIVENDOR")}
    <a href="{"companies.products?company_id=`$company.logos.theme.company_id`"|fn_url}" title="{$company.company}">
        {$company.company|truncate:60:"...":true}
    </a>
{elseif fn_allowed_for("ULTIMATE")}
    <span>
        {$company.company|truncate:60:"...":true}
    </span>
{/if}
