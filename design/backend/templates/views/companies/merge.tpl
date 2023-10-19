{include file="views/profiles/components/profiles_scripts.tpl"}

{capture name="mainbox"}

<form action="{""|fn_url}" method="post" name="userlist_form" id="userlist_form">
<input type="hidden" name="fake" value="1" />
<input type="hidden" name="from_company_id" value="{$company_id}" />

{include file="common/pagination.tpl" save_current_page=true save_current_url=false}

{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
{include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}
{if $companies}
<div class="table-responsive-wrapper">
    <table width="100%" class="table table-middle table--relative table-responsive">
    <thead>
    <tr>
        <th width="1%" class="left">
        <th><a class="cm-ajax" href="{"`$c_url`&sort_by=id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("id")}{if $search.sort_by === "id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
        <th width="25%"><a class="cm-ajax" href="{"`$c_url`&sort_by=company&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("name")}{if $search.sort_by === "company"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
        <th width="25%"><a class="cm-ajax" href="{"`$c_url`&sort_by=email&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("email")}{if $search.sort_by === "email"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
        <th width="25%"><a class="cm-ajax" href="{"`$c_url`&sort_by=date&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("registered")}{if $search.sort_by === "date"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
    </tr>
    </thead>
    {foreach from=$companies key=k item=company}
    <tr>
        <td class="left" data-th="">
            <input type="radio"{if $k == 0} checked="checked"{/if} name="to_company_id" value="{$company.company_id}"/>
        </td>
        <td data-th="{__("id")}"><a href="{"companies.update?company_id=`$company.company_id`"|fn_url}">&nbsp;<span>{$company.company_id}</span>&nbsp;</a></td>
        <td data-th="{__("name")}"><a href="{"companies.update?company_id=`$company.company_id`"|fn_url}">{$company.company}</a></td>
        <td data-th="{__("email")}"><a href="mailto:{$company.email}">{$company.email}</a></td>
        <td data-th="{__("registered")}">{$company.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
    </tr>
    {/foreach}
    </table>
</div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{if $companies}
    {*include file="common/table_tools.tpl" href="#companies"*}
{/if}

{include file="common/pagination.tpl"}
</form>

{capture name="buttons"}
    {if $companies}
    <div class="pull-left">
    {*capture name="tools_list"}
         <ul>
             <li><a class="cm-process-items cm-submit" data-ca-dispatch="dispatch[profiles.export_range]" data-ca-target-form="userlist_form">{__("export_selected")}</a></li>
         </ul>
         {/capture*}
        {include file="buttons/button.tpl" but_text=__("merge") but_name="dispatch[companies.merge]" but_meta="cm-confirm" but_role="submit-link" but_target_form="userlist_form"}
        {*include file="common/tools.tpl" prefix="main" hide_actions=true tools_list=$smarty.capture.tools_list display="inline" link_text=__("choose_action")*}
    </div>
    {/if}
{/capture}

{capture name="sidebar"}
    <div class="sidebar-row">
    <h6>{__("help")}</h6>
        <span>{__("warning_merging_companies", ["[company_name]" => $company_name])}</span>
        {__("select_new_owner_company")}
    </div>
    {*include file="views/companies/components/companies_search_form.tpl" dispatch="companies.merge" company_id=$smarty.request.company_id*}
{/capture}

{/capture}
{include file="common/mainbox.tpl" title="{__("merge_vendor")}: `$company_name`" content=$smarty.capture.mainbox title_extra=$smarty.capture.title_extra sidebar=$smarty.capture.sidebar buttons=$smarty.capture.buttons}
