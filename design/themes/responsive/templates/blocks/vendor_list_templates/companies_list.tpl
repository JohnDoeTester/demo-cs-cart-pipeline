{** block-description:vendor_names **}

{if $items}
    <ul>
    {foreach from=$items item=v key=k}
        <li><a href="{"companies.products?company_id=`$k`"|fn_url}">{$v.company}</a></li>
    {/foreach}
    </ul>
    
    <div class="ty-homepage-vendors__devider">
        <a class="ty-btn ty-btn__tertiary" href="{"companies.catalog"|fn_url}">{__("all_vendors")}</a>
    </div>
{/if}