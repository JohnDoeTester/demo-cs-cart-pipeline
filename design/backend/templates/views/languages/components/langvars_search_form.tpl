<div class="sidebar-row">
    <h6>{__("admin_search_title")}</h6>
    <form action="{""|fn_url}" name="langvars_search_form" method="get">
        <input type="hidden" name="name" value="{if !$smarty.request.name|is_array}{$smarty.request.name}{/if}"/>

        <div class="sidebar-field">
            <label>{__("search_for_pattern")}</label>
            <input type="text" name="q" size="20" value="{$smarty.request.q}" class="search-input-text"/>
        </div>

        {include file="buttons/search.tpl" but_name="dispatch[languages.translations]"}
    </form>
</div>