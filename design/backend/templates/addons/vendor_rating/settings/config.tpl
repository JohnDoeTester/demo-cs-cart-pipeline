{$active_tab = $smarty.request.selected_sub_section|default:"vendor_rating_tab_general"}

{capture name="general"}
    {include file="addons/vendor_rating/settings/components/formula.tpl"}
    {include file="addons/vendor_rating/settings/components/start_rating_period.tpl"}
    {include file="addons/vendor_rating/settings/components/cron_command.tpl"}
    {if $addons.master_products.status === "ObjectStatuses::ACTIVE"|enum && $addons.master_products.allow_buy_default_common_product === "YesNo::YES"|enum}
        {include file="addons/vendor_rating/settings/components/rating_above_price.tpl"}
    {/if}
{/capture}

{capture name="rating_ranges"}
    {include file="addons/vendor_rating/settings/components/rating_ranges.tpl"}
{/capture}

<div class="cm-j-tabs cm-track tabs" data-ca-tabs-input-name>
    <ul class="nav nav-tabs">
        <li id="vendor_rating_tab_general" class="cm-js {if $active_tab === "vendor_rating_tab_general"}active{/if}">
            <a>{__("general")}</a>
        </li>
        <li id="vendor_rating_tab_rating_ranges" class="cm-js {if $active_tab === "vendor_rating_tab_rating_ranges"}active{/if}">
            <a>{__("vendor_rating.rating_ranges")}</a>
        </li>
    </ul>
</div>

<div class="cm-tabs-content">
    <div id="content_vendor_rating_tab_general" class="hidden">{$smarty.capture.general nofilter}</div>
    <div id="content_vendor_rating_tab_rating_ranges" class="hidden">{$smarty.capture.rating_ranges nofilter}</div>
</div>
