{** block-description:selected_filters **}

{if $items}

{$ajax_div_ids = "product_filters_*,selected_filters_*,products_search_*,category_products_*,currencies_*,languages_*,product_features_*"}
{$curl = $config.current_url}
{$filter_base_url = $curl|fn_query_remove:"result_ids":"full_render":"filter_id":"view_all":"req_range_id":"features_hash":"subcats":"page":"total"}

<div class="ty-horizontal-product-filters ty-selected-product-filters cm-product-filters cm-horizontal-filters"
    data-ca-target-id="{$ajax_div_ids}"
    data-ca-base-url="{$filter_base_url|fn_url}"
    data-ca-tooltip-class = "ty-product-filters__tooltip"
    data-ca-tooltip-right-class = "ty-product-filters__tooltip--right"
    data-ca-tooltip-mobile-class = "ty-tooltip--mobile"
    data-ca-tooltip-layout-selector = "[data-ca-tooltip-layout='true']"
    data-ce-tooltip-events-tooltip = "mouseenter"
    id="selected_filters_{$block.block_id}">
<div class="ty-product-filters__wrapper">
{foreach from=$items item="filter"}

    {if $filter.selected_variants || $filter.selected_range}

    {$reset_url = $filter_base_url}
    {$fh = $smarty.request.features_hash|fn_delete_filter_from_hash:$filter.filter_id}
    {if $fh}
        {$reset_url = $filter_base_url|fn_link_attach:"features_hash=$fh"}
    {/if}
    {$filter_uid="`$block.block_id`_`$filter.filter_id`"}

    <div class="ty-horizontal-product-filters-dropdown{if $filter.selected_variants || $filter.selected_range} active{/if}">
        <div id="sw_elm_selected_filter_{$filter_uid}" class="ty-horizontal-product-filters-dropdown__wrapper cm-combination">{$filter.filter}{if $filter.selected_variants} ({$filter.selected_variants|sizeof}){/if}<a class="cm-ajax cm-ajax-full-render cm-history" href="{$reset_url|fn_url}" data-ca-event="ce.filtersinit" data-ca-target-id="{$ajax_div_ids}">{include_ext file="common/icon.tpl" class="ty-icon-cancel-circle"}</a>{include_ext file="common/icon.tpl" class="ty-icon-down-micro ty-horizontal-product-filters-dropdown__icon"}</div>
        <div id="elm_selected_filter_{$filter_uid}" class="cm-popup-box hidden ty-horizontal-product-filters-dropdown__content cm-horizontal-filters-content">
            {hook name="blocks:product_filters_variants_element"}
                {if $filter.slider}
                    {if $filter.feature_type == "ProductFeatures::DATE"|enum}
                        {include file="blocks/product_filters/components/product_filter_datepicker.tpl" filter_uid=$filter_uid filter=$filter}
                    {else}
                        {include file="blocks/product_filters/components/product_filter_slider.tpl" filter_uid=$filter_uid filter=$filter}
                    {/if}
                {else}
                    {include file="blocks/product_filters/components/product_filter_variants.tpl" filter_uid=$filter_uid filter=$filter}
                {/if}
            {/hook}
            <div class="ty-product-filters__tools clearfix">
                {include file="buttons/button.tpl" but_text=__("close") but_meta="cm-external-click" but_external_click_id="sw_elm_filter_`$filter_uid`"}
            </div>
        </div>
    </div>
    {/if}

{/foreach}
</div>
<!--selected_filters_{$block.block_id}--></div>

{/if}

<div data-ca-tooltip-layout="true" class="hidden">
    <button type="button" data-ca-scroll=".ty-mainbox-title" class="cm-scroll ty-tooltip--link ty-tooltip--filter"><span class="tooltip-arrow"></span></button>
</div>
