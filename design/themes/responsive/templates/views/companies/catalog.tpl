{hook name="companies:catalog"}

{$title=__("all_vendors")}

{include file="common/pagination.tpl"}

{include file="views/companies/components/sorting.tpl"}

{if $companies}

{foreach $companies as $company}
{$obj_id=$company.company_id}
{$obj_id_prefix="`$obj_prefix``$obj_id`"}
{include file="common/company_data.tpl" company=$company show_name=true show_descr=true show_rating=true show_vendor_rating=true show_logo=true show_links=true}
<div class="ty-companies">
    <div class="ty-companies__img">
        {assign var="capture_name" value="logo_`$obj_id`"}
        {$smarty.capture.$capture_name nofilter}

        {assign var="rating" value="rating_$obj_id"}
        {$smarty.capture.$rating nofilter}
    </div>

    <div class="ty-companies__info">
        {assign var="company_name" value="name_`$obj_id`"}
        {$smarty.capture.$company_name nofilter}

        {assign var="vendor_rating" value="vendor_rating_$obj_id"}
        {$smarty.capture.$vendor_rating nofilter}
        <div>
            {assign var="company_descr" value="company_descr_`$obj_id`"}
            {$smarty.capture.$company_descr nofilter}
        </div>
    </div>
</div>
{/foreach}

{else}
    <p class="ty-no-items">{__("no_items")}</p>
{/if}

{include file="common/pagination.tpl" extra_id=",vendors_map_container*" full_render=true }

{capture name="mainbox_title"}{$title}{/capture}

{/hook}