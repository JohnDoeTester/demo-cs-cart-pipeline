{if $bar_chart && $bar_chart.content}{strip}
    {*
        $analytics_card
        $bar_chart
        $item
        $ratio
        $href_tag
        $href_attr
    *}
    <div class="analytics-card-bar-chart"
        data-ca-analytics-card="barChart"
        data-ca-analytics-card-id="{$analytics_card.id}_{$bar_chart.id}"
    >
        {if $bar_chart.title}
            <div class="analytics-card-bar-chart__title">{$bar_chart.title nofilter}</div>
        {/if}
        <div class="analytics-card-bar-chart__content">
            {foreach $bar_chart.content as $item}
                {if $item.ratio}
                    {$ratio = $item.ratio}
                {elseif $item.total}
                    {$ratio = $item.value / $item.total * 100}
                {else}
                    {$ratio = "0.00"}
                {/if}

                {if $item.value && $item.is_price}
                    {include file="common/price.tpl" value=$item.value assign=item_value_text}
                {else}
                    {$item_value_text = $item.value}
                {/if}

                {if $item.total && $item.is_price}
                    {include file="common/price.tpl" value=$item.total assign=item_total_text}
                {elseif $item.total}
                    {$item_total_text = $item.total}
                {else}
                    {$item_total_text = __("dashboard.analytics_card.unlimited")}
                {/if}

                {$href_tag = ($item.href) ? "a" : "div"}
                {$href_attr = ($item.href) ? "href=`$item.href|fn_url`" : ""}
                <div class="analytics-card-bar-chart__item"
                    data-ca-analytics-card-bar-chart-item-id="{$analytics_card.id}_{$bar_chart.id}_{$item.id}"
                >
                    <div class="analytics-card-bar-chart__item-info">
                        {if $item.name}
                            <{$href_tag} {$href_attr} class="analytics-card-bar-chart__item-name">{$item.name nofilter}</{$href_tag}>
                        {/if}
                        <div class="analytics-card-bar-chart__item-values">
                            <{$href_tag} {$href_attr} class="analytics-card-bar-chart__item-value">{$item_value_text nofilter}</{$href_tag}>
                            <span class="analytics-card-bar-chart__item-total">{$item_total_text nofilter}</span>
                        </div>
                    </div>
                    <{$href_tag} {$href_attr} class="analytics-card-bar-chart__item-bar-wrapper">
                        <div class="progress analytics-card-bar-chart__item-progress">
                            <div class="bar analytics-card-bar-chart__item-bar
                                {if $item.type === "primary"} analytics-card-bar-chart__item-bar--primary{/if}
                                {if $item.total && $item.value > $item.total} analytics-card-bar-chart__item-bar--danger{/if}"
                                {if $ratio > 0} style="width: {$ratio}%;"{/if}
                            ></div>
                        </div>
                    </{$href_tag}>
                </div>
            {/foreach}
        </div>
    </div>
{/strip}{/if}