<div id="products_search_{$block.block_id}">

{assign var="products_search" value="Y"}
{$is_selected_filters = $smarty.request.features_hash}

{if $products}
    {assign var="title_extra" value="{__("products_found")}: `$search.total_items`"}
    {assign var="layouts" value=""|fn_get_products_views:false:0}

    {if $layouts.$selected_layout.template}
        {include file="`$layouts.$selected_layout.template`" columns=$settings.Appearance.columns_in_products_list show_qty=true}
    {/if}
{else}
    {hook name="products:search_results_no_matching_found"}
        {if !$show_not_found_notification && $is_selected_filters}
            {include file="common/no_items.tpl"
                text_no_found=__("text_no_products_found")
                no_items_extended=true
                reset_url=$config.current_url|fn_query_remove:"features_hash"
            }
        {else}
            {include file="common/no_items.tpl"
                text_no_found=__("text_no_matching_products_found")
            }
        {/if}
    {/hook}
{/if}

<!--products_search_{$block.block_id}--></div>

{hook name="products:search_results_mainbox_title"}
{capture name="mainbox_title"}<span class="ty-mainbox-title__left">{__("search_results")}</span><span class="ty-mainbox-title__right" id="products_search_total_found_{$block.block_id}">{$title_extra nofilter}<!--products_search_total_found_{$block.block_id}--></span>{/capture}
{/hook}