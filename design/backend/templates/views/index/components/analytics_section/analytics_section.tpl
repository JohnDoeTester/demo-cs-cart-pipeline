{strip}
{*
    $show_analytics_section
    $analytics_data
    $order_statuses
    $orders

    $analytics_card
*}

{$show_analytics_section = $show_analytics_section|default:true}

{if $show_analytics_section}
    {* Get $analytics_data *}
    {include file="views/index/components/analytics_section/analytics_data/get_analytics_data.tpl"
        order_statuses=$order_statuses
        orders=$orders
    }

    <section class="analytics-section">
        <h2 class="analytics-section__title">
            {__("dashboard.analytics_section_title")}
        </h2>
        <div class="analytics-section__content">
            {if $analytics_data.primary || $analytics_data.secondary || $analytics_data.tertiary}
                <div class="analytics-section__column analytics-section__column--primary">
                    {foreach $analytics_data.primary as $analytics_card}
                        {include file="views/index/components/analytics_section/analytics_card/analytics_card.tpl"}
                    {/foreach}
                </div>
                <div class="analytics-section__column analytics-section__column--secondary">
                    {foreach $analytics_data.secondary as $analytics_card}
                        {include file="views/index/components/analytics_section/analytics_card/analytics_card.tpl"}
                    {/foreach}
                </div>
                <div class="analytics-section__column analytics-section__column--tertiary">
                    {foreach $analytics_data.tertiary as $analytics_card}
                        {include file="views/index/components/analytics_section/analytics_card/analytics_card.tpl"}
                    {/foreach}
                </div>
            {else}
                <div class="analytics-section__no-items no-items">
                    {__("no_data")}
                </div>
            {/if}
        </div>
    </section>
{/if}
{/strip}