{** block-description:original **}

{script src="js/tygh/product_filters.js"}

{if $block.type == "product_filters"}
    {$ajax_div_ids = "product_filters_*,selected_filters_*,products_search_*,category_products_*,currencies_*,languages_*,product_features_*"}
    {$curl = $config.current_url}
{else}
    {$curl = "products.search"|fn_url}
    {$ajax_div_ids = ""}
{/if}

{$filter_base_url = $curl|fn_query_remove:"result_ids":"full_render":"filter_id":"view_all":"req_range_id":"features_hash":"subcats":"page":"total"}
{$is_selected_filters = $smarty.request.features_hash}
{$show_not_found_notification = $show_not_found_notification|default:0}

<div class="cm-product-filters"
    data-ca-target-id="{$ajax_div_ids}"
    data-ca-base-url="{$filter_base_url|fn_url}"
    data-ca-tooltip-class = "ty-product-filters__tooltip"
    data-ca-tooltip-right-class = "ty-product-filters__tooltip--right"
    data-ca-tooltip-mobile-class = "ty-tooltip--mobile"
    data-ca-tooltip-layout-selector = "[data-ca-tooltip-layout='true']"
    data-ce-tooltip-events-tooltip = "mouseenter"
    id="product_filters_{$block.block_id}">
<div class="ty-product-filters__wrapper" data-ca-product-filters="wrapper" {if $is_selected_filters}data-ca-product-filters-status="active"{/if}>
{if $items}

{foreach from=$items item="filter" name="filters"}
    {hook name="blocks:product_filters_variants"}
    {assign var="filter_uid" value="`$block.block_id`_`$filter.filter_id`"}
    {assign var="cookie_name_show_filter" value="content_`$filter_uid`"}
    {if $filter.display == "N"}
        {* default behaviour of cm-combination *}
        {assign var="collapse" value=true}
    {else}
        {* reverse behaviour of cm-combination *}
        {assign var="collapse" value=false}
    {/if}

    {$reset_url = ""}
    {if $filter.selected_variants || $filter.selected_range}
        {$reset_url = $filter_base_url}
        {$fh = $smarty.request.features_hash|fn_delete_filter_from_hash:$filter.filter_id}
        {if $fh}
            {$reset_url = $filter_base_url|fn_link_attach:"features_hash=$fh"|fn_link_attach:"show_not_found_notification=$show_not_found_notification"}
        {/if}
    {/if}

    {$is_filter_slider=$filter.slider}

    <div class="ty-product-filters__block{if !$is_filter_slider} ty-product-filters__block--variants{/if}">
        <div id="sw_content_{$filter_uid}" class="ty-product-filters__switch cm-combination-filter_{$filter_uid}{if !$collapse} open{/if} cm-save-state {if $filter.display == "Y"}cm-ss-reverse{/if}">
            <span class="ty-product-filters__title">{$filter.filter}{if $filter.selected_variants} ({$filter.selected_variants|sizeof}){/if}{if $reset_url}<a class="cm-ajax cm-ajax-full-render cm-history" href="{$reset_url|fn_url}" data-ca-event="ce.filtersinit" data-ca-target-id="{$ajax_div_ids}">{include_ext file="common/icon.tpl" class="ty-icon-cancel-circle ty-reset-filters"}</a>{/if}</span>
            {include_ext file="common/icon.tpl" class="ty-icon-down-open ty-product-filters__switch-down"}
            {include_ext file="common/icon.tpl" class="ty-icon-up-open ty-product-filters__switch-right"}
        </div>

        {hook name="blocks:product_filters_variants_element"}
            {if $is_filter_slider}
                {if $filter.feature_type == "ProductFeatures::DATE"|enum}
                    {include file="blocks/product_filters/components/product_filter_datepicker.tpl" filter_uid=$filter_uid filter=$filter}
                {else}
                    {include file="blocks/product_filters/components/product_filter_slider.tpl" filter_uid=$filter_uid filter=$filter}
                {/if}
            {else}
                {include file="blocks/product_filters/components/product_filter_variants.tpl" filter_uid=$filter_uid filter=$filter collapse=$collapse}
            {/if}
        {/hook}
    </div>
    {/hook}
{/foreach}

{if $ajax_div_ids}
<div class="ty-product-filters__tools clearfix {if !$is_selected_filters}hidden{/if}" data-ca-product-filters="tools">

    <a href="{$filter_base_url|fn_url}" rel="nofollow" class="ty-product-filters__reset-button cm-ajax cm-ajax-full-render cm-history" data-ca-event="ce.filtersinit" data-ca-target-id="{$ajax_div_ids}">
        {include_ext file="common/icon.tpl"
            class="ty-icon-cw ty-product-filters__reset-icon"
        }
        {__("reset")}
    </a>

</div>
{/if}

{/if}
</div>
<!--product_filters_{$block.block_id}--></div>

<div data-ca-tooltip-layout="true" class="hidden">
    <button type="button" data-ca-scroll=".ty-mainbox-title" class="cm-scroll ty-tooltip--link ty-tooltip--filter"><span class="tooltip-arrow"></span></button>
</div>
