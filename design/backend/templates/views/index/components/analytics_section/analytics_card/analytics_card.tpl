{if $analytics_card}{strip}
    {*
        $analytics_card
    *}

    <div class="analytics-card"
        data-ca-analytics-card="main"
        data-ca-analytics-card-id="{$analytics_card.id}"
        id="{$analytics_card.id}">
        <div class="analytics-card__inner">
            {if $analytics_card.preheader}
                <div class="analytics-card__preheader {""}
                    {if $analytics_card.is_selected_date}
                        cm-date-range__selected-date-text
                    {/if}"
                >
                    {$analytics_card.preheader nofilter}
                </div>
            {/if}
            {if $analytics_card.title || $analytics_card.title_button}
                <div class="analytics-card__header">
                    {if $analytics_card.title}
                        <h3 class="analytics-card__title">{$analytics_card.title nofilter}</h3>
                    {/if}
                    {if $analytics_card.title_button}
                        <a href="{$analytics_card.title_button.href|fn_url}" class="analytics-card__title-button">
                            {$analytics_card.title_button.name nofilter}
                        </a>
                    {/if}
                </div>
            {/if}
            {if $analytics_card.number || $analytics_card.dynamics_number}
                <div class="analytics-card__numbers">
                    {if $analytics_card.number}
                        <div class="analytics-card__number">{$analytics_card.number nofilter}</div>
                    {/if}
                    {if $analytics_card.number_dynamics}
                        {$number_dynamics_text = $analytics_card.number_dynamics}
                        {if $analytics_card.number_dynamics|is_numeric && $analytics_card.number_dynamics >= 0}
                            {$number_dynamics_class = "analytics-card__number-dynamics--positive"}
                            {$number_dynamics_text = "`$analytics_card.number_dynamics`%"}
                        {elseif $analytics_card.number_dynamics|is_numeric && $analytics_card.number_dynamics < 0}
                            {$number_dynamics_class = "analytics-card__number-dynamics--negative"}
                            {$number_dynamics_text = "`$analytics_card.number_dynamics|abs`%"}
                        {/if}
                        <div class="analytics-card__number-dynamics {$number_dynamics_class}">{$number_dynamics_text nofilter}</div>
                    {/if}
                </div>
            {/if}
            {if $analytics_card.content}
                <div class="analytics-card__content">
                    {foreach $analytics_card.content as $content_item}
                        <div class="analytics-card__content-item">
                            {$content_item nofilter}
                        </div>
                    {/foreach}
                </div>
            {/if}
            {if $analytics_card.buttons}
                <div class="analytics-card__buttons">
                    {foreach $analytics_card.buttons as $button_key => $button}
                        {include file="buttons/button.tpl"
                            but_role=$button.role|default:"action"
                            but_id="`$analytics_card.id`_`$button.id|default:$button_key`"
                            but_text=$button.name|default:__("dashboard.view")
                            but_href=$button.href|default:"index.index"
                            but_meta="analytics-card__button `$button.class`"
                        }
                    {/foreach}
                </div>
            {/if}
            {include file="views/index/components/analytics_section/analytics_card/graph.tpl"
                graph=$analytics_card.graph
            }
            {include file="views/index/components/analytics_section/analytics_card/bar_chart.tpl"
                bar_chart=$analytics_card.bar_chart
            }
            {include file="views/index/components/analytics_section/analytics_card/resource_list.tpl"
                resource_list=$analytics_card.resource_list
            }
            {include file="views/index/components/analytics_section/analytics_card/resource_list_tabs.tpl"
                resource_list_tabs=$analytics_card.resource_list_tabs
            }
            {if $analytics_card.scripts}
                {foreach $analytics_card.scripts as $script_path}
                    {script src=$script_path}
                {/foreach}
            {/if}
        </div>
    <!--{$analytics_card.id}--></div>
{/strip}{/if}