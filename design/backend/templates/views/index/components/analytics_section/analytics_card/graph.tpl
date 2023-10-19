{if $graph && $graph.content}{strip}
    {*
        $analytics_card
        $graph
        $is_day
    *}
    {script src="js/tygh/backend/dashboard/graph.js"}

    <div class="analytics-card-graph"
        data-ca-analytics-card="graph"
        data-ca-analytics-card-id="{$analytics_card.id}_{$graph.id}"
        data-ca-analytics-card-graph-is-day="{$is_day}"
        data-ca-analytics-card-graph-previous-period-text="{__("previous_period")|escape:"htmlall"}"
        data-ca-analytics-card-graph-current-period-text="{__("current_period")|escape:"htmlall"}"
        data-ca-analytics-card-graph-current-previous-period-text="{__("current_previous_period", ["[date]" => "MMM d, yyyy"])|escape:"htmlall"}"
        data-ca-analytics-card-graph-content="{$graph.content|@json_encode}"
    >
        <div class="analytics-card-graph__view spinner" data-ca-analytics-card="graphView"></div>
    </div>
{/strip}{/if}