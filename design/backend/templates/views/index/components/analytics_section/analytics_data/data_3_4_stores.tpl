{strip}
{*
    Column 3. Item 5. Stores: Storefront or vendors (For administration only)
*}
{if !empty($general_stats.companies) && $auth.user_type === "UserTypes::ADMIN"|enum && !$runtime.company_id}

    {if "MULTIVENDOR"|fn_allowed_for}
        {$stores_text = __("dashboard.analytics_card.vendors")}
        {$view_stores_text = __("dashboard.analytics_card.view_vendors")}
    {else}
        {$stores_text = __("dashboard.analytics_card.stores")}
        {$view_stores_text = __("dashboard.analytics_card.view_stores")}
    {/if}
    {$data_3_4_stores = [
        id => "analytics_card_stores",
        title => $stores_text,
        title_button => [
            name => $view_stores_text,
            href => "companies.manage"
        ],
        number => $general_stats.companies.total_companies|number_format
    ]}
    {$data_3_4_stores = $data_3_4_stores scope=parent}
{/if}
{/strip}