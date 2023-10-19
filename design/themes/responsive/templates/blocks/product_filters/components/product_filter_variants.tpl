<ul class="ty-product-filters {if $collapse}hidden{/if}" id="content_{$filter_uid}">

    {if $filter.display_count && $filter.variants|count > $filter.display_count}
    <li>
        {script src="js/tygh/filter_table.js"}

        <div class="ty-product-filters__search">
        <input type="text" placeholder="{__("storefront_search_field")}" class="cm-autocomplete-off ty-input-text-medium" name="q" id="elm_search_{$filter_uid}" value="" />
        {include_ext file="common/icon.tpl"
            class="ty-icon-cancel-circle ty-product-filters__search-icon hidden"
            id="elm_search_clear_`$filter_uid`"
            title=__("clear")
        }
        </div>
    </li>
    {/if}

    {$white_color = "#ffffff"}

    {if $filter.variants}
        {if $filter.filter_style == "ProductFilterStyles::COLOR"|enum}
            <div class="ty-product-filters__color-filter">
                {foreach $filter.variants as $variant}
                    <li class="ty-product-filters__color-list-item cm-product-filters-checkbox-container">
                        <label
                            id="elm_checkbox_{$filter_uid}_{$variant.variant_id}"
                            name="product_filters[{$filter.filter_id}]"
                            class="ty-product-filters__color-filter-item
                            {if $variant.disabled}{if $variant.selected}ty-product-filters__color-filter-item--selected{else}ty-product-filters__color-filter-item--disabled{/if}{/if}"
                            data-cm-product-color-filter="true"
                            data-ca-product-color-filter-checkbox-id="elm_checkbox_{$filter_uid}_{$variant.variant_id}"
                            title="{$filter.prefix}{$variant.variant|fn_text_placeholders}{$filter.suffix}">
                            <input
                                class="cm-product-filters-checkbox ty-product-filters__color-filter-checkbox"
                                type="checkbox"
                                data-ca-filter-id="{$filter.filter_id}"
                                value="{$variant.variant_id}"
                                id="elm_checkbox_{$filter_uid}_{$variant.variant_id}"
                                {if $variant.selected}checked{/if}
                                {if $variant.disabled && !$variant.selected}disabled="disabled"{/if} />
                            {$variant_selected_class = ($variant.selected) ? "ty-product-filters__color-filter-check--selected" : ""}
                            {$variant_color_class = ($variant.color === $white_color) ? "ty-product-filters__color-filter-check--invert" : ""}
                            {include_ext file="common/icon.tpl"
                                class="ty-icon-ok ty-product-filters__color-filter-check `$variant_selected_class` `$variant_color_class`"
                            }
                            <div
                                class="ty-product-filters__color-filter-swatch
                                {if $variant.selected}ty-product-filters__color-filter-swatch--selected{/if}"
                                style="background-color:{$variant.color|default:$white_color}">
                            </div>
                        </label>
                    </li>
                {/foreach}
            </div>
        {else}
            <li class="ty-product-filters__item-more">
                <ul id="ranges_{$filter_uid}" {if $filter.display_count}style="max-height: {$filter.display_count * 2}em;"{/if} class="ty-product-filters__variants cm-filter-table" data-ca-input-id="elm_search_{$filter_uid}" data-ca-clear-id="elm_search_clear_{$filter_uid}" data-ca-empty-id="elm_search_empty_{$filter_uid}">

                    {foreach $filter.variants as $variant}
                        <li class="cm-product-filters-checkbox-container ty-product-filters__group">
                            <label {if $variant.disabled} class="{if $variant.selected}ty-product-filters__empty-result{else}disabled{/if}"{/if}>
                                <input class="cm-product-filters-checkbox"
                                       type="checkbox"
                                       {if $variant.selected}checked="checked"{/if}
                                       name="product_filters[{$filter.filter_id}]"
                                       data-ca-filter-id="{$filter.filter_id}"
                                       value="{$variant.variant_id}"
                                       id="elm_checkbox_{$filter_uid}_{$variant.variant_id}"
                                        {if $variant.disabled && !$variant.selected}disabled="disabled"{/if}>
                                <span>{$filter.prefix}{$variant.variant|fn_text_placeholders}{$filter.suffix}</span>
                            </label>
                        </li>
                    {/foreach}
                </ul>
            </li>
        {/if}
        <li>
            <p id="elm_search_empty_{$filter_uid}" class="ty-product-filters__no-items-found hidden">{__("no_items_found")}</p>
        </li>
    {/if}
</ul>
