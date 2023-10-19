{strip}
{if "MULTIVENDOR"|fn_allowed_for}
    {*
        Column 3. Item 1. Current balance (For vendor only)
    *}
    {if $runtime.company_id}
        {* Prepare vendor current balance value *}
        {include file="common/price.tpl"
            value=$current_balance
            assign=vendor_current_balance_value
        }

        {$data_3_1_vendor_current_balance = [
            id => "analytics_card_vendor_current_balance",
            title => __("dashboard.analytics_card.vendor_current_balance"),
            title_button => [
                name => __("dashboard.analytics_card.view_accounting"),
                href => "companies.balance"
            ],
            number => $vendor_current_balance_value
        ]}
        {$data_3_1_vendor_current_balance = $data_3_1_vendor_current_balance scope=parent}
    {/if}
{/if}
{/strip}