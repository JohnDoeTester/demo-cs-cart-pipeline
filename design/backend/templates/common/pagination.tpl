{hook name="common:pagination"}

    {assign var="id" value=$div_id|default:"pagination_contents"}
    {assign var="c_url" value=$current_url|default:$config.current_url|fn_query_remove:"page"}
    {assign var="pagination" value=$search|fn_generate_pagination}
    {$show_pagination_open = $show_pagination_open|default:true}
    {$show_pagination_open = $show_pagination_open scope=parent}

    {if $smarty.capture.pagination_open == "Y"}
        {assign var="pagination_meta" value=" paginate-top"}
    {/if}

    {if $smarty.capture.pagination_open != "Y"}
    <div class="cm-pagination-container{if $pagination_class} {$pagination_class}{/if}" id="{$id}">
    {/if}

    {if $pagination}
        {assign var="min_per_page_range" value=$pagination.per_page_range|min}

        {if $save_current_page}
            <input type="hidden" name="page" value="{$search.page|default:$smarty.request.page|default:1}" />
        {/if}

        {if $save_current_url}
            <input type="hidden" name="redirect_url" value="{$config.current_url}" />
        {/if}

        {if !$disable_history}
            {assign var="history_class" value=" cm-history"}
        {else}
            {assign var="history_class" value=" cm-ajax-cache"}
        {/if}
        {if $smarty.capture.pagination_open !== "Y" && $show_pagination_open || $smarty.capture.pagination_open === "Y"}
            <div class="pagination-wrap clearfix">

                {* Left buttons *}
                {if $pagination.total_items > $min_per_page_range}
                    <div class="pagination pagination-start">
                        <ul>
                        {if $pagination.current_page != "full_list" && $pagination.total_pages > 0}

                            {* Button "<<" *}
                            <li class="{if !$pagination.prev_page}disabled{/if}{$history_class} mobile-hide">
                                <a
                                    data-ca-scroll=".cm-pagination-container"
                                    class="{if $pagination.prev_page}cm-ajax{/if}{$history_class} pagination-item"
                                    {if $pagination.prev_page}
                                        href="{"`$c_url`&page=1"|fn_url}"
                                        data-ca-page="1"
                                        data-ca-target-id="{$id}"
                                    {/if}>
                                    {include_ext file="common/icon.tpl" class="icon icon-double-angle-left"}
                                </a>
                            </li>

                            {* Button "<" *}
                            <li class="{if !$pagination.prev_page}disabled{/if}{$history_class}">
                                <a
                                    data-ca-scroll=".cm-pagination-container"
                                    class="{if $pagination.prev_page}cm-ajax{/if}{$history_class} pagination-item"
                                    {if $pagination.prev_page}
                                        href="{"`$c_url`&page=`$pagination.prev_page`"|fn_url}"
                                        data-ca-page="{$pagination.prev_page}"
                                        data-ca-target-id="{$id}"
                                    {/if}>
                                    {include_ext file="common/icon.tpl" class="icon icon-angle-left"}
                                </a>
                            </li>
                        {/if}
                        </ul>
                    </div>

                    {* Dropdown button *}
                    <div class="pagination-dropdown">

                        {foreach from=$pagination.navi_pages item="pg" name="f_pg"}

                            {if $pg == $pagination.current_page}
                            {capture name="pagination_list"}
                                {assign var="range_url" value=$c_url|fn_query_remove:"items_per_page"}

                                {foreach from=$pagination.per_page_range item="step"}
                                    <li>
                                        <a
                                            data-ca-scroll=".cm-pagination-container"
                                            class="cm-ajax{$history_class} pagination-dropdown-per-page"
                                            href="{"`$c_url`&items_per_page=`$step`"|fn_url}"
                                            data-ca-target-id="{$id}">
                                            {__("objects_per_page", ["[n]" => $step])}
                                        </a>
                                    </li>
                                {/foreach}

                            {/capture}
                            {$rnd = rand()}
                            {include
                                file="common/tools.tpl"
                                prefix="pagination_`$rnd`"
                                caret=true
                                hide_actions=true
                                tools_list=$smarty.capture.pagination_list
                                link_text=__("pagination_range", ["[pagination.range_from]" => $pagination.range_from, "[pagination.range_to]" => $pagination.range_to, "[pagination.total_items]" => $pagination.total_items])
                                override_meta="pagination-selector"
                                skip_check_permissions="true"
                                tool_meta="{$pagination_meta}"
                            }
                            {/if}
                        {/foreach}
                    </div>

                    {* Right buttons *}
                    <div class="pagination pagination-end">
                        <ul>
                        {if $pagination.current_page != "full_list" && $pagination.total_pages > 0}

                            {* Button ">" *}
                            <li class="{if !$pagination.next_page}disabled{/if}{$history_class} pagination-item">
                                <a
                                    data-ca-scroll=".cm-pagination-container"
                                    class="{if $pagination.next_page}cm-ajax{/if}{$history_class} pagination-item"
                                    {if $pagination.next_page}
                                        href="{"`$c_url`&page=`$pagination.next_page`"|fn_url}"
                                        data-ca-page="{$pagination.next_page}"
                                        data-ca-target-id="{$id}"
                                    {/if}>
                                    {include_ext file="common/icon.tpl" class="icon icon-angle-right"}
                                </a>
                            </li>

                            {* Button ">>" *}
                            <li class="{if !$pagination.next_page}disabled{/if}{$history_class} mobile-hide">
                                <a
                                    data-ca-scroll=".cm-pagination-container"
                                    class="{if $pagination.next_page}cm-ajax{/if}{$history_class} pagination-item"
                                    {if $pagination.next_page}
                                        href="{"`$c_url`&page=`$pagination.total_pages`"|fn_url}"
                                        data-ca-page="{$pagination.total_pages}"
                                        data-ca-target-id="{$id}"
                                    {/if}>
                                    {include_ext file="common/icon.tpl" class="icon icon-double-angle-right"}
                                </a>
                            </li>
                        {/if}
                        </ul>
                    </div>
                {/if}
                
                {if $extra}
                    <div class="pagination__extra">
                        {$extra nofilter}
                    </div>
                {/if}

            </div>
        {/if}
    {/if}

    {if $smarty.capture.pagination_open == "Y"}
        <!--{$id}--></div>
        {capture name="pagination_open"}N{/capture}
    {elseif $smarty.capture.pagination_open != "Y"}
        {capture name="pagination_open"}Y{/capture}
    {/if}

{/hook}
