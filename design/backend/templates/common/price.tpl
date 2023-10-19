{$show_currency = $show_currency|default:true}
{$space = ($enable_non_breaking_space|default:true) ? "&nbsp;" : " "}
{capture name="currencies"}
{strip}
    {if $settings.General.alternative_currency == "use_selected_and_alternative"}
        {$value|format_price:$currencies.$primary_currency:$span_id:$class:false nofilter}
        {if $secondary_currency != $primary_currency}{$space nofilter}
            ({if $class}<span class="{$class}"></span>{/if}
                {$value|format_price:$currencies.$secondary_currency:$span_id:$class:true:$is_integer nofilter}
            {if $class}<span class="{$class}"></span>{/if})
        {/if}
    {else}
        {$value|format_price:$currencies.$secondary_currency:$span_id:$class:true nofilter}
    {/if}
{/strip}
{/capture}

{if $view == "input"}
    <input type="text" id="{$input_id}" name="{$input_name}" value="{$value}" class="cm-numeric {$class}" placeholder="{$placeholder}" {if $show_currency}data-a-sign="{$currencies.$primary_currency.symbol|strip_tags nofilter}"{/if} {if $currencies.$primary_currency.after == "Y"}data-p-sign="s"{/if} data-a-dec="." data-a-sep="," {if $product_id}data-ct-product="{$product_id}"{/if}>
{else}
    {$smarty.capture.currencies nofilter}
{/if}