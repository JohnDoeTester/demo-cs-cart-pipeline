{if !$checkbox_name}
    {$checkbox_name="page_ids"}
{/if}

{if $parent_id}
    <div class="pages-tree__children {if !$expand_all}hidden{/if}" id="page{$combination_suffix}_{$parent_id}">
{/if}

{foreach from=$pages_tree item=page}

    {if "ULTIMATE"|fn_allowed_for}
        {$allow_save=$page|fn_allow_save_object:"pages"}
    {/if}

    {if !$is_exclusive_page_type && $page_types[$page.page_type].exclusive}
        {$_come_from = $page.page_type}
    {else}
        {$_come_from = $come_from}
    {/if}

    {if $language_direction == "rtl"}
        {$direction = "right"}
    {else}
        {$direction = "left"}
    {/if}
    
    <div class="longtap-selection" data-ca-bulkedit-component="tableWrapper">
        <table width="100%" class="table table-tree table-middle table--relative table-nobg table-responsive">
            {if $header && !$hide_header}
                {$header=""}
                <thead data-ca-bulkedit-default-object="true" data-ca-bulkedit-component="defaultObject">
                    <tr>
                        <th class="left mobile-hide" width="6%">
                            {if $display != "radio"}
                                {if $is_bulkedit_menu}
                                    {include file="common/check_items.tpl" check_statuses=$pages_statuses is_check_disabled=!$has_permission}

                                    <input type="checkbox"
                                        class="bulkedit-toggler hide"
                                        data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]" 
                                        data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                    />
                                {else}
                                    {include file="common/check_items.tpl"}
                                {/if}
                            {/if}
                        </th>
                        {if !$picker && !$hide_position}
                            <th class="left mobile-hide" width="7%">{__("position_short")}</th>
                        {/if}
                        <th width="70%" class="left">
                            {if !$hide_show_all && $search.get_tree == "multi_level"}
                                <span alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" id="on_page{$combination_suffix}" class="cm-combinations-pages{$combination_suffix}{if $expand_all} hidden{/if}" ><span class="icon-caret-right "></span></span>
                                <span alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" id="off_page{$combination_suffix}" class="cm-combinations-pages{$combination_suffix}{if !$expand_all} hidden{/if}" ><span class="icon-caret-down "></span></span>
                            {/if}
                            {__("name")}
                        </th>
                        {if !$hide_delete_button}
                            <th width="10%" class="mobile-hide">&nbsp;</th>
                        {/if}
                        {if !$picker}
                            <th width="10%" class="right">{__("status")}</th>
                        {/if}
                    </tr>
                </thead>
            {/if}
            <tr class="cm-row-status-{$page.status|lower}{if $page.level > 0 && $search.get_tree == "multi_level"} cm-longtap-target longtap-selection multiple-table-row{/if}"
                {if $is_bulkedit_menu && $has_permission && $display !== "radio"}
                    data-ca-longtap-action="setCheckBox"
                    data-ca-longtap-target="input.cm-item"
                    data-ca-id="{$page.page_id}"
                {/if}
            >
                <td class="left mobile-hide" width="6%">
                    {if $display == "radio"}
                        <input type="radio" name="{$checkbox_name}" id="radio_{$page.page_id}" value="{$page.page_id}" class="cm-item" />
                    {else}
                        <input type="checkbox" name="{$checkbox_name}[]" id="checkbox_{$page.page_id}" value="{$page.page_id}" class="cm-item cm-item-status-{$page.status|lower}{if $is_bulkedit_menu} hide {/if}user-success" />
                    {/if}
                </td>
                {if !$picker && !$hide_position}
                    <td width="7%" class="mobile-hide" data-th="{__("position_short")}">
                        <input type="text" name="pages_data[{$page.page_id}][position]" size="3" maxlength="10" value="{$page.position}" class="input-micro input-hidden" />
                        
                        {if "ULTIMATE"|fn_allowed_for}
                            <input type="hidden" name="pages_data[{$page.page_id}][company_id]" size="3" maxlength="3" value="{$page.company_id}" class="hidden" />
                        {/if}
                    </td>
                {/if}
                <td class="row-status" width="70%" data-th="{__("name")}">
                    {strip}
                        <div class="text-over" {if $search.get_tree == "multi_level"}style="padding-{$direction}: {math equation="x*14" x=$page.level|default:0}px;"{/if}>
                            {if $page.subpages || $page.has_children}
                                {$_dispatch=$dispatch|default:"pages.manage"}
                                {if $except_id}
                                    {$except_url="&except_id=`$except_id`"}
                                {/if}
                                <a href="#" alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_page{$combination_suffix}_{$page.page_id}" class="cm-combination-pages{$combination_suffix} {if $expand_all && !$hide_show_all}hidden{/if}" {if $page.has_children}onclick="Tygh.$.ceAjax('request', '{"$_dispatch?parent_id=`$page.page_id`&get_tree=multi_level`$except_url`&display=`$display`&checkbox_name=`$checkbox_name`&combination_suffix=`$combination_suffix`{if $is_exclusive_page_type}&page_type={$page.page_type}{/if}"|fn_url nofilter}', {$ldelim}result_ids: 'page{$combination_suffix}_{$page.page_id}', caching: true{$rdelim});"{/if}><span class="icon-caret-right"></span></a>
                                <a href="#" alt="{__("collapse_sublist_of_items")}" title="{__("collapse_sublist_of_items")}" id="off_page{$combination_suffix}_{$page.page_id}" class="cm-combination-pages{$combination_suffix} {if !$expand_all || $hide_show_all}hidden{/if}"><span class="icon-caret-down"></span> </a>
                            {elseif $search.get_tree == "multi_level"}
                                <span style="padding-{$direction}: 14px;">&nbsp;</span>
                            {/if}


                            {if !$picker}<a href="{"pages.update?page_id=`$page.page_id`&come_from=`$_come_from`"|fn_url}" {if $page.status == "N"}class="manage-root-item-disabled"{/if} id="page_title_{$page.page_id}" title="{$page.page}">{else}<label class="inline-label" for="radio_{$page.page_id}" id="page_title_{$page.page_id}">{/if}
                                {$page.page}
                            {if !$picker}</a>{else}</label>{/if}

                            {if $show_id || $page.page_type && !$is_exclusive_page_type}
                                <span class="muted"> <small>
                                    {if $show_id} #{$page.page_id}{/if}
                                    {if $show_id && $page.page_type && !$is_exclusive_page_type}, {/if}
                                    {if $page.page_type && !$is_exclusive_page_type} 
                                        {$pt=$page_types[$page.page_type]} 
                                        {__($pt.single)}
                                    {/if}
                                </small></span>
                            {/if}
                            <div class="shift-left">
                                {include file="views/companies/components/company_name.tpl" object=$page}
                            </div>
                        </div>
                    {/strip}
                </td>
                {if !$picker}
                    <td width="10%" class="mobile-hide" data-th="{__("tools")}">
                        <input type="hidden" name="pages_data[{$page.page_id}][parent_id]" size="3" maxlength="10" value="{$page.parent_id}" />
                        {capture name="tools_list"}
                            {if $search.get_tree}
                                {$multi_level="&multi_level=Y"}
                            {/if}
                            {if !$picker}
                                {$_href="pages.update?page_id=`$page.page_id`&come_from=`$_come_from`"}
                            {/if}
                            <li>{btn type="list" text=__("edit") href=$_href}</li>
                            {if "ULTIMATE"|fn_allowed_for && $allow_save || !"ULTIMATE"|fn_allowed_for}
                                <li>{btn type="list" text=__("delete") class="cm-confirm" href="pages.delete?page_type=`$page.page_type`&page_id=`$page.page_id``$multi_level`&come_from=`$_come_from`" method="POST"}</li>
                            {/if}
                        {/capture}
                        <div class="hidden-tools">
                            {dropdown content=$smarty.capture.tools_list}
                        </div>
                    </td>
                {/if}
                {if !$hide_delete_button}
                    <td width="10%" class="nowrap right" data-th="{__("status")}">
                        {if "ULTIMATE"|fn_allowed_for && $allow_save || !"ULTIMATE"|fn_allowed_for}
                            {include file="common/select_popup.tpl" id=$page.page_id status=$page.status hidden=true object_id_name="page_id" table="pages" popup_additional_class="dropleft"}
                        {/if}
                    </td>
                {/if}
            </tr>
        </table>

        {if $page.subpages || $page.has_children}
            {include file="views/pages/components/pages_tree.tpl" pages_tree=$page.subpages parent_id=$page.page_id show_id=$show_id}
        {/if}
    </div>
{/foreach}

{if $parent_id}<!--page{$combination_suffix}_{$parent_id}--></div>{/if}