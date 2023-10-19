{script src="js/lib/jqueryuitouch/jquery.ui.touch-punch.min.js"}
{$min = $filter.min}
{$max = $filter.max}
{$left = $filter.left|default:$min}
{$right = $filter.right|default:$max}
{$disable_slider = $filter.disable}

{if $filter.selected_range}
    {$value = "{$filter.current_left}{$smarty.const.FILTERS_HASH_FEATURE_SEPARATOR}{$filter.current_right}"}

    {if $filter.extra}
        {$value = "{$value}{$smarty.const.FILTERS_HASH_FEATURE_SEPARATOR}{$filter.extra}"}
    {/if}
{/if}

<div id="content_{$filter_uid}" class="cm-product-filters-checkbox-container ty-price-slider {if $collapse}hidden{/if} {$extra_class}">
    <p class="ty-price-slider__inputs">
        <bdi class="ty-price-slider__bidi-container">
            <span class="ty-price-slider__filter-prefix">{$filter.prefix nofilter}</span>
            <input type="text"
                class="ty-price-slider__input-text"
                id="slider_{$filter_uid}_left"
                name="left_{$filter_uid}"
                value="{$left}"
                {if $disable_slider}
                    disabled="disabled"
                {/if}
                data-ca-previous-value="{$left}"/>
            <span class="ty-price-slider__filter-suffix">{$filter.suffix nofilter}</span>
        </bdi>
        &nbsp;–&nbsp;
        <bdi class="ty-price-slider__bidi-container">
            <span class="ty-price-slider__filter-prefix">{$filter.prefix nofilter}</span>
            <input type="text"
                class="ty-price-slider__input-text"
                id="slider_{$filter_uid}_right"
                name="right_{$filter_uid}"
                value="{$right}"
                {if $disable_slider}
                    disabled="disabled"
                {/if}
                data-ca-previous-value="{$right}"/>
            <span class="ty-price-slider__filter-suffix">{$filter.suffix nofilter}</span>
        </bdi>
    </p>
        <div id="slider_{$filter_uid}" class="ty-range-slider cm-range-slider">
            <ul class="ty-range-slider__wrapper">
                <li class="ty-range-slider__item" style="left: 0%;">
                    <span class="ty-range-slider__num">
                        <span><bdi>{$filter.prefix nofilter}<span>{$min}</span>{$filter.suffix nofilter}</bdi></span>
                    </span>
                </li>
                <li class="ty-range-slider__item" style="left: 100%;">
                    <span class="ty-range-slider__num">
                        <span><bdi>{$filter.prefix nofilter}<span>{$max}</span>{$filter.suffix nofilter}</bdi></span>
                    </span>
                </li>
            </ul>
        </div>

        <input id="elm_checkbox_slider_{$filter_uid}" data-ca-filter-id="{$filter.filter_id}" class="cm-product-filters-checkbox hidden" type="checkbox" name="product_filters[{$filter.filter_id}]" value="{$value}" {if $filter.selected_range}checked="checked"{/if} />

        {* Slider params *}
        <input type="hidden" id="slider_{$filter_uid}_json" value='{ldelim}
            "disabled": {$disable_slider|to_json},
            "min": {$min},
            "max": {$max},
            "left": {$left},
            "right": {$right},
            "step": {$filter.round_to},
            "extra": "{$filter.extra}"
        {rdelim}' />
        {* /Slider params *}
</div>
